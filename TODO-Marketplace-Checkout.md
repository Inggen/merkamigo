# TODO-Marketplace-Checkout.md — Pago de productos entre cliente y negocio

**Fecha:** 15 de septiembre de 2026
**Contexto:** No estaba en `TODO_social.md` tal cual — su Fase 7 ("Checkout y pedidos") asumía una capa "Merkamigo Pay" genérica sin resolver el problema real de un marketplace multi-vendedor: ¿a la cuenta de quién llega el dinero, quién paga la tarifa de la pasarela, y quién es responsable fiscalmente de la venta? Esta sesión resolvió eso con el usuario directamente (dueño de Merkamigo) antes de programar, dado lo que está en juego (dinero real, cumplimiento tributario).

## 1. Decisión de arquitectura (tomada con el usuario, no asumida)

**Cada negocio conecta su PROPIA cuenta de Wompi.** El pago de un cliente va directo a la cuenta del negocio — Merkamigo nunca recauda dinero de terceros. Se llegó a esto después de investigar que Wompi no ofrece split-payment automático (solo un producto separado de "Pagos a terceros/Payouts": cobrar completo y luego dispersar manualmente, lo que sí habría convertido a Merkamigo en recaudador de dineros de terceros, con toda la carga fiscal que el usuario específicamente no quería asumir).

Consecuencias directas de esta decisión:
- **Responsabilidad fiscal de la venta**: queda 100% del lado del negocio — es su cuenta, su plata, su NIT/cédula, su factura. Merkamigo no calcula ni retiene IVA ni ningún impuesto por cuenta de nadie.
- **Comisión de Merkamigo**: se cobra APARTE, periódicamente, contra la misma tarjeta que el negocio ya tiene guardada para su suscripción — reutiliza `Billing\Payment`/`WompiClient` existentes, cero integración de cobro nueva.
- **Tarifas de Wompi**: las paga cada negocio directamente a Wompi por su propia cuenta (recaudo). Merkamigo no las ve ni las intermedia.
- **Reporte DIAN**: investigado que existe una obligación de reporte para "operadores de plataformas digitales" (Resolución DIAN 000199/2024, modificada por 000228/2025) — es un deber de *informar*, no de pagar impuestos ajenos. No se pudo confirmar desde fuentes públicas si aplica un umbral que exima a un marketplace pequeño. **Pendiente de confirmar con el contador/abogado tributario del usuario** — mientras tanto, el sistema ya captura todo lice (NIT del negocio vía `BusinessVerification`, por venta: bruto/comisión/neto) para no tener que reconstruir nada si aplica.

## 2. Qué se construyó

### Dominio nuevo `App\Domain\Marketplace`
- **Migraciones** (reversibles): `business_wompi_credentials` (llaves cifradas a nivel de aplicación — más estricto que `wompi_settings`, que es la propia cuenta de Merkamigo con menor superficie de riesgo), `orders`, `commission_charges`.
- **Modelos**: `BusinessWompiCredential`, `Order`, `CommissionCharge`.
- **Acciones**: `ConnectBusinessWompi` (valida la llave contra la API real de Wompi antes de guardar), `CreateOrderCheckout`, `ApplyApprovedOrder`, `AccrueCommission`, `ChargeCommission`.
- **`App\Support\Wompi\BusinessWompiClient`**: clase separada de `App\Support\Wompi\WompiClient` (la de Merkamigo) A PROPÓSITO — aunque duplica ~60 líneas de fórmulas de firma, es intencional: cero riesgo de tocar ni un carácter del cliente que sostiene el cobro de suscripciones en producción, que el usuario pidió explícitamente no modificar.
- **Notificaciones**: `OrderPaid` (al negocio, dinero ya en su cuenta).
- **Policy**: `OrderPolicy` (solo el comprador o un miembro del negocio pueden ver un pedido).

### Rutas y controladores
- `OrderCheckoutController` — mismo patrón que `Billing\CheckoutController` (redirección hospedada + verificación server-side al volver), pero firmado con la llave del NEGOCIO.
- `BusinessWompiWebhookController` — **un webhook por negocio** (`webhooks/wompi/negocios/{business}`), cada uno lo pega en el panel de SU propia cuenta Wompi, verificado con el `events_secret` de ESE negocio. Probado que un webhook firmado con el secreto del negocio A es rechazado si se envía al endpoint del negocio B (aislamiento entre negocios).

### UI
- **Emprendedor**: "Cobros en línea" (conectar Wompi propio, con la URL del webhook lista para copiar) y "Ventas" (pedidos pagados + comisión pendiente + botón "Cobrar ahora").
- **Comprador**: "Mis compras" — deliberadamente separado de "Mis pedidos" (`OrderConfirmation`, constancia manual sin pago en línea, ya existía) para no confundir dos conceptos distintos.
- Botón "Comprar ahora" en la página pública del producto — solo aparece si el negocio conectó Wompi y el producto tiene precio fijo.

## 3. Alcance reducido a propósito

- **Un pedido = un producto** (sin carrito multi-ítem todavía).
- **Sin botón "Reportar" en Ventas/Mis compras** — el reembolso/disputa de una compra directa al negocio queda fuera de este alcance (Merkamigo no tiene el dinero para reembolsar, sería entre cliente y negocio).

## 4. Verificado

- 29 tests (`tests/Feature/Marketplace/`), todos en verde: conexión de Wompi (incluida validación real contra la API, cifrado de llaves y el candado de verificación de identidad), cálculo de montos/comisión, aprobación idempotente, acumulación de comisión entre negocios sin mezclarse, cobro de comisión contra tarjeta guardada (manual y en lote programado), checkout HTTP end-to-end, webhook por negocio con aislamiento probado entre negocios, autorización de "Ventas"/pedidos.
- Suite completa: **816 passed / 34 failed** (mismos 34 preexistentes de siempre). Los tests de `Billing` (los 45 de la integración de suscripciones existente) siguen exactamente igual, sin tocar.
- Verificado manualmente con Playwright (negocio + producto + credencial de prueba, creados y limpiados después): botón "Comprar ahora" en el producto público, página "Cobros en línea" mostrando la URL del webhook y el estado conectado, página "Ventas" vacía correctamente — sin errores de consola.
- Pint limpio.

## 5. Pendiente antes de producción

1. **Confirmar con contador/abogado tributario**: si Merkamigo tiene obligación de reporte DIAN como operador de plataforma, y con qué frecuencia/formato. *(Fuera del alcance de código, pendiente del usuario.)*
2. ~~Decidir si `BusinessVerification` aprobada es obligatoria antes de dejar conectar Wompi~~ — **Hecho.** `ConnectBusinessWompi::handle()` exige `$business->hasVerifiedBadge()` antes de validar/guardar las llaves; el panel "Cobros en línea" muestra un aviso con enlace a verificación en vez del formulario cuando el negocio aún no está verificado.
3. ~~Automatizar el cobro de comisión~~ — **Hecho.** `ChargeOpenCommissions` (comando `marketplace:charge-commissions`, programado semanalmente los lunes 3am en `routes/console.php`) cobra en lote las comisiones abiertas con al menos 7 días desde que se abrieron (`--all` para forzar todas sin importar la edad); una falla en un negocio (p. ej. sin tarjeta guardada) no detiene el resto del lote. El botón manual "Cobrar ahora" en "Ventas" sigue disponible para adelantarlo.
4. Explicarle a los negocios, en el propio flujo de "Cobros en línea", que las llaves son de SU cuenta Wompi (persona natural o jurídica), no de Merkamigo — el texto ya lo dice, pero vale la pena validar que quede claro para alguien no técnico.
