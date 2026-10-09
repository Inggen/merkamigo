# TODO_VENTAS_RENTABILIDAD.md — Merkamigo
**Objetivo:** generar primeras ventas reales, cobrar correctamente, medir rentabilidad y validar recurrencia sin construir funciones ajenas al circuito comercial.
**Estado:** propuesta de ejecución para Codex, NO autorización de despliegue ni de cambios en producción.
**Prioridad:** P0 (bloqueos) → P1 (ventas piloto) → P2 (escala).
**Rama de trabajo sugerida:** `codex/todo-ventas-rentabilidad`.

## 0. REGLAS OBLIGATORIAS
- [ ] Leer `README.md`, `.github/skills/desarrollo-aplicaciones/SKILL.md`, `.github/skills/revision-seguridad/SKILL.md` y `.github/skills/diseno-uiux/SKILL.md`.
- [ ] Auditar el código vigente antes de modificar: los TODO antiguos y README pueden estar desactualizados. Crear `docs/auditoria-ventas-rentabilidad.md` con rutas/clases, estados observados y evidencia.
- [ ] **No ejecutar** migraciones, seeders, comandos de Artisan ni cobros en producción. No tocar datos reales. No generar pagos reales ni activar promociones a clientes sin autorización humana.
- [ ] No cambiar unilateralmente precios comerciales ($0, $49.900/mes, $99.000/mes), tasa actual del 5%, período de prueba, distribución de beneficios por plan, condiciones legales o comisiones. Proponer opciones, agregar pruebas y esperar decisión para activar cambios comerciales.
- [ ] Conservar planes Básico/Emprendedor/Negocios, entitlements, histórico, compras, suscripciones y Wompi del negocio vs. Wompi Merkamigo. Evitar sistemas de pagos paralelos.
- [ ] Migraciones aditivas, reversibles, con respaldo/restauración ensayados antes de pasar a producción. Usar feature flags para nuevas vías de checkout y cobrar solo por backend/webhook verificado, nunca por redirect.
- [ ] No abrir acceso sin controles de abuso/antifraude. No almacenar tarjeta/CVV, credenciales o tokens en logs. Cumplir tratamiento de datos personales y reglas de comercio electrónico.
- [ ] Si hay diferencias entre este TODO y el código actual, documentarlas antes de implementar; no asumir bugs sin reproducirlos.

## P0 — PRIMERO: cerrar el circuito de dinero (bloqueante)
### P0.1 Auditoría y trazabilidad del flujo transaccional
Referencias actuales: `routes/web.php`, `app/Http/Controllers/Marketplace/OrderCheckoutController.php`, `app/Domain/Marketplace/Actions/CreateOrderCheckout.php`, `ApplyApprovedOrder.php`, `BusinessWompiWebhookController.php`, `app/Domain/Marketplace/Actions/AccrueCommission.php`, `ChargeCommission.php`, `ChargeOpenCommissions.php`, `config/services.php`.
- [ ] Diagramar: visita → producto → checkout → Wompi negocio → webhook → pedido pagado → comisión acumulada → cobro comisión → conciliación.
- [ ] Distinguir estados: `order.pendiente/pagado/rechazado/cancelado`, `payment`, `commission_charge.abierta/pendiente_cobro/pagada/fallida`. Revisar estados sin resolución, idempotencia y reintentos.
- [ ] Auditar inventario, cancelaciones y reembolsos; no generar comisiones definitivas sobre operaciones reembolsadas o duplicadas. Definir reversos proporcionales y evitar doble cobro.
- [ ] Confirmar eventos y pagos reales únicamente mediante webhooks firmados o validación verificable en API del proveedor; probar webhooks duplicados, desordenados, tardíos y montos/monedas incorrectos.
- [ ] Agregar/validar panel interno de conciliación: GMV pagado, comisiones devengadas, cobradas, fallidas, pendientes, devoluciones, pagos sin conciliar. Sin exponer PII.
- **Aceptación:** cada venta aprobada es única, el vendedor recibe en su cuenta, Merkamigo registra la comisión exacta, cobro o error explícito y auditoría recuperable.

### P0.2 Checkout de productos sin registro obligatorio (propuesta a implementar detrás de bandera)
Referencias: `routes/web.php` (ruta `productos/{product}/comprar` dentro de `auth`), `resources/views/vitrinas/product.blade.php`, `app/Domain/Marketplace/Models/Order.php` y migraciones de `orders` (comprador obligatorio actualmente).
- [ ] Diseñar checkout invitado mínimo, activado por feature flag y con degradación al login actual.
- [ ] Invitado: seleccionar cantidad/variante → nombre, email y teléfono cuando corresponda → pago Wompi → confirmación/consulta segura; no pedir registro para pagar.
- [ ] Considerar diferencias entre productos digitales, suscripciones, entregas físicas y productos de servicio; no habilitar guest para casos que requieran cuenta sin resolver control de acceso/entrega.
- [ ] Añadir asociación opcional posterior a cuenta (consentida, segura, sin secuestro de pedidos); no asignar puntos por compra no confirmada.
- [ ] Proteger confirmación/seguimiento mediante token aleatorio o URL firmada; no exponer pedidos con IDs consecutivos. Rate limits, CSRF cuando aplique, validaciones por backend.
- [ ] Resolver `buyer_user_id` sin romper ventas existentes: migración aditiva/nullable solo si las dependencias y policies quedan cubiertas; actualizar notificaciones, reportes, analytics, comisiones y entitlements.
- [ ] Precios/stock/variantes recalculados en servidor. Revisar condiciones `desde` frente a precio de cobro real; no cobrar una cotización como si fuera precio fijo.
- [ ] Funnel medible: product_view → buy_click → checkout_started → payment_approved/failed → order_confirmed; conservar atribución UTM y promoción cuando exista, sin duplicar eventos.
- **Aceptación:** un usuario nuevo puede comprar producto físico elegible sin crear cuenta; comprador registrado sigue funcionando; no se filtran datos privados ni se duplican pedidos o cobros.

### P0.3 Cobro confiable de la comisión (5% actual) sin forzar renovaciones
Referencias: `CreateOrderCheckout.php`, `AccrueCommission.php`, `ChargeCommission.php`, `SaveBusinessPaymentSource.php`, `ProcessSubscriptionRenewals.php`.
- [ ] Documentar el estado actual: compra entra a Wompi del negocio; 5% se acumula; `ChargeCommission` requiere `wompi_payment_source_id` de Merkamigo; el flujo existente de guardar tarjeta activa renovación automática según estado de la fuente.
- [ ] **No activar** cambios al método de recaudo/comisión sin aprobación comercial/legal. Diseñar aceptación explícita y separada de (a) cobro de comisión, (b) renovación automática del plan.
- [ ] Determinar qué negocios pueden vender sin método cobrable de comisión; proponer alta de método obligatorio o mecanismo alternativo legal/operativamente viable. Mostrar estado y explicación antes de habilitar pago.
- [ ] Reconciliar comisiones con reembolsos, contracargos, tarifa, mínimos de cobro si el proveedor los exige, comisiones fallidas y reintentos; definir notificaciones y suspensión proporcionada.
- [ ] Verificar que cobranza semanal no reintenta silenciosamente cargos fallidos ni marca exitoso un cobro pendiente; probar concurrencia e idempotencia.
- **Aceptación:** no existen ventas con comisión imposible de recaudar sin alerta/acción y consentimiento correspondiente; no se activa autorrenovación sin consentimiento separado.

### P0.4 Corregir condiciones comerciales inconsistentes y costo IA
Referencias: `database/seeders/PlanSeeder.php`, `BillingProductSeeder.php`, `SubscribeToPlan.php`, `ApplyApprovedPayment.php`, `ApplyBillingProductPurchase.php`, páginas de planes/checkout.
- [ ] Reproducir conflicto: `trial_days=14` para Emprendedor/Negocios vs. compra por Wompi que activa período pagado. Preparar tabla de decisión: prueba real sin cobro, compra inmediata sin promesa de trial, o prueba opcional; **no elegir unilateralmente**.
- [ ] Alinear frontend, precio total, periodicidad, renovación, cancelación, impuestos, términos y backend tras aprobación de negocio. No ofrecer «14 días gratis» si el flujo cobra de inmediato.
- [ ] Revisar `asistente-ia`: `expires_in_days=null` en producto de $49.900 = entitlement sin caducidad; proponer vigencia mensual o créditos, topes de consumo y costos, con migración compatible para compradores existentes y respeto a condiciones aceptadas.
- [ ] Prevenir recompra redundante del asistente si `canUseAiChatbot()` por plan Negocios o entitlement activo. Revisar otros beneficios destacados ya incluidos.
- [ ] Validar desacople: plan SaaS distinto de servicio humano. Los TODO `TODO_Merkamigo_Planes_Servicios_v2.md` y `TODO_Merkamigo_Servicios_para_Crecer.md` son planes pendientes, no implementaciones comprobadas.
- **Aceptación:** ninguna oferta promete prueba incompatible con pago, IA tiene economía/unidades claras y no hay doble venta accidental de beneficios incluidos.

### P0.5 Seguridad y preparación de producción
- [ ] **URGENTE:** revisar los archivos rastreados `auto.key` y `auto.crt` en la raíz del repositorio; determinar si son credenciales privadas auténticas. Si lo son, revocar/rotar certificado y secretos afectados, dejar de versionarlos y revisar historial/CI. No imprimir ni compartir su contenido. No asumir que son reales sin inspección segura.
- [ ] Revisar `/migrar`, `/limpiar-cache`, `/link` con GET autenticados en `routes/web.php`: proponer migración a comandos CLI/operación segura; proteger contra CSRF, navegación accidental y acciones destructivas. No cambiar rutas en producción sin plan de despliegue.
- [ ] Validar Wompi sandbox/producción, claves por comercio, firmas por negocio, validación de llaves, webhooks, backups y alertas de error.
- [ ] Ejecutar pruebas CI en entorno aislado con datos ficticios: `php artisan test`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, `npm run build` (o documentar imposibilidad).
- **Aceptación:** checklist de seguridad aprobado, llaves seguras, pruebas críticas y runbook de reversión, sin exposición de PII.

## P1 — Venta piloto durante 30 días (después de P0)
### P1.1 Oferta comercial simple
- [ ] Mantener Básico $0, Emprendedor $49.900/mes y Negocios $99.000/mes sujetos a confirmación de catálogo vigente en producción.
- [ ] Facilitar checkout y activar upsell contextual, nunca agresivo: de 5 productos a 20; Wompi, Copiloto y destacados con Emprendedor; IA/Live/métricas con Negocios.
- [ ] Comercializar con sistema vigente: Vitrina asistida $49.900, Kit Arranca Bonito $99.900, destacado 7/14/30 días $9.900/$16.900/$29.900; **confirmar costo operativo y vigencias**. No prometer resultados garantizados ni inventar «más elegido».
- [ ] Diseñar recorrido visual desde `planes-y-precios` a un negocio y checkout con 2–3 acciones máximo; reutilizar `BillingProduct`, checkout Merkamigo y `ApplyBillingProductPurchase`. Mensajes claros de servicio manual vs. activación digital.
- [ ] Definir beneficios de prueba sin descuentos ilimitados ni cuotas operativas que destruyan margen.
- **Aceptación:** oferta y facturación claras, checkout validado para cada artículo y órdenes humanas rastreables.

### P1.2 Conseguir 20–30 comercios transaccionables, empezando por Cajicá y Zipaquirá
- [ ] Pipeline comercial: prospecto → contacto → demo → vitrina publicada → 5 productos con foto/precio → Wompi conectado/verificado → primera compra → segunda compra.
- [ ] Filtrar negocios con oferta comprable, inventario/entrega y respuesta en WhatsApp (comida, café, regalos, belleza, mascotas y servicios bien parametrizados). Priorizar comercios con capacidad de cumplir pedidos.
- [ ] Usar Kebero como candidato de caso piloto solo si el responsable aprueba y ofrece una promoción real, fecha/precio confirmados y cuentas Wompi operativas.
- [ ] Medir adquisición, verificación, conexión de pagos, publicaciones, compra y cumplimiento por negocio; registro gratuito no equivale a negocio activo.
- [ ] Material de demo comercial de 15 minutos: producto real → compartir/QR → checkout real verificado → estadística básica → oferta de plan apropiada.
- **Objetivo de validación, no promesa:** 20–30 comercios vendibles y primeras 100 compras confirmadas.

### P1.3 Captación orientada a productos
- [ ] Priorizar URLs de producto/negocio, SEO estructurado, sitemap, `GoogleMerchantFeedController`, sincronización Merchant condicionada a credenciales y cumplimiento del proveedor.
- [ ] Campañas locales de productos/combos con enlace directo, UTMs y presupuesto explícito: grupos autorizados, reels, QR en negocio, grupos locales y alianzas; evitar spam o envío masivo no consentido.
- [ ] Producto y promoción deben mostrar municipio, precio final/desde honesto, disponibilidad y método de entrega claramente.
- [ ] Probar atribución `organic/search/social/QR/direct/paid` de visitantes a checkout/pedido pagado.
- **Aceptación:** reporte semanal con visitas, CTR compra, abandonos, ventas, comisión cobrada y CAC estimado por canal.

### P1.4 Dashboard de rentabilidad real
- [ ] Definir eventos y reportes: vitrinas activas, comercios con Wompi, pedidos pagados, GMV, take rate efectivo, comisión devengada/cobrada/pendiente, MRR de planes activos, altas/bajas, ingresos puntuales, margen de servicios y costo variable IA/pasarela.
- [ ] Separar **GMV ≠ ingresos Merkamigo**. MRR solo suscripciones recurrentes activas, no pagos únicos, ni comisión, ni ventas del vendedor.
- [ ] Mostrar cohortes semanal/mensual, tasas: visita→producto→checkout→pago; negocio registrado→publicado→primera venta; pago→recompra.
- [ ] Costos: infraestructura, Wompi asociado a cobranza Merkamigo, IA, pauta, fotografías/horas, soporte, devoluciones y tributos aplicables.
- [ ] Medir CAC, ARPA, churn, ingreso neto, margen de contribución y break-even antes de aumentar pauta.
- **Aceptación:** vista gerencial coherente con pagos, ventas y conciliación, con datos reales/sandbox claramente separados.

## P2 — Solo tras validar primeras ventas y retención
- [ ] Merkapuntos sobre compras pagadas verificadas; presupuesto/fondeo del comercio, devoluciones de puntos al reembolsar, códigos QR de canje y economía sostenible.
- [ ] Eventos: un piloto real de cupos/reservas/entrada/QR con Wompi negocio y política de cancelación, priorizando lo ya construido.
- [ ] Suscripción del comprador a productos recurrentes únicamente tras pruebas de cobro, cancelación, reembolso y soporte.
- [ ] Live Shopping: desplegar streaming y medir costo por sesión solo si el funnel de compra normal ya convierte; resolver checkout/entrega de Live antes de escalar multistream.
- [ ] Posibles comisiones diferenciadas según plan o venta en Básico: SOLO después de analizar conversión, margen, contrato, reglas de la pasarela y aceptación comercial. No modificar tasa 5% por adelantado.
- [ ] Servicios de contenido mensual Esencial/Impulsa: modelar costos humanos, alcance, recurrencia y órdenes por separado de planes, siguiendo TODO comerciales existentes; no asumir que están implementados.

## Plan de entrega para Codex (PR pequeños, orden estricto)
1. **PR 1 — Auditoría documental y matriz de riesgos:** sin migraciones ni cambios de negocio. Evidencia del código vigente, flujos, pruebas faltantes, políticas de prueba y costos IA.
2. **PR 2 — Seguridad/conciliación de cobros:** solo ajustes no destructivos aprobados; pruebas de webhook, comisión, reembolsos, pagos fallidos y protección credenciales.
3. **PR 3 — Checkout invitado detrás de bandera:** modificaciones aditivas, tests y experiencia móvil; conmutador por negocio/entorno.
4. **PR 4 — Claridad comercial y upsell:** tras confirmar decisiones P0.4; no cambiar precios ni trials sin firma comercial.
5. **PR 5 — Dashboard funnel y rentabilidad + instrumentación:** métricas verificables y sin doble conteo.
6. **PR 6 — Piloto de 20–30 comercios:** guías operativas, guiones, tracking de campañas y checklist de onboarding; activación manual autorizada.

## Validaciones transversales
- [ ] Cliente registrado e invitado (cuando flag activo) compran sin doble orden ni login forzoso para producto elegible.
- [ ] Webhooks repetidos, pendientes, fallidos, contracargos y reembolsos no duplican pagos ni comisión.
- [ ] Un negocio jamás puede acceder a pedidos, llaves ni métricas de otro.
- [ ] Verificación de identidad requerida antes de conectar Wompi negocio; no relajarla accidentalmente.
- [ ] Ninguna acción de pago se completa desde parámetros del navegador/redirect.
- [ ] Renovación voluntaria de plan independiente de consentimiento para cobrar comisión.
- [ ] Facturación de IA con vigencia/costo controlado y sin doble cobro cuando está incluida.
- [ ] Responsive y accesibilidad en producto/checkout/confirmación.
- [ ] CI y rollback documentados; sin modificaciones directas en producción.

## Decisiones que Codex debe escalar antes de codificar cambios comerciales
1. ¿14 días de trial real, plan pago desde el día uno o elegir entre ambas modalidades?
2. ¿El plan Básico permanece sin checkout como ahora? (por defecto **sí**; no alterar).
3. ¿Cómo garantizamos la comisión del 5% cuando el negocio aún no tiene método de cobro válido?
4. ¿Asistente IA vigente por 30 días, por créditos o con uso limitado? ¿Qué pasa con compras históricas?
5. ¿Qué productos admiten compra como invitado y cuáles requieren entrega autenticada?
6. ¿Quién asume costos de pasarela, devoluciones, soporte y reclamos?

## Métricas meta (hipótesis de validación, NO pronóstico)
- 20–30 negocios capaces de vender, 100 pedidos realmente pagados, 15 conversiones a plan pago, 10 servicios puntuales/destacados.
- Reportar GMV y dinero realmente recaudado por Merkamigo por separado.
- Escenario ilustrativo de ingreso BRUTO, no utilidad: 12 × $49.900 + 3 × $99.000 + 5 × $99.900 + 10 × $16.900 + $6.000.000 × 5% = **$1.864.300 COP**; MRR SaaS = **$895.800 COP**. Depende de que todo se venda y de cobrar efectivamente comisiones; no incluye costos ni impuestos.

## Definición de terminado
Cada PR trae: archivos tocados, diagnóstico inicial, decisiones aprobadas, pruebas ejecutadas y resultados, impacto en producción, plan de rollback, evidencias UX, riesgos pendientes y guía de activación. **No marcar P0 cerrado solo por existir clases o rutas. Debe estar probado de extremo a extremo en sandbox y luego aprobado para piloto.**
