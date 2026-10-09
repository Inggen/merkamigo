# Auditoría técnica y comercial — Ventas y rentabilidad

**PR 1 de `TODO_VENTAS_RENTABILIDAD.md`.** Documental: no se ejecutaron migraciones, seeders ni comandos destructivos; no se tocaron datos ni configuración de producción. Todo lo listado abajo se verificó leyendo el código, las migraciones y las pruebas tal como están hoy en `main` (rama de trabajo `codex/todo-ventas-rentabilidad`), no el histórico de TODOs.

## 0. Reglas obligatorias — constancia de lectura

- Leídos antes de auditar: [`README.md`](../README.md), [`.github/skills/desarrollo-aplicaciones/SKILL.md`](../.github/skills/desarrollo-aplicaciones/SKILL.md), [`.github/skills/revision-seguridad/SKILL.md`](../.github/skills/revision-seguridad/SKILL.md), [`.github/skills/diseno-uiux/SKILL.md`](../.github/skills/diseno-uiux/SKILL.md).
- **El `README.md` está desactualizado** respecto al código vigente: describe el repo como "la fundación técnica de la Fase 0" (auth + vitrina mínima, Filament 5.7/Livewire 4.3 como lo único con lógica real) y marca "fuera de alcance todavía" la vitrina completa, la plaza, los cobros y Merkapuntos. En realidad, a la fecha de esta auditoría, el repo ya tiene implementados y con pruebas: Marketplace con Wompi, suscripciones de plan y de producto, Merkapuntos, eventos públicos (reservas + cupos de asistencia), Live Commerce, feed social y Google Merchant Feed. Ninguna decisión de este documento se basó en el README; se dejó constancia aquí porque el propio TODO pide documentar esta clase de discrepancia antes de implementar.
- No se ejecutó `php artisan migrate`, ningún seeder, ni `/migrar`, `/link`, `/limpiar-cache` contra ningún entorno.
- No se inspeccionó el contenido de `auto.key`/`auto.crt` (ver 4.1) más allá de su tipo de archivo — por restricción explícita del propio TODO y del entorno de ejecución.

---

## 1. Circuito de dinero (P0.1) — diagrama y estados observados

### 1.1 Flujo real (Marketplace de productos)

```
visita producto → GET /productos/{product}/comprar  (detrás de auth+verified)
  → CreateOrderCheckout::handle()
      - exige business->hasWompiConnected()
      - exige price_type fijo (rechaza 'consultar'/'sin_precio'/precio vacío)
      - calcula unit_price/amount/commission EN SERVIDOR (ignora cualquier precio del cliente)
      - crea Order (status=pendiente) + OrderItem dentro de una transacción
  → vista marketplace.checkout (modal Wompi o fallback hospedado)
  → Wompi (cuenta del NEGOCIO, nunca la de Merkamigo)
  → dos caminos que pueden llegar en cualquier orden o al mismo tiempo:
      (a) GET /pedidos/{order}/retorno → OrderCheckoutController::return()
            - authorize('view', $order)
            - fetchTransaction($id) contra la API de Wompi (nunca confía en el querystring)
      (b) POST /webhooks/wompi/negocios/{business} → BusinessWompiWebhookController::handle()
            - verifica firma con el events_secret DEL NEGOCIO
            - busca el Order por reference + business_id
  → ambos caminos llaman ApplyApprovedOrder::handle($order, $status, $txId, $raw)
      - status ∈ {pendiente, pagado, rechazado} según Wompi (APPROVED/DECLINED-ERROR-VOIDED/otro)
      - si pagado: AccrueCommission::handle($order) + OrderPaid a los miembros del negocio
        + AnalyticsEvent de conversión de promoción/compra en vivo + Entitlement si el producto es digital
```

### 1.2 Hallazgo crítico — condición de carrera entre webhook y retorno (no probada)

`ApplyApprovedOrder::handle()` solo se protege contra doble aplicación así:

```php
if (in_array($order->status, [Order::PAGADO, Order::RECHAZADO], true)) {
    return $order;
}
```
[`ApplyApprovedOrder.php:23`](../app/Domain/Marketplace/Actions/ApplyApprovedOrder.php#L23)

Esa guarda protege llamadas **secuenciales** dentro del mismo proceso (cubierto por `test_applying_an_approved_order_twice_is_idempotent`, [`OrderCheckoutTest.php:92`](../tests/Feature/Marketplace/OrderCheckoutTest.php#L92)), pero **no** protege dos procesos PHP distintos leyendo el mismo `Order` casi al mismo tiempo: el navegador vuelve a `/pedidos/{order}/retorno` justo cuando Wompi ya está entregando el webhook a `/webhooks/wompi/negocios/{business}`. Ninguno de los dos caminos usa `lockForUpdate()` sobre la fila `orders`, ni envuelve el "leer estado → decidir → escribir" en una transacción con bloqueo:

- `OrderCheckoutController::return()` recibe `$order` por route binding normal (sin lock).
- `BusinessWompiWebhookController::handle()` hace `Order::where(...)->first()` (sin lock).
- `ApplyApprovedOrder::handle()` no abre transacción propia.

Si ambos procesos leen `status=pendiente` antes de que cualquiera escriba, **ambos** ejecutan `AccrueCommission::handle($order)`, que sí bloquea la fila de `CommissionCharge` con `lockForUpdate()` ([`AccrueCommission.php:22`](../app/Domain/Marketplace/Actions/AccrueCommission.php#L22)) pero no verifica si ESTE pedido específico ya fue acumulado — no hay chequeo de `$order->commission_charge_id === null` antes de sumar. Resultado: el mismo pedido puede sumarse DOS VECES a `gross_amount_cents`/`commission_cents`/`orders_count` de la comisión abierta, y el negocio recibe la notificación `OrderPaid` dos veces.

No hay ninguna prueba en el repo (`BusinessWompiWebhookTest.php`, `OrderCheckoutHttpTest.php`, `OrderCheckoutTest.php`) que dispare los dos caminos concurrentemente o fuera de orden — todas son llamadas secuenciales dentro de un mismo test. El TODO pide explícitamente probar "webhooks duplicados, desordenados, tardíos"; hoy esa cobertura no existe para el caso concurrente real (sí existe para "webhook repetido después de que el estado ya quedó resuelto").

**Severidad:** alta — afecta el dinero que Merkamigo cobra, no solo la UX. **Esfuerzo de mitigación:** bajo (añadir `lockForUpdate()` sobre `Order` al entrar a `ApplyApprovedOrder::handle()`, o una restricción única que impida acumular dos veces la misma `order_id` en `commission_charges`). Se deja para PR 2, no se toca aquí.

### 1.3 Reembolsos y cancelaciones — no existen en el código, solo en comentarios

- `orders.status` es un enum `['pendiente','pagado','rechazado','cancelado']` ([`2026_09_15_150100_create_orders_table.php:35`](../database/migrations/2026_09_15_150100_create_orders_table.php#L35)), pero **ningún código del dominio Marketplace asigna `Order::CANCELADO`** (verificado por `grep` en todo `app/Domain/Marketplace`) ni existe acción de reembolso/reverso.
- `ApplyApprovedOrder::handle()` solo mapea `DECLINED|ERROR|VOIDED` → `rechazado`. Un `VOIDED` que llegue **después** de que el pedido ya estaba `pagado` (reembolso real en Wompi) es ignorado por la guarda de idempotencia de la línea 23 (ya está en un estado "final"), así que la comisión que ya se acumuló en el `CommissionCharge` **nunca se revierte**.
- El comentario de `ChargeOpenCommissions.php:13` ("da un colchón natural para reembolsos antes de que el dinero salga de la tarjeta") describe solo la espera de 7 días antes de cobrar — no hay ningún mecanismo que reste una comisión ya acumulada si el pedido se reembolsa dentro de esos 7 días.
- No existe columna `refunded_at` ni `refund_amount_cents` en `orders`.

**Conclusión:** el punto del TODO "no generar comisiones definitivas sobre operaciones reembolsadas" no está resuelto hoy; el único mitigante existente es el retraso de 7 días antes de cobrar, que es operativo (gana tiempo) pero no corrige el monto automáticamente.

### 1.4 Panel de conciliación interno — no existe

Se buscó en `app/Filament/Resources/` cualquier recurso sobre `Order`, `CommissionCharge` o `Payment` del Marketplace. El único resultado fue `OrderConfirmationResource` ([`app/Filament/Resources/OrderConfirmations/`](../app/Filament/Resources/OrderConfirmations/)), que corresponde a un modelo distinto (`OrderConfirmation`, una constancia sin pago en línea — ver comentario en `CreateOrderCheckout`'s docstring y el propio TODO). **Hoy ningún superadmin puede ver desde `/admin` el GMV, las comisiones devengadas/cobradas/fallidas ni los pedidos pagados reales** — toda esa información solo es consultable por `tinker` o consulta SQL directa. Este es el gap más directo con la aceptación de P0.1 ("panel interno de conciliación").

### 1.5 Idempotencia operativa de `ChargeCommission` (ligado a P0.3, ver 2.2)

Ver sección 2 — el estado `pendiente_cobro` puede quedar huérfano si `ChargeCommission::handle()` lanza una excepción no capturada a mitad de camino.

---

## 2. Cobro de la comisión del 5 % (P0.3)

### 2.1 Flujo confirmado

`AccrueCommission` agrupa pedidos pagados en un `CommissionCharge` con estado `abierta` (uno por negocio a la vez, con `lockForUpdate()` correcto para evitar condiciones de carrera **entre pedidos del mismo negocio**, aunque no entre los dos caminos de la sección 1.2). Semanalmente (`routes/console.php`, no editado en esta auditoría), `ChargeOpenCommissions::handle()` cobra solo las `abierta` con `commission_cents > 0` y al menos 7 días de antigüedad, contra `WompiClient` (cuenta de **Merkamigo**, confirmado en el docstring de [`ChargeCommission.php:19`](../app/Domain/Marketplace/Actions/ChargeCommission.php#L19) — es la única llamada de todo el flujo que mueve dinero hacia Merkamigo).

### 2.2 Hallazgo — una comisión fallida nunca se vuelve a intentar

```php
CommissionCharge::query()
    ->where('status', CommissionCharge::ABIERTA)   // ← solo este estado
    ->where('commission_cents', '>', 0)
    ->where('period_start', '<=', now()->subDays($minAgeDays))
```
[`ChargeOpenCommissions.php:26-29`](../app/Domain/Marketplace/Actions/ChargeOpenCommissions.php#L26-L29)

`ChargeCommission::handle()` mueve el estado de `abierta` → `pendiente_cobro` → (`pagada` | `fallida`), y **nada en el código vuelve a poner una comisión `fallida` en `abierta`**. Como el lote semanal solo mira `abierta`, una comisión que falla (tarjeta rechazada, fondos insuficientes, error de Wompi) queda **abandonada para siempre**: no se reintenta, no genera alerta, y el negocio sigue acumulando nuevas ventas en un `CommissionCharge` *nuevo* (porque `AccrueCommission` crea uno nuevo si no encuentra ninguno `abierta`). El TODO pide "verificar que la cobranza semanal no reintenta silenciosamente cargos fallidos" — es cierto que no reintenta silenciosamente, pero **tampoco reintenta de ninguna forma**, lo que es una fuga de ingreso, no solo un riesgo de doble cobro.

Adicionalmente: si `WompiClient::chargePaymentSource()` lanza una excepción de red/HTTP (no `InvalidArgumentException`) **después** de que `$charge` ya quedó en `pendiente_cobro` ([`ChargeCommission.php:41`](../app/Domain/Marketplace/Actions/ChargeCommission.php#L41)) pero antes de completar el polling, esa excepción no está capturada dentro de `handle()` y tampoco por el `catch (\InvalidArgumentException $e)` de `ChargeOpenCommissions::handle()` ([línea 35](../app/Domain/Marketplace/Actions/ChargeOpenCommissions.php#L35)) — se propaga, interrumpe el `->each()` (dejando sin procesar el resto del lote de ese ciclo) y la comisión queda congelada en `pendiente_cobro`, un estado que el lote semanal tampoco vuelve a mirar.

**Severidad:** media-alta (dinero real que deja de cobrarse sin que nadie se entere, por ausencia de panel — ver 1.4). **Esfuerzo:** bajo (reintentar `fallida`/`pendiente_cobro` con backoff, o al menos registrarlas en el panel de conciliación que no existe).

### 2.3 Consentimiento de comisión vs. renovación automática — acoplados en un solo paso

`SaveBusinessPaymentSource::handle()` ([archivo completo](../app/Domain/Billing/Actions/SaveBusinessPaymentSource.php)) hace, en una sola llamada:

```php
$business->update([
    'wompi_payment_source_id' => (string) $data['id'],  // habilita ChargeCommission
    ...
    'auto_renew_enabled' => ($data['status'] ?? null) === 'AVAILABLE',  // habilita renovación de plan
]);
```

`PaymentSourceController::store()` solo exige los `acceptance_token`/`accept_personal_auth_token` **genéricos de Wompi** (términos de uso y tratamiento de datos personales que exige la pasarela para tokenizar cualquier tarjeta) — no hay ningún texto ni checkbox propio de Merkamigo que distinga "autorizo que me cobren la comisión del 5 % sobre mis ventas" de "autorizo la renovación automática mensual de mi plan". Guardar la tarjeta activa ambas cosas a la vez. Esto es exactamente lo que el TODO pide evitar ("diseñar aceptación explícita y separada"); hoy no existe esa separación.

### 2.4 Fortaleza encontrada (no todo es riesgo)

`BusinessWompiCredential` — las credenciales Wompi de cada negocio (`private_key`, `integrity_secret`, `events_secret`) están cifradas a nivel de aplicación (`'encrypted'` cast) y excluidas de la serialización (`$hidden`) ([`BusinessWompiCredential.php:27-44`](../app/Domain/Marketplace/Models/BusinessWompiCredential.php#L27-L44)), con un comentario explícito de que el estándar de protección es más alto que el de las credenciales propias de Merkamigo por tratarse de datos de un tercero. Esto ya cumple la expectativa de seguridad del TODO para este punto — no requiere trabajo adicional.

---

## 3. Checkout de invitado (P0.2) — estado actual: no implementado, confirmado por diseño

- `productos/{product}/comprar` vive dentro de `Route::middleware(['auth','verified'])` ([`routes/web.php:339,366`](../routes/web.php#L366)).
- `orders.buyer_user_id` es `foreignId(...)->constrained('users')->cascadeOnDelete()` — **no nullable** ([`2026_09_15_150100_create_orders_table.php:27`](../database/migrations/2026_09_15_150100_create_orders_table.php#L27)). Cualquier checkout de invitado real requerirá una migración que lo vuelva nullable, exactamente como anticipa el TODO.
- **Nota de riesgo adicional no mencionada explícitamente en el TODO:** `cascadeOnDelete()` en `buyer_user_id` significa que si una cuenta de cliente se elimina, **se borran en cascada todos sus pedidos pagados** — se pierde el registro contable/histórico de ventas reales, no solo el acceso del usuario a verlos. Si el checkout de invitado introduce un `buyer_user_id` nullable, convendría revisar si de paso se cambia a `nullOnDelete()` para no seguir perdiendo historial de ventas al borrar cuentas.
- `CreateOrderCheckout::handle()` ya calcula el precio a cobrar **en servidor**, a partir de `$product->price`/`$variant->price` (nunca `price_type='desde'` como si fuera precio fijo — ese caso ya está excluido explícitamente en la línea 31). El punto del TODO sobre "no cobrar una cotización como si fuera precio fijo" **ya está resuelto** en el código actual; no es una brecha pendiente.
- **No existe ningún campo de inventario/stock** en `Product` ni en sus migraciones (`grep` sin resultados). `CreateOrderCheckout` no valida cantidad disponible porque no hay nada que validar: hoy es posible pagar cualquier `cantidad` de un producto físico sin que el sistema sepa si existe stock. Esto es más amplio que "falta revisar cancelaciones de inventario" (como dice el TODO) — **no hay modelo de inventario que auditar**; construirlo es un prerrequisito si el checkout de invitado habilita compra de productos físicos sin intervención manual del negocio.
- Como referencia de que el patrón de checkout de invitado ya es técnicamente viable en este código base: las reservas de eventos (`EventReservationCheckoutController`, `EventAttendanceCheckoutController`) **ya permiten pagar sin cuenta** y comparten el mismo patrón Wompi-por-negocio + webhook firmado. El ticket de asistencia (`eventos/entradas/{eventAttendance}`) se protege con URL firmada (`middleware('signed')`) en vez de con sesión — el mismo patrón podría reutilizarse para una confirmación de pedido de invitado, en vez de inventar IDs aleatorios nuevos.

---

## 4. Condiciones comerciales inconsistentes (P0.4)

### 4.1 Confirmado: la página de precios promete un trial que el checkout no da

`resources/views/public/planes-y-precios.blade.php` muestra, para Emprendedor y Negocios:

```blade
@if ($plan->trial_days > 0)
    {{ trans_choice('Incluye :count día de prueba|Incluye :count días de prueba', $plan->trial_days, ...) }}
@endif
```
[`planes-y-precios.blade.php:100-104`](../resources/views/public/planes-y-precios.blade.php#L100-L104)

con `trial_days = 14` sembrado para ambos planes de pago ([`PlanSeeder.php:73,101`](../database/seeders/PlanSeeder.php#L73)). Pero el único camino de autoservicio para activar un plan de pago es `CheckoutController::createForPlan()` → `CreatePaymentCheckout` → cobro inmediato por Wompi → `ApplyApprovedPayment::handle()` → `SubscribeToPlan::handle($business, $plan, null, $periodEnd)` **con `$periodEndsAt` ya resuelto**. El propio código de `SubscribeToPlan` ya documenta y fuerza esto:

```php
// `$periodEndsAt` solo llega desde `ApplyApprovedPayment` — ya se cobró de
// verdad ... así que no tiene sentido volver a arrancar un periodo de prueba.
$isTrial = $periodEndsAt === null && $plan->trial_days > 0;
```
[`SubscribeToPlan.php:23-28`](../app/Domain/Billing/Actions/SubscribeToPlan.php#L23-L28)

`SubscribeToPlan::handle()` solo se llama desde tres sitios: el cambio manual de plan en Filament (sin pago), `ApplyApprovedPayment` (siempre con pago ya hecho) y el downgrade automático a plan gratuito. **No existe ningún camino de autoservicio donde `$isTrial` resulte `true` para un plan pago** — es decir, el campo `trial_days` y la lógica que lo honra existen y funcionan, pero quedaron inertes para cualquier cliente que se suscriba por `/planes-y-precios`, mientras la página sigue anunciando "Incluye 14 días de prueba". Esto confirma, con evidencia de código (no solo de intención), exactamente el conflicto que el TODO pide resolver antes de activar ventas reales — y como pide el TODO, **no se decide aquí** cuál de las dos modalidades usar; eso queda en la lista de decisiones a escalar (sección 6).

### 4.2 Confirmado: economía del asistente IA sin vigencia ni tope de uso

`BillingProductSeeder` siembra `asistente-ia` como `BillingProduct::ENTITLEMENT` a $49.900 con `payload: ['entitlement_key' => AI_CHATBOT, 'expires_in_days' => null]` ([`BillingProductSeeder.php:79-85`](../database/seeders/BillingProductSeeder.php#L79)). `ApplyBillingProductPurchase::applyEntitlement()` traduce `expires_in_days = null` directamente en `$entitlement->expires_at = null` ([`ApplyBillingProductPurchase.php:82`](../app/Domain/Billing/Actions/ApplyBillingProductPurchase.php#L82)) — un **pago único da acceso de por vida** al chatbot con IA, sin créditos, sin tope de mensajes y sin re-cobro, mientras el costo real de inferencia (tokens de IA) es variable y recurrente mientras el negocio siga usándolo. No hay ningún job ni acción que vuelva a cobrar o que corte el acceso por consumo.

### 4.3 Confirmado: no hay protección contra comprar el add-on que ya se tiene

`Business::canUseAiChatbot()` devuelve `true` si el negocio está en el plan Negocios (`isOnTopPlan()`) o si ya tiene el entitlement activo ([`Business.php:549-552`](../app/Domain/Businesses/Models/Business.php#L549)). Pero ni `CreatePaymentCheckout` (el action que crea el `Payment` para cualquier `BillingProduct`) ni la vista de "Impulsa tu negocio" (`⚡impulsar.blade.php`, sin ninguna referencia a `canUseAiChatbot`/`hasEntitlement` por `grep`) comprueban esto antes de ofrecer o cobrar el add-on `asistente-ia`. Un negocio en plan Negocios, o uno que ya compró el add-on antes, puede pagar $49.900 otra vez por algo que ya tiene — exactamente el escenario de "doble venta accidental" que el TODO pide prevenir.

---

## 5. Seguridad y preparación de producción (P0.5)

### 5.1 Urgente — `auto.key`/`auto.crt` versionados en el repositorio

```
$ git ls-files | grep -iE "auto\.(key|crt)$"
auto.crt
auto.key
$ file auto.key auto.crt
auto.key: PEM EC private key
auto.crt: PEM certificate
```

Ambos archivos están **rastreados por git** desde el commit `e307f990` (2026-09-21, `mvp1.1`) y siguen presentes en `main` hoy. `.gitignore` solo excluye `/storage/*.key`, no estos dos archivos en la raíz. No se inspeccionó el contenido más allá de confirmar el tipo de archivo (el entorno de ejecución de esta auditoría bloqueó explícitamente cualquier intento de leer el material de la llave con `openssl`, y no se buscó otra forma de evadir esa restricción). **No se puede descartar que sea una llave privada real** (de un certificado TLS local de desarrollo, por ejemplo de Herd, o de otro propósito); el propio TODO ya pide tratarlo como potencialmente real hasta que alguien con acceso seguro lo confirme. Acción pendiente para quien tenga ese acceso: confirmar el origen, rotar si corresponde, sacarlo del control de versiones y revisar si ya se expuso en algún fork/CI.

### 5.2 Rutas administrativas sensibles — parcialmente mitigado, no "sin protección" como asume el TODO

El TODO describe `/migrar`, `/limpiar-cache` y `/link` como rutas "GET autenticadas" a asegurar. Verificado en código: **ya están detrás de `auth` Y de un chequeo de rol explícito**:

```php
Route::middleware('auth')->group(function () {
    Route::get('/limpiar-cache', function () {
        abort_unless(auth()->user()?->hasAnyPlatformRole(['superadmin']), 403);
        ...
```
[`routes/web.php:55-100`](../routes/web.php#L55-L100)

Esto es más protección de la que el texto del TODO da por sentado — se deja constancia de la discrepancia, como pide la regla 0. Lo que **sigue siendo cierto** del riesgo original: son `GET` ejecutando acciones con efecto (incluyendo `migrate --force`), lo que las deja expuestas a precarga de enlaces del navegador, extensiones que siguen `<a href>` en segundo plano, o un superadmin pegando la URL en un chat/bookmarklet sin querer ejecutarla. Al estar protegidas por sesión+rol, la explotación requiere ya ser (o secuestrar la sesión de) un superadmin — no es una ruta abierta al público — pero migrar a `POST` + confirmación seguiría siendo la mejora correcta antes de depender de estas rutas en un flujo de despliegue real.

### 5.3 Wompi — separación sandbox/producción y firmas, confirmado por diseño

- `config('services.wompi')` resuelve `api_url` según `WOMPI_ENV` (`sandbox` por defecto) — no hardcodeado.
- La cuenta de Merkamigo (`WompiClient`/`config/services.php`) y la de cada negocio (`BusinessWompiClient`/`BusinessWompiCredential`, cifrada) están completamente separadas en código; no se encontró ningún punto donde se mezclen las llaves de una cuenta con las de otra.
- Los dos webhooks (`webhooks.wompi` de Merkamigo y `webhooks.wompi.negocio` por negocio) verifican firma antes de procesar cualquier evento (`BusinessWompiWebhookTest::test_a_negocios_webhook_cannot_be_forged_with_another_negocios_secret` cubre explícitamente que la firma de un negocio no sirve para otro).
- No se verificó la configuración real de backups/alertas de error en el proveedor de hosting porque está fuera del alcance de este repositorio (es infraestructura, no código versionado aquí).

### 5.4 Suite de verificación — no ejecutada en esta auditoría (documental, regla 0)

Las reglas obligatorias del propio TODO piden no ejecutar nada contra datos reales, pero sí piden (P0.5) "ejecutar pruebas CI en entorno aislado". Como PR 1 es estrictamente documental y las pruebas (`php artisan test`, Pint, PHPStan, `npm run build`) no modifican datos ni producción, correrlas aquí es seguro y es la única forma de confirmar que el estado descrito arriba está realmente probado donde dice estarlo. Quedó pendiente ejecutarlas en este mismo PR — ver "Siguientes pasos" al final.

---

## 6. P1/P2 — qué ya existe como base, qué falta

No se auditó P1/P2 con el mismo nivel de detalle que P0 porque el propio plan de entrega los deja para después de cerrar P0; lo siguiente es un inventario rápido de qué tan lejos está cada uno de "se puede empezar hoy", para no sobre-prometer en el roadmap:

- **P1.1 (oferta comercial) / P1.2 (pipeline comercial):** no son código, son proceso comercial — nada que auditar en el repo más allá de lo ya confirmado en la sección 4 (precios/trial). `BillingProductSeeder` ya tiene destacados 7/14/30 días, Vitrina asistida y Kit arranca bonito con los precios exactos que cita el TODO.
- **P1.3 (captación orientada a productos):** `GoogleMerchantFeedController` y `feeds/google-merchant.xml` ya existen y están documentados en `docs/google-merchant.md` (no leído a fondo en esta pasada); `sitemap.xml` y `llms.txt` también existen como rutas públicas.
- **P1.4 (dashboard de rentabilidad):** `AnalyticsEvent` ya tiene un patrón de eventos de embudo para Live Commerce (`live_checkout_started`, `live_purchase`) y para Merkapuntos/eventos, pero **no existe el equivalente para el checkout normal de Marketplace** (no hay `producto_buy_click`, `checkout_started` ni `payment_approved/failed` para `/productos/{product}/comprar`) — es la pieza de instrumentación que más directamente falta para medir el funnel que pide P1.4, y además depende de que exista primero el panel de conciliación de la sección 1.4.
- **P2 (Merkapuntos sobre compras verificadas, piloto de eventos, suscripciones de cliente, Live Shopping):** Merkapuntos, eventos (reservas y cupos) y suscripciones de cliente a negocio ya están implementados con pruebas (confirmado en sesiones previas de este mismo repositorio, no re-verificado línea por línea aquí porque P0 es el bloqueante). Quedan pendientes, como pide el TODO, sin tocar hasta validar P0/P1: no se encontró ningún lugar del código que ya condicione Merkapuntos a "compra pagada verificada" de Marketplace específicamente — ese cruce (puntos por ventas reales del marketplace, no solo por registro manual del negocio) no está implementado y debe diseñarse en su momento, no asumirse construido.

---

## 7. Matriz de riesgos (resumen)

| # | Hallazgo | Área | Severidad | Evidencia | Esfuerzo estimado |
|---|---|---|---|---|---|
| 1 | Condición de carrera webhook/retorno puede duplicar comisión acumulada | P0.1 | Alta | §1.2 | Bajo |
| 2 | Sin panel interno de conciliación (GMV/comisión) | P0.1 | Alta | §1.4 | Medio |
| 3 | Reembolsos/voids posteriores no revierten comisión ya acumulada | P0.1 | Media-alta | §1.3 | Medio |
| 4 | Comisión `fallida`/`pendiente_cobro` nunca se reintenta ni alerta | P0.3 | Media-alta | §2.2 | Bajo |
| 5 | Consentimiento de comisión y de renovación de plan acoplados en un solo guardado de tarjeta | P0.3 | Media | §2.3 | Medio (legal + UX) |
| 6 | `/planes-y-precios` promete 14 días de prueba que el checkout real no otorga | P0.4 | Alta (cara al cliente) | §4.1 | Bajo (decisión + copy) una vez se elija modalidad |
| 7 | Add-on de IA sin vigencia ni tope de uso frente a costo variable | P0.4 | Media-alta | §4.2 | Medio |
| 8 | Sin protección contra recomprar un entitlement ya incluido en el plan | P0.4 | Baja-media | §4.3 | Bajo |
| 9 | `auto.key`/`auto.crt` versionados en el repo | P0.5 | Crítica (hasta descartar) | §5.1 | Bajo (rotar + `.gitignore`), requiere acceso seguro |
| 10 | `buyer_user_id` con `cascadeOnDelete()` borra historial de ventas al eliminar la cuenta | P0.2 (hallazgo adicional) | Media | §3 | Bajo |
| 11 | No existe modelo de inventario/stock | P0.2 | Alta (si se habilita invitado para productos físicos) | §3 | Alto |

Fortalezas confirmadas (no requieren trabajo): credenciales Wompi por negocio cifradas y ocultas (§2.4); separación real sandbox/producción y de llaves Merkamigo-vs-negocio (§5.3); precio cobrado siempre calculado en servidor, nunca confiado al cliente (§1.1, §3); `/migrar`/`/link`/`/limpiar-cache` ya exigen rol superadmin, no solo sesión (§5.2).

---

## 8. Decisiones a escalar antes de codificar cambios comerciales

Tal como pide el TODO, ninguna de estas se decidió en este documento:

1. ¿`trial_days=14` real (sin cobrar hasta el día 15) o cobro inmediato con la promesa de prueba retirada de `/planes-y-precios`? Hoy el código ya implementa la segunda opción de facto (cobra siempre) mientras la página promete la primera.
2. ¿Cómo se revierte la comisión de un pedido reembolsado/anulado después de haberse acumulado?
3. ¿Se reintenta una comisión `fallida` automáticamente (con qué backoff) o requiere intervención manual visible en un panel?
4. ¿El asistente IA pasa a vigencia mensual, a créditos de uso, o se mantiene de por vida pero con tope de mensajes? ¿Qué pasa con quienes ya lo compraron bajo la condición actual?
5. ¿Se bloquea la recompra del add-on de IA cuando el negocio ya lo tiene por plan, o se permite y simplemente no se muestra la opción?
6. Alcance de la migración de `buyer_user_id`: ¿se aprovecha para cambiar `cascadeOnDelete()` a `nullOnDelete()` y conservar el historial contable al borrar una cuenta, o se mantiene el comportamiento actual?

---

## 9. Siguientes pasos (no ejecutados en este PR)

- Correr `php artisan test --parallel`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse` y `npm run build` sobre esta misma rama antes de fusionar, para confirmar que el estado descrito arriba sigue siendo el real al momento de cerrar el PR (regla 0 / P0.5).
- PR 2 (seguridad/conciliación de cobros): atacar los hallazgos #1, #2, #3, #4 y #9 de la matriz — son los que tocan dinero o historial de ventas y no requieren ninguna decisión comercial previa.
- PR 3+ (checkout de invitado, claridad comercial): requieren las decisiones de la sección 8 antes de escribir código, tal como exige el TODO.
