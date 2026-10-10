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

## 8. Decisiones escaladas — resueltas el 2026-10-09

Las 6 decisiones de la versión anterior de esta sección ya se revisaron con el dueño del producto, apoyadas además en evidencia de [`Merkamigo_estrategia_de_ventas.md`](../Merkamigo_estrategia_de_ventas.md) (guion de venta real usado en campo, versión del 29 sep 2026). Quedan registradas aquí como decisión tomada, no como pregunta abierta:

1. **Trial de 14 días → se retira el texto de `/planes-y-precios`.** El guion de ventas real (reunión de 15-20 min, sección "Cierre y pago") nunca ofrece un período de prueba — va directo de la demo al pago. Se alinea el copy con lo que el checkout ya hace hoy (cobra de inmediato); no se toca el backend de `SubscribeToPlan`/`ApplyApprovedPayment`, que ya está bien.
2. **Reversar comisión de un pedido reembolsado → restar del `CommissionCharge` abierto si aún no se cobró.** Cubre el caso más común (reembolso dentro de los 7 días de colchón antes del cobro semanal, §2.2). No se construye todavía un mecanismo de ajuste/crédito para comisiones ya cobradas — eso queda para si la frecuencia real de reembolsos tardíos lo justifica.
3. **Comisión `fallida` → reintento automático con backoff** (ej. a los 2, 5 y 10 días) antes de escalarla a revisión manual en el panel de conciliación (§1.4, todavía por construir).
4. **Asistente IA → pasa de pago único "de por vida" a suscripción mensual con cobro automático recurrente**, reutilizando el mismo patrón de tarjeta guardada que ya usa la renovación del plan (`wompi_payment_source_id`/`auto_renew_enabled`, §2.3) en vez de construir un mecanismo de cobro nuevo. Esto **cambia el guion de ventas vigente**, que hoy trata el asistente IA como "servicio puntual" de pago único igual que Vitrina asistida/Kit Arranca Bonito ([`Merkamigo_estrategia_de_ventas.md`](../Merkamigo_estrategia_de_ventas.md), filas de la tabla de oferta) — ese documento debe actualizarse junto con el código, o el equipo comercial seguirá vendiéndolo como pago único.
   - **Migración de compradores existentes ("de por vida"):** se migran todos a mensual, con aviso previo (no se respeta la condición de por vida de forma indefinida). Pendiente de definir junto con Legal/Comercial antes de PR4: con cuánta anticipación se avisa, qué pasa si no actualizan su método de pago a tiempo (¿se suspende el acceso o hay un período de gracia?), y el texto exacto del aviso — el TODO pide explícitamente "respeto a condiciones aceptadas" al migrar compras existentes, así que el aviso y el plazo deben quedar documentados antes de ejecutar la migración, no solo en el código.
5. **Recompra del add-on de IA cuando ya se tiene por plan → se bloquea/oculta.** Ya decidido por el propio guion de ventas, sin necesidad de preguntarlo: "Ya incluido en el plan Negocios — no cobrarlo aparte a quien ya lo tenga" ([`Merkamigo_estrategia_de_ventas.md:22,26`](../Merkamigo_estrategia_de_ventas.md)).
6. **`buyer_user_id` → cambiar `cascadeOnDelete()` a `nullOnDelete()`** al tocar esa migración para el checkout de invitado (PR3), para no seguir perdiendo el historial contable de ventas cuando se borra una cuenta de cliente. Resuelto como mejor práctica obvia, sin necesidad de escalarlo — ver §3.

### 8.1 Hallazgo adicional confirmado al revisar el guion de ventas

`Merkamigo_estrategia_de_ventas.md` (sección "Revisión previa a la visita") registra un `TypeError` en `Plan.php:67` al abrir "Tu plan" (29 sep 2026). **Ya está corregido**: `Plan::limit()` castea explícitamente a `(int)` con un comentario que describe ese mismo bug ([`app/Domain/Billing/Models/Plan.php:71-76`](../app/Domain/Billing/Models/Plan.php#L71-L76)), y el commit que lo corrigió (`a2832fe2`) es del 1 de octubre — un día después del reporte. Si el error reaparece en producción, la causa ya no sería esta; habría que diagnosticarlo aparte.

---

## 9. PR2 — seguridad/conciliación de cobros (cerrado 2026-10-09)

Ataca los hallazgos #1, #2, #3, #4 y #9 de la matriz (§7) y las decisiones #1 (trial), #2 (reversar comisión) y #3 (reintentar comisión fallida) de §8. Solo cambios aditivos (migraciones nuevas, sin tocar datos existentes) y de solo-lectura en `/admin`; nada de lo decidido en §8 sobre el asistente IA (PR4) se tocó aquí.

- **Hallazgo #1 (condición de carrera webhook/retorno):** `ApplyApprovedOrder` ahora hace todo el ciclo leer-decidir-escribir dentro de una transacción con `lockForUpdate()` sobre el pedido — el segundo proceso en llegar espera al primero y su propia guarda de idempotencia lo detiene. `AccrueCommission` suma una defensa propia (no vuelve a sumar un pedido que ya tiene `commission_charge_id`) para cualquier llamador futuro que no pase por ese lock.
- **Hallazgo #3 / decisión #2 (reversar comisión por reembolso):** nuevo estado `Order::REEMBOLSADO` (migración aditiva al enum) para distinguir "nunca se aprobó" de "se aprobó y luego se revirtió" — solo esta segunda transición dispara `ReverseCommission`, que resta el pedido del `CommissionCharge` SOLO si sigue `abierta` (si ya se está cobrando o ya se cobró, se registra en el log para revisión manual, tal como se decidió).
- **Hallazgo #4 / decisión #3 (comisión fallida sin reintento):** `ChargeCommission` ahora acepta reintentar una comisión `fallida` con el backoff decidido (2, 5 y 10 días — `ChargeCommission::RETRY_BACKOFF_DAYS`); agotados los tres reintentos, queda marcada para revisión manual (`CommissionCharge::needsManualAttention()`). También se cerró el hueco de que un error inesperado de Wompi (timeout, 5xx) dejara la comisión congelada en `pendiente_cobro` para siempre — cualquier excepción ahora también programa un reintento. De paso se corrigió que el contador "Comisiones cobradas" del comando semanal contaba cualquier intento que no lanzara excepción, no solo los que de verdad terminaron `pagada`.
- **Hallazgo #2 (sin panel de conciliación):** dos recursos nuevos de solo lectura en `/admin` → `Cobro`: **Comisiones de marketplace** (`CommissionChargeResource`, con la acción "Cobrar ahora" solo para superadmin) y **Pedidos (marketplace)** (`OrderResource`), ambos restringidos a admin/superadmin. La página "Ventas" que ya tenía el negocio también se actualizó para dejarlo reintentar su propia comisión fallida, no solo cobrar la que está abierta.
- **Decisión #1 (trial de 14 días):** **pendiente todavía** — no se tocó `/planes-y-precios` en este PR (es un cambio de copy sin dependencia técnica de los demás; se deja para el PR de claridad comercial, PR4, junto con las decisiones del asistente IA).
- **Hallazgo #9 (`auto.key`/`auto.crt` en el repo):** se dejaron de rastrear (`git rm --cached` + entrada en `.gitignore`) sin borrarlos del disco ni del histórico de git. **Sigue pendiente**, fuera del alcance de este repositorio: confirmar si son credenciales reales y, si lo son, rotarlas/revocarlas con acceso al origen real (Herd, hosting, etc.) — esto no se puede resolver solo con un commit.

**Verificación:** `php artisan test --parallel` → 1203 pruebas pasaron, 1 falla ajena a este PR (`MerkapuntosPageTest`, ver nota de PR1 — cambios de diseño sin commitear de una tarea anterior, no tocados aquí). `./vendor/bin/pint --test` y `./vendor/bin/phpstan analyse` limpios sobre todos los archivos de este PR. No se corrió `npm run build` porque PR2 no toca CSS/JS compilado.

**Nota de concurrencia:** al cerrar este PR había cambios sin commitear de otra tarea en curso en el mismo repositorio (notificaciones push, registro por teléfono, mensajería) — no se tocaron ni se incluyeron en los commits de PR2.

---

## 10. PR3 — checkout de invitado (cerrado 2026-10-09)

Ataca el hallazgo #11 y la decisión #6 de §8, con el alcance confirmado por el usuario el 2026-10-09: **solo productos digitales** — sin modelo de inventario (hallazgo #11, no construido en este PR), no hay forma segura de evitar sobreventa en productos físicos sin cuenta, así que esos siguen exigiendo login sin cambios.

- **Decisión #6 (`buyer_user_id`):** migración aditiva — pasa a nullable y de `cascadeOnDelete()` a `nullOnDelete()`. Borrar la cuenta de un comprador ya no borra su historial de ventas.
- **Checkout de invitado, detrás de bandera** (`MARKETPLACE_GUEST_CHECKOUT_ENABLED`, apagada por defecto): un visitante sin sesión que intenta comprar un producto digital recibe un formulario mínimo (nombre/correo/teléfono) en vez del login; para cualquier otro caso (flag apagado o producto no digital), la degradación es exactamente la misma que hacía el middleware `auth` (`redirect()->guest(route('login'))`, conserva la URL de retorno). La regla de elegibilidad vive en `CreateOrderCheckout` (no solo en el controlador) para que ninguna vía de entrada futura pueda saltársela.
- **Confirmación y descarga sin cuenta:** mismo patrón que `EventAttendance` (reservas de eventos sin cuenta, ya construido antes) — el invitado recibe por correo (`GuestOrderPaid`, on-demand) un enlace **firmado** a su confirmación; la descarga del producto digital también es una URL firmada contra el pedido pagado, sin crear ningún `Entitlement` (esa tabla exige `user_id`, que un invitado no tiene). El retorno de Wompi usa `signed:id` — ignora el parámetro `id` que el propio Wompi añade al volver, que de otro modo invalidaría cualquier firma.
- **No construido en este PR** (fuera de alcance, no lo pidió el usuario): asociación opcional posterior de un pedido de invitado a una cuenta ya registrada. Si se vuelve a pedir el checkout de invitado para productos físicos, antes hay que auditar/construir inventario (hallazgo #11) — no asumir que ya existe.

**Verificación:** `php artisan test --parallel` → 1216 pruebas pasaron (13 nuevas de `GuestCheckoutTest`). Hay 7 fallas en el run completo, todas confirmadas **ajenas a este PR** (reproducidas igual con `git stash` aplicando el estado limpio de HEAD, sin mis cambios): `MerkapuntosPageTest` y `CreateEventReservationTest` ya reportadas en PR1/PR2, más `BusinessChatConversationTest` (×2), `SwitchExperienceTest` y `WeeklyReportsAndAlertsTest` (×2) — estas tres últimas aparecen por primera vez porque el commit `3fff6ead` (de la tarea concurrente de notificaciones/registro, ya fusionada a esta rama) introdujo una notificación `StorefrontPublished` inesperada; no se investigó más a fondo porque no es código de este PR. `./vendor/bin/pint --test` y `./vendor/bin/phpstan analyse` limpios sobre todos los archivos de PR3 (las 3 fallas pre-existentes de PHPStan en este dominio, ya documentadas en PR2, siguen siendo las mismas).

**Nota de concurrencia:** la tarea concurrente detectada en PR2 (notificaciones push, registro, mensajería) ya se fusionó a esta rama como el commit `3fff6ead`. Al cerrar PR3 hay una NUEVA tanda de cambios sin commitear de esa misma tarea (`SystemMaintenance`, envío de correo de prueba) — tampoco se tocaron ni se incluyeron aquí.

---

## 11. PR4 — asistente IA mensual, recompra bloqueada y trial retirado (cerrado 2026-10-10)

Ataca las decisiones #1, #4 y #5 de §8.

- **Decisión #1 (trial de 14 días):** retirado el texto de `/planes-y-precios`. El campo `trial_days` se deja intacto en `Plan`/`PlanSeeder` por si se decide activarlo de verdad más adelante — solo se quitó la promesa en la página, no la capacidad del modelo.
- **Decisión #5 (recompra bloqueada):** `CreatePaymentCheckout` rechaza (como las demás reglas de este action, `InvalidArgumentException`) comprar un add-on tipo `entitlement` que el negocio ya tiene activo, ya sea por su plan (`canUseAiChatbot()`) o por una compra anterior (`hasEntitlement()`). La vista "Impulsa tu negocio" ya ni lo muestra en esos casos — doble defensa (oculto + bloqueado), como se decidió.
- **Decisión #4 (asistente IA → suscripción mensual):**
  - `BillingProductSeeder`: `asistente-ia` pasa de `expires_in_days: null` a `30`. **Los entitlements ya otorgados ANTES de este cambio quedan intactos** (siguen de por vida, `expires_at = null`) — migrarlos a mensual requiere el aviso previo que el usuario pidió, y su plazo/texto todavía no está definido con Comercial/Legal (ver punto 4 de §8). Este PR construye el mecanismo técnico, no ejecuta esa migración de clientes ya existentes.
  - Nuevas `ChargeEntitlementRenewal` y `ProcessEntitlementRenewals` (cron diario, `billing:process-entitlement-renewals`, mismo horario que la renovación de planes) — mismo patrón exacto que `ChargeSubscriptionRenewal`/`ProcessSubscriptionRenewals` (tarjeta guardada del negocio, sondeo de estado final), reutilizando `ApplyBillingProductPurchase::applyEntitlement()` sin duplicar su lógica de extensión de `expires_at`.
  - Un negocio que ya está en el plan Negocios nunca se cobra el add-on por separado, ni al comprarlo ni al renovarlo (mismo criterio que la decisión #5).
  - **Requiere tarjeta guardada desde el primer pago**: sin eso no habría forma de cobrar el segundo mes. `CreatePaymentCheckout` lo exige antes de dejar pagar, y la vista manda primero a "Tu plan" a guardar una si no hay ninguna.
- **No construido en este PR** (fuera de alcance explícito): la migración real de compradores existentes a mensual (pendiente del aviso de Comercial/Legal).

### 11.1 Actualización 2026-10-10 — periodo de gracia igual al de planes + doc de ventas

Pedido del usuario tras cerrar PR4: "deja el cobro mensual igual de robusto que la renovación de planes" + "actualiza Merkamigo_estrategia_de_ventas.md".

- **Periodo de gracia:** `BusinessEntitlement` gana `status` (`activa`/`en_gracia`) y `grace_ends_at` (migración aditiva), espejando `Subscription`/`ProcessSubscriptionRenewals` exactamente: un entitlement recurrente vencido sin tarjeta guardada, o cuyo cobro es rechazado, entra a `en_gracia` (3 días) en vez de perder el acceso de inmediato — `isActive()` sigue dando `true` mientras la gracia no termine. Se notifica al negocio (`EntitlementRenewalDue`, mismo patrón que `SubscriptionRenewalDue`) cuando no hay tarjeta guardada. El reintento diario durante la gracia, al aprobarse, repone `status=activa` y recalcula `expires_at` desde el momento del cobro — nunca desde la fecha vieja vencida, mismo criterio que usan los planes. Agotada la gracia sin pago, el entitlement queda vencido y deja de reintentarse solo (no hay "plan gratis" al que degradar, a diferencia de los planes — no se construyó un equivalente de `ApplyPlanDowngrades` porque no hace falta: `isActive()` ya refleja el vencimiento sin que nada más tenga que actuar).
- **`Merkamigo_estrategia_de_ventas.md` actualizado:** el asistente IA ya no se presenta como pago único en ningún punto del documento — ahora es $49.900/mes con renovación automática, se aclara que exige tarjeta guardada, y queda explícito que los compradores de antes del cambio conservan su acceso de por vida hasta que exista el aviso previo (todavía pendiente de Comercial/Legal) para migrarlos.
- También se corrigieron, a pedido del usuario, las 6 fallas de prueba que venían arrastrándose desde PR3 — ninguna era de ventas/rentabilidad (ver detalle en los mensajes de commit `5a3c23b3`): dos por un `Notification::fake()` mal ubicado que capturaba el nuevo `StorefrontPublished` de la tarea concurrente, una por el nuevo requisito de teléfono en el registro (cambio deliberado de esa misma tarea), y una por un texto de UI ya rediseñado (`rewards-banner.blade.php`). Se sincronizaron las pruebas con el comportamiento real, sin revertir ninguna regla de negocio ajena.

**Verificación (PR4 + §11.1 combinados):** `php artisan test --parallel` → **1239 pruebas pasaron, 0 fallas** (primera vez en toda esta rama de trabajo con el run completo en verde). `./vendor/bin/pint --test` limpio. `./vendor/bin/phpstan analyse` limpio salvo el mismo patrón `?->` ya aceptado en `ChargeSubscriptionRenewal`/`ChargeCommission`/`ChargeEntitlementRenewal` (relaciones genuinamente nulables que Larastan no detecta como tales ni con `@property` manual — confirmado en dos intentos separados en esta rama).

---

## 12. PR5 — dashboard de embudo y rentabilidad (cerrado 2026-10-10)

Ataca el hallazgo #2 de la matriz (§7, "sin panel interno de conciliación") en su parte de rentabilidad gerencial, y la instrumentación de embudo que §6 dejaba pendiente ("no existe el equivalente al checkout normal de Marketplace").

- **Embudo del checkout de Marketplace:** nuevos tipos de `AnalyticsEvent` — `MARKETPLACE_CHECKOUT_STARTED` (disparado en `CreateOrderCheckout`, justo al crear el pedido) y `MARKETPLACE_PAYMENT_APPROVED`/`MARKETPLACE_PAYMENT_FAILED` (en `ApplyApprovedOrder`, idempotentes igual que el resto de esa acción). El paso de "vista" ya lo cubría `PRODUCTO_VIEW`. **No se inventó un paso "clic en comprar"** distinto de "checkout iniciado": en la UI real el botón del producto va directo a crear el pedido, no hay un clic intermedio que medir por separado.
- **`RentabilidadOverview`** (widget nuevo en el dashboard de `/admin`, solo admin/superadmin — moderador no lo ve): GMV histórico (pedidos pagados, nunca sumado a la comisión), comisión cobrada/pendiente/que necesita atención (reutiliza los estados que ya dejó visibles PR2), MRR separado en **planes** (suscripciones de pago activas, excluye prueba y plan gratis) y **add-ons** (entitlements recurrentes activos o en gracia, excluye los de por vida), negocios con Wompi conectado, y la conversión de checkout→pago de los últimos 30 días.
- **Deliberadamente NO construido** (regla 0 del TODO, "no inventar métricas"): CAC, ARPA, churn, margen de contribución y break-even. Todos necesitan costos (pauta, infraestructura, soporte, fotografía/horas) que hoy no se capturan en ningún lugar del sistema — mostrar un número ahí sería inventarlo, no calcularlo. Tampoco se construyeron cohortes semana a semana (el TODO las pide, pero son una vista aparte, más grande, que no bloquea tener ya visibles GMV/comisión/MRR).

**Verificación:** `php artisan test --parallel` → **1249 pruebas pasaron, 0 fallas**. `./vendor/bin/pint --test` limpio. `./vendor/bin/phpstan analyse` limpio salvo los mismos dos patrones `?->` ya aceptados en esta rama (uno nuevo en `RentabilidadOverview` sobre `BusinessEntitlement::sourceBillingProduct`, misma categoría que los anteriores).

---

## 13. Siguientes pasos

Con PR1-PR5 cerrados, de los 6 PR originales del TODO solo queda:

- **PR 6 (piloto de 20-30 comercios):** operativo, no requiere código — guías, guiones, tracking de campañas y checklist de onboarding (ver sección "Plan de entrega" del TODO original). Nada de código bloquea empezarlo.
- **Antes de activar la migración de clientes existentes del asistente IA a mensual:** definir con Comercial/Legal el plazo de aviso y su texto exacto (§8 punto 4) — el mecanismo técnico ya existe desde PR4/§11.1, falta la decisión comercial para ejecutarlo.
- **Si se quiere ir más allá de lo que pidió el TODO en P1.4:** cohortes semana a semana, y CAC/ARPA/churn/margen de contribución una vez existan costos reales capturados (pauta, infraestructura, soporte) — ninguno de los dos bloquea el piloto de PR6.
