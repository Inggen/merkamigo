# TODO de desarrollo · Eventos y reservas en Merkamigo

**Estado:** por implementar. **Versión:** 1.0, 1 de octubre de 2026.  
**Contexto funcional:** [Estrategia y reglas detalladas](TODO_eventos_reservas_Merkamigo.md).  
**Meta:** publicar eventos abiertos de los negocios y permitir reservas privadas de un espacio con comida, equipos, cotización y pago en línea.

> Instrucción para Codex: inspecciona primero el repositorio real de Merkamigo. Reutiliza los modelos, permisos, planes, publicaciones sociales, notificaciones y flujo de Wompi que ya existan. No sustituyas funcionalidades vigentes ni ejecutes migraciones destructivas. La distribución por plan de este documento es una **propuesta pendiente de decisión comercial**; impleméntala mediante capacidades configurables, sin afirmar que ya está disponible. revisa la carpeta `public/mockups/eventos/` para refernciar el diseño del contenido, no deber reahacer el header o footer, los sidebars deben usarse de acuerdo a cada diseño.

## Decisiones de producto para arrancar

- [ ] Confirmar si cada vitrina tiene un solo espacio reservable en MVP o varios; el esquema debe admitir varios sin rediseño.
- [ ] Confirmar si el negocio exige un plato por asistente, permite más/menos platos o define consumo mínimo.
- [ ] Confirmar la política de cancelación/reembolso, el tiempo de retención de una franja y si se cobra el valor completo o un anticipo. **Supuesto de MVP:** pago completo con confirmación automática tras pago verificado.
- [ ] Confirmar si equipos adicionales tienen precio fijo, inventario y tiempo de montaje.
- [ ] Aprobar distribución propuesta: agenda y publicación en todos los planes; reserva, cotización y Wompi en Emprendedor y Negocios; prioridad global Negocios > Emprendedor > Básico con relevancia y rotación.
- [ ] Definir vigencia, impuestos y reglas de facturación de los servicios antes de abrir pagos reales.

## Fase 0 · Reconocimiento y protección de lo existente

- [ ] Localizar modelos/controladores/vistas/API de vitrinas, productos, planes, feed, roles, métricas, notificaciones y Wompi propio. Anotar rutas y tablas que se reutilizarán.
- [ ] Identificar si ya existe un módulo `events` o entidades sociales de eventos; extenderlas con migraciones incrementales. Evitar un segundo concepto incompatible de evento.
- [ ] Revisar autorización por vitrina y colaboradores; documentar permisos actuales.
- [ ] Revisar el flujo real de Wompi: credenciales por negocio, firma de transacciones, webhooks, conciliación, estados y notificaciones. No almacenar tarjetas ni claves en texto plano.
- [ ] Preparar plan de migración y reversión, respaldo de producción, banderas de función y ambiente de pruebas de pago.

**Aceptación:** un mapa corto de componentes existentes y migraciones propuestas permite revisar impactos antes de codificar.

## Fase 1 · Datos, permisos y capacidades

- [ ] Crear o ampliar configuración `event_settings` por vitrina: habilitado, eventos públicos, reservas privadas, zona horaria, capacidad, anticipación, duración máxima de tres horas, modalidad de cobro y política.
- [ ] Modelar espacio/sala, disponibilidad semanal, fechas bloqueadas y tiempo de preparación entre reservas. Empezar con un espacio visible si así se aprueba.
- [ ] Modelar platos elegibles para eventos con precio, disponibilidad y referencia al producto de la vitrina cuando exista.
- [ ] Crear catálogo global de equipos con **sonido, micrófono y televisor** como opciones iniciales; permitir que el administrador agregue más tipos. Cada vitrina selecciona varios y puede crear equipos propios sin modificar el catálogo global.
- [ ] Definir disponibilidad, cantidad y cargo opcional por equipo. La opción `Otro` requiere descripción y tratamiento explícito del precio.
- [ ] Modelar `public_events`, `event_reservations`, renglones de platos/equipos, instantánea de precios y `payment_attempts` con referencia única.
- [ ] Añadir capacidades por plan y permisos por vitrina para gestión, publicación, reserva y cobro. Proteger todas las rutas del servidor; ocultar botones en UI no basta.
- [ ] Crear índices por vitrina, municipio, fecha, estado y franja; definir unicidad/idempotencia donde aplique.

**Aceptación:** una vitrina no puede leer ni mutar la configuración, reservas o pagos de otra; los cambios de tarifas no alteran reservas ya cotizadas o pagadas.

## Fase 2 · Panel de configuración del negocio

- [ ] Añadir sección **Eventos** al panel del vendedor: activar/desactivar; pestañas `Agenda`, `Reservas`, `Configuración`.
- [ ] Formulario para modalidad **por platos**, **por horas** o **híbrida**. Solicitar tarifas pertinentes a la modalidad y validar precios no negativos.
- [ ] CRUD de horarios, bloqueos, capacidad y espacios. Mostrar calendario de ocupación con reservas privadas visibles solo para miembros autorizados.
- [ ] CRUD de platos para eventos y selector múltiple de equipos globales/propios; indicar incluidos vs cobrables.
- [ ] Mostrar estado de conexión de Wompi propio y bloquear activación de reservas con pago si faltan credenciales verificadas.
- [ ] Configurar textos visibles al cliente: condiciones, cancelaciones e instrucciones; vista previa del flujo público.
- [ ] Al desactivar nuevas reservas, mantener histórico y permitir cumplir reservas ya confirmadas.

**Aceptación:** un dueño configura Kebero con máximo tres horas, platos de prueba, sonido/micrófono/televisor y cualquiera de las tres tarifas sin tocar código.

## Fase 3 · Disponibilidad, cotizador y reserva privada

- [ ] Añadir CTA `Reserva tu evento` en vitrina solo si la función está activa y el plan la permite.
- [ ] Paso 1: fecha, hora y duración de 1 a 3 horas; bloquear horarios no disponibles, vencidos, fuera de operación o que se solapen.
- [ ] Paso 2: número de personas y cantidades por plato. Caso de referencia: **2 crepes + 3 sándwiches + 1 estroganoff = 6 platos para 6 pax**. Validar contra capacidad y regla de consumo configurada.
- [ ] Paso 3: selección de varios equipos disponibles; verificar inventario y cargos.
- [ ] Paso 4: resumen con fecha, horas, personas, platos, equipos, subtotales y total. Calcular en servidor, en COP, usando una instantánea de tarifas; nunca confiar en montos del cliente.
- [ ] Fórmula: `platos = Σ(cantidad × precio)`; `lugar = duración × precio/hora`; `equipos = Σ(cantidad × cargo)`; total usa platos, lugar o ambos según modalidad, más equipos y cargos válidos que se muestren antes del pago.
- [ ] Capturar datos mínimos del prospecto y aceptación de condiciones; generar reserva `pendiente_pago` y retener la franja por un plazo configurado.
- [ ] Implementar bloqueo transaccional de franja/capacidad y revalidar disponibilidad antes de crear el intento de pago para impedir doble reserva concurrente.
- [ ] Manejar errores de red, franja agotada, cambio de tarifa y abandono sin perder datos innecesariamente.

**Aceptación:** el mismo ejemplo de seis personas calcula correctamente las tres modalidades; duración superior a tres horas es rechazada; dos prospectos no pueden confirmar el último cupo simultáneamente.

## Fase 4 · Wompi, confirmación y operaciones

- [ ] Reutilizar la integración de **Wompi propio de la vitrina**; crear una referencia de pago única asociada a la reserva y al importe calculado.
- [ ] Validar en servidor el evento/webhook de pago, el importe, la moneda, la referencia y el estado. Hacer el procesamiento idempotente y registrar auditoría.
- [ ] Transiciones: `pendiente_pago` → `confirmada` solo por pago verificado; `pago_fallido` o `vencida` libera la retención. Definir tratamiento de pagos tardíos sin confirmar una franja ya asignada.
- [ ] Enviar confirmación al prospecto y aviso interno al negocio; incluir fecha/hora, asistentes, platos, equipos, total y referencia. Evitar exponer datos personales en el feed.
- [ ] Listado y detalle de reservas para el negocio con filtros por estado/fecha; cancelación y reembolso según política aprobada.
- [ ] Registrar reintentos y fallos de webhook para conciliación manual, sin duplicar reservas ni cargos.

**Aceptación:** éxito, rechazo, webhook repetido, webhook tardío y cancelación quedan en estados coherentes; un pago duplicado no duplica confirmaciones.

## Fase 5 · Eventos públicos, vitrina y feed

- [ ] Formulario de evento público: título, descripción, imagen, inicio/fin, municipio, ubicación, capacidad opcional, estado y vínculo a vitrina.
- [ ] Permitir marcar si el evento público bloquea un espacio y qué franja ocupa; una publicación informativa no bloquea disponibilidad por defecto.
- [ ] Calendario/lista de próximos eventos en la vitrina, con detalle accesible y enlace compartible. No mostrar reservas privadas.
- [ ] Acción `Publicar en el feed` desde un evento publicado: crear post vinculado con enlace a la ficha; impedir posts duplicados y definir qué ocurre al editar/cancelar el evento.
- [ ] Estados `borrador`, `publicado`, `finalizado` y `cancelado`; un cancelado no aparece como próximo ni acepta nuevas acciones.

**Aceptación:** un evento abierto de Kebero se ve en su vitrina y un solo post del feed lleva a la misma ficha; una reserva privada no se vuelve pública.

## Fase 6 · Agenda social global y visibilidad comercial

- [ ] Añadir **Eventos** al sidebar izquierdo de la zona social, con vista pública responsive y filtros por municipio, fecha y categoría.
- [ ] Indexar solo eventos publicados y futuros, con datos suficientes para búsqueda, orden y paginación.
- [ ] Ordenar primero por coincidencia de ubicación/fecha/interés; luego aplicar impulso de visibilidad Negocios, Emprendedor, Básico. Añadir rotación, diversidad de vitrinas y límite de repetición para que Básico reciba exposición real.
- [ ] Si hay espacios patrocinados/destacados, etiquetarlos de forma clara y mantenerlos separados de la relevancia orgánica.
- [ ] Medir impresiones, aperturas, clics y reservas atribuibles por vitrina y plan. Crear en el panel una explicación de visibilidad y CTA de upgrade basada en beneficios reales.
- [ ] Probar agenda vacía, municipios con pocos eventos, varios negocios del mismo plan y negocios Básico entre resultados relevantes.

**Aceptación:** Negocios recibe un impulso comprobable entre eventos igual de relevantes; Básico no desaparece de la agenda; un evento de otra zona no desplaza sistemáticamente a uno cercano por el plan.

## Fase 7 · Pruebas, seguridad y salida

- [ ] Pruebas unitarias de modalidades de precio, 3 horas, pax/platos, inventario de equipos, fechas y zona horaria.
- [ ] Pruebas de integración de permisos, concurrencia, expiración, Wompi en sandbox, webhooks repetidos y cambios de plan.
- [ ] Pruebas end-to-end: crear evento público → publicar en vitrina/feed/agenda; configurar Kebero → reservar para seis → pagar → confirmar → ver en panel.
- [ ] Revisar móvil, teclado, accesibilidad, mensajes de error, carga y estados vacíos. Proteger datos personales, limitar intentos abusivos y auditar cambios sensibles.
- [ ] Activar por bandera en un piloto con Kebero; observar errores, reservas abandonadas, pagos y métricas. Desplegar al resto tras validar resultados, con rollback y respaldo.
- [ ] Actualizar ayuda de vendedor/prospecto y la tabla comercial **solo cuando esté operativo**; documentar restricciones y condiciones del plan.

## Orden recomendado de entregas

1. **MVP reservas:** Fases 0–4 para Kebero, sin agenda global. Resultado: configuración, cotización y reserva pagada segura.
2. **Eventos abiertos:** Fase 5 para calendario de vitrina y publicación al feed.
3. **Descubrimiento y monetización:** Fase 6 con agenda global, prioridad por plan y métricas; luego fase 7 para ampliar el despliegue.

**Fuera del MVP:** boletería individual, asientos numerados, transmisión en vivo, pagos repartidos entre varios negocios, sincronización bidireccional con calendarios externos y cobro automático de horas adicionales.
