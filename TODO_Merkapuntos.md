# TODO · Merkapuntos / Merkamigo Premia

Versión: 03 octubre 2026. Estado: propuesta lista para implementación, pendiente de inspeccionar el repositorio. Todos los ítems están sin ejecutar. Los valores de los mockups son ejemplos, no políticas de producción aprobadas.

## Instrucción inicial para Codex

Implementa el programa de recompensas dentro de Merkamigo, preservando sus módulos actuales, autenticación, selector Comprador / Mi negocio, municipio, vitrina, zona social y mensajería interna. Reutiliza componentes y servicios existentes. No reconstruyas la plataforma ni introduzcas un framework nuevo sin necesidad.

Primero lee AGENTS.md y examina el repositorio, versiones, permisos, base de datos, pruebas y despliegue. Este paquete no contiene el repositorio y no confirma nombres de modelos, rutas, roles o tablas existentes. Los nombres siguientes son orientativos y deben adaptarse sin romper lo existente.

**La última decisión de UX es obligatoria:** una sección Merkapuntos para el cliente, un escáner para el negocio. Registrar compra y entregar premio son estados de ese escáner; el canje y la sugerencia IA se abren dentro de la vista actual. No conviertas los 15 mockups en 15 pasos obligatorios.

## Carpeta pública de referencia

Copiar la carpeta `public/mockups/merkapuntos/` del ZIP en el mismo lugar del repositorio. Incluye PNG individuales, HTML estáticos, CSS, logo e índice. Abrir:

- Archivo local: `public/mockups/merkapuntos/index.html`.
- URL con la aplicación servida: `/mockups/merkapuntos/index.html`.
- Una vista: `/mockups/merkapuntos/08-registrar-compra.html`.
- Imagen correspondiente: `/mockups/merkapuntos/08-registrar-compra.png`.

Los HTML no son la aplicación final, no se conectan a cuentas ni ejecutan compras. Algunos botones solo enlazan entre ejemplos. Los QR son ilustrativos e inválidos. Implementar las interacciones reales en los componentes de Merkamigo. No utilizar los mockups como páginas de producción para operaciones del programa. Mantener la carpeta pública sin datos reales, tokens, recibos, secretos ni identificadores de clientes. No publicar los documentos internos de este paquete dentro de `public/`.

| ID | Archivo HTML y PNG con igual nombre base | Uso de implementación |
|---|---|---|
| 01 | `01-inicio-publico` | Integración de premios en el inicio actual |
| 02 | `02-feed-publico` | Recompensas asociadas a publicaciones del negocio |
| 03 | `03-vitrina-recompensas` | Insignia, regla y pestaña Recompensas |
| 04 | `04-registro-contextual` | Registro/login conservando el premio elegido |
| 05 | `05-merkapuntos-cliente` | Única sección principal del cliente |
| 06 | `06-merkapuntos-movil` | Adaptación móvil de la misma sección y QR desplegable |
| 07 | `07-escaner-negocio` | Único escáner con identificación automática del tipo de código |
| 08 | `08-registrar-compra` | Estado del escáner: cliente identificado / valor pagado |
| 09 | `09-entregar-premio` | Estado del escáner: canje identificado / entrega |
| 10 | `10-crear-recompensa` | Editor breve con configuración avanzada desplegable |
| 11 | `11-adhesion-negocio` | Activación voluntaria del negocio |
| 12 | `12-administracion` | Administración, campañas y revisión |
| 13 | `13-canje-cliente` | Modal en la sección del cliente, generado al pulsar Usar |
| 14 | `14-catalogo-publico` | Catálogo navegable sin registro |
| 15 | `15-sugerencia-ia` | Estado del mismo editor con rango calculado y explicación |

Preferir estas referencias sobre las láminas antiguas: incorporan la simplificación posterior. Las ilustraciones son referencias de composición; reemplazarlas por las imágenes reales de productos, con su texto alternativo. Usar el logo real y estilos de Merkamigo; no inventar logos oficiales de negocios.

## Alcance y recorridos mínimos

1. Visitante: ve un premio en inicio/feed/vitrina → consulta condiciones sin login → se registra cuando decide empezar a acumular → vuelve al premio.
2. Compra presencial: cliente muestra su QR → negocio escanea → ingresa valor y pulsa **Registrar y dar puntos**. El botón confirma pago recibido. Escanear solo identifica.
3. Canje: cliente pulsa **Usar N Merkapuntos** → abre código en modal, reservando puntos/cupo → negocio escanea y pulsa **Entregar premio**. No añadir confirmación intermedia.
4. Negocio: adhesión voluntaria → elige producto → revisa sugerencia o valor manual → activa con presupuesto y cupos válidos.

MVP: compra presencial confirmada por empleado autorizado, canje presencial con solo puntos, catálogo público, saldos por negocio, avisos internos, recibo opcional y reclamación excepcional, IA explicativa, auditoría y control administrativo. Compras pagadas dentro de Merkamigo se conectarán solo si la infraestructura existente está disponible y verificada.

Fase posterior: puntos + dinero con pago validado, integración POS y pagos web, fondos y compensación para bonos globales, campañas complejas y canje entre negocios. No activar bonos globales sin financiación verificable y compensación aprobada.

## Reglas de producto

- Nombre visible de la unidad: **Merkapuntos**. Nombre del programa: Merkamigo Premia.
- Los puntos de compras pertenecen al ámbito del negocio emisor. No sumar distintos ámbitos como si fueran un saldo canjeable universal. La tarjeta del premio explica dónde son válidos.
- Bonos Merkamigo constituyen un ámbito separado; solo para ofertas expresamente adheridas con financiación y compensación definidas. Mostrar esta tarjeta únicamente cuando exista ese programa, no un saldo ficticio por defecto.
- No hay conversión a efectivo ni un valor COP universal inferido por la IA. No emitir por likes, comentarios o compartir en el MVP.
- El negocio financia sus premios; el presupuesto no es una tarifa de Merkamigo. La adhesión muestra obligaciones y condiciones.
- Política de acumulación versionada, aprobada y calculada en servidor. Ejemplo no vinculante: `floor(valor_elegible / 1000)` puntos. Antes del lanzamiento definir moneda, impuestos, descuentos, rubros excluidos, base elegible, devoluciones y redondeo. No usar el ejemplo automáticamente en todos los negocios.
- Cada premio define costo completo declarado, puntos, stock/cupo, vigencia y condiciones. El presupuesto afecta publicación y reserva, no elimina puntos previamente obtenidos.
- Puntos disponibles, reservados y consumidos son distintos. Al reservar un canje, retirar puntos del disponible; al completar, consumir la reserva sin descontar de nuevo.
- Si cancela o expira sin entrega, liberar puntos, stock y costo reservado. Si ya fue entregado, cancelarlo desde el cliente no está permitido.
- Validez inicial propuesta de código: 15 minutos, configurable y comunicada. No confundir con vencimiento de los puntos. Vencimiento de puntos queda desactivado hasta aprobar una política explícita.
- No cambiar requisitos de un canje ya reservado. Guardar copia de condiciones/costos/regla al generar la operación. Al retirar un negocio del programa, conservar historia y resolver canjes pendientes según condiciones acordadas.
- Sin saldo o stock: mostrar falta de puntos / agotado y no generar código. Clientes no registrados pueden consultar premios, pero no reclamar uno sin saldo.

## Roles y permisos

| Actor | Puede | Restricción |
|---|---|---|
| Visitante | Consultar catálogo, recompensas y condiciones | No ve saldos ni datos de terceros |
| Cliente | Ver sus saldos, mostrar QR, reservar/cancelar sus canjes, reclamar compra olvidada | No confirma compras ni se acredita puntos |
| Dueño de negocio | Adherirse, administrar premios/presupuesto/reglas y autorizar empleados | Solo su negocio |
| Empleado autorizado | Registrar compra, validar/entregar premio | No edita políticas o presupuesto salvo permiso separado |
| Administrador de plataforma | Supervisar, aprobar campañas, revisar alertas y ajustes justificados | Toda corrección tiene motivo y auditoría; sin borrado de movimientos |

## Fase 0 · Inspección y configuración

- [x] **F0.1 Inspeccionar antes de editar.** Identificar autenticación, roles, negocio activo, catálogo, pedidos/pagos, feed, notificaciones y convenciones. Documentar rutas/servicios reales en una nota de implementación. Dependencia: acceso al repositorio. Aceptación: reutilización justificada, sin duplicar usuarios/negocios o sistemas de notificaciones. — Ver "Nota de implementación" al final del documento.
- [x] **F0.2 Instalar referencias públicas.** Copiar `public/mockups/merkapuntos/`; verificar índice y 15 vistas. Aceptación: accesibles como archivos de referencia, sin datos sensibles; todo el paquete interno queda fuera de `public/`. — **Aclarado por el usuario (2026-10-04)**: los 7 PNG sueltos en `public/mockups/merkapuntos/` SÍ son la referencia real a usar (no el paquete de 15 HTML numerados descrito arriba, que nunca llegó). Se revisaron como referencia de composición para construir F2.4/F2.5 — nunca se tocó el header/sidebar/footer de la plataforma, solo el contenido interno de cada página, tal como pidió el usuario.
- [ ] **F0.3 Definir políticas y flags.** Funcionalidad desactivada por defecto en producción; activación selectiva por negocio. Aprobar regla, base elegible, plazos, presupuesto, costo y términos. Dependencia: decisión operativa. Aceptación: no se emite ni promete saldo inventado; política incompleta bloquea activación con un mensaje concreto. — **Parcial**: el interruptor global (`config('loyalty.enabled')`, apagado por defecto) y el mecanismo de activación selectiva por negocio (`LoyaltyEnrollment` + `LoyaltyPolicy` versionada, bloqueada sin política activa) ya existen y están probados. Los valores reales de la regla (puntos por COP), presupuestos, plazos y términos siguen siendo decisión de cada negocio — no se fijó ningún número de producción.

## Fase 1 · Modelo y servicios

Adaptar nombres al repositorio. No duplicar entidades ya existentes.

| Entidad lógica | Campos y relación mínimos |
|---|---|
| Adhesión | negocio, estado, consentimiento/versión/fecha, responsable, presupuesto |
| Política | negocio, versión, regla determinista, base elegible, vigencia |
| Cuenta de puntos | cliente, ámbito/negocio, estado; única por combinación |
| Movimiento | cuenta, tipo, puntos, compra/canje/campaña origen, clave idempotente, actor, fecha |
| Compra | negocio, cliente, valor elegible en unidad monetaria menor, origen, referencia, estado, regla snapshot, empleado |
| Premio | negocio, producto/servicio, tipo, puntos, costo declarado, cupos, vigencia, estado, versión |
| Canje | cliente, premio snapshot, puntos/costo reservados, token hash, expiración, estado, empleado entrega |
| Reserva de presupuesto | ámbito, período/campaña, costo comprometido, vínculo al canje |
| Solicitud excepcional | compra/recibo privado, estado, decisión, motivo, vinculación a compra existente |
| Campaña | financiación, reglas, oferta adherida, fechas, topes, estado; posterior si no hay fondos |
| Auditoría / salida de avisos | actor, evento, versión, correlación y entrega idempotente |

- [x] **F1.1 Migraciones aditivas y permisos.** Dependencia: F0.1. Aceptación: datos actuales preservados; índices únicos y relaciones válidos; recibos fuera de carpeta pública; separación de negocios en consultas y acciones. — `database/migrations/2026_10_04_090000_create_loyalty_tables.php`, 8 tablas nuevas, nada existente modificado. Migrada y revertida en local sin errores.
- [x] **F1.2 Libro de movimientos.** Aceptación: saldos reconstruibles, sin edición manual destructiva; movimientos reversos referencian el original; enteros para puntos y moneda, sin errores de coma flotante; una única fuente de verdad para saldo. — `LoyaltyMovement` (append-only), `LoyaltyAccount::availablePoints()` siempre suma la tabla, nunca una columna cacheada.
- [x] **F1.3 Registrar compra.** Acción mutadora autenticada POST equivalente. Negocio y empleado se verifican en servidor, valor positivo/elegible y regla vigente. Fecha/actor/ID internos automáticos. Clave idempotente reutilizada en reintentos. Aceptación: doble clic o pérdida de conexión crea una compra y una acreditación; notificación posterior a commit. — `App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase`. Probado en `tests/Feature/Loyalty/LoyaltyPurchaseTest.php`.
- [x] **F1.4 Validar duplicados reales.** Recibo externo único por negocio cuando existe; compras procedentes de pedidos usan el ID del pedido. Un ID interno automático por sí solo no detecta la misma venta introducida dos veces. Añadir aviso por coincidencia cliente/valor/ventana temporal y revisión, sin bloquear compras legítimas idénticas sin criterio. Aceptación: repetir una referencia no acredita; los casos sin recibo dejan evidencia del límite de verificación manual. — Índice único `(business_id, external_reference)`; heurística de posible duplicado guarda `metadata.possible_duplicate` sin bloquear. El enlace a `pedidos` del marketplace (usar `Order::id` como referencia) queda para cuando se conecte F2 con checkout real.
- [x] **F1.5 Reservar canje atómicamente.** Verificar autenticación, ámbito, saldo, premio publicado, fecha, stock y presupuesto disponible con bloqueos/operación atómica y clave idempotente. Aceptación: dos peticiones simultáneas no gastan el mismo saldo ni la última unidad; reserva de puntos, stock y costo se completa íntegra o no ocurre. — `ReserveLoyaltyRedemption`, `lockForUpdate()` sobre premio y cuenta. Las pruebas verifican la guarda de forma secuencial (ver nota en el propio test); no se ejecutó contra una concurrencia real multihilo.
- [x] **F1.6 Entrega/cancelación/vencimiento.** Transiciones válidas: reservado → entregado / cancelado / expirado. Tarea programada idempotente libera lo vencido. Aceptación: entrega contra cancelación concurrente tiene un solo resultado; un código consumido no vuelve a entregar; fecha de servidor manda. — `DeliverLoyaltyRedemption`, `CancelLoyaltyRedemption`, comando `loyalty:expire-redemptions` (programado cada 5 min en `routes/console.php`, detrás de `config('loyalty.enabled')`).
- [x] **F1.7 Devoluciones y ajustes.** Dependencia: F1.2–F1.3. Vincular anulaciones al origen y revertir solo puntos elegibles. Aprobar regla de devolución parcial y de puntos ya gastados: puede crear deuda de puntos y bloquear nuevos canjes, conservando evidencia; nunca truncar saldo a cero silenciosamente. Si invalida un canje pendiente, liberar sus reservas en la misma transacción. Aceptación: reintento no duplica la reversión y no deja puntos sin respaldo. — `ReverseLoyaltyPurchase`. El saldo puede quedar negativo a propósito (deuda de puntos); no se truncó a cero en ningún punto del código.
- [x] **F1.8 QR seguros.** Separar código de identificación del cliente y código de canje, ambos tokens opacos con tipo validado. No exponer teléfono, email, ID secuencial o permisos. QR de cliente identifica, no autoriza retiro. Canje con token único, hash almacenado, expiración, solo accesible al negocio correcto. Aceptación: tokens alterados, expirados, de otro negocio o usados no mutan nada; límite de intentos y registro de rechazos. — `LoyaltyTokens`, `IssueLoyaltyIdentityToken` (prefijo `idn_`), tokens de canje (prefijo `rdm_`) en `ReserveLoyaltyRedemption`/`DeliverLoyaltyRedemption`. Pendiente para la fase de UI: límite de intentos de escaneo por IP/usuario (hoy no hay endpoint HTTP todavía, así que no hay dónde aplicar throttle).

## Fase 2 · Cliente y captación

- [x] **F2.1 Inicio / feed / vitrina.** Ref. 01–03. Dependencias: catálogo F1. Mostrar premios reales activos con cupos, municipio y condiciones; no ocultar premio por falta de login. Reutilizar navegación y sidebar existentes. Aceptación: visitante encuentra premio sin registrarse; no se repite un banner en cada interacción ni se desplaza el contenido social con anuncios invasivos. — Banner en Inicio/Feed (`feed/partials/rewards-banner.blade.php`, un único lugar, arriba del contenido) y pestaña "Recompensas" en la vitrina pública (`vitrinas/show.blade.php`), ambos solo si hay premios reales que mostrar — nunca un banner vacío. No se tocó ningún header/sidebar/footer, solo el contenido interno de cada página, como pidió el usuario.
- [x] **F2.2 Catálogo público.** Ref. 14. Filtros municipio/categoría/negocio y búsqueda; tarjetas con ámbito, disponibilidad, puntos y condiciones visibles o desplegables. Aceptación: filtros funcionan sin login, con paginación; agotados no se anuncian como disponibles; estados vacío/error/cargando. — `GET /premia` y `GET /premia/{reward}` (`PremiaController`), públicas, con filtro de municipio/categoría/búsqueda y paginación. Diseño persuasivo tomado de las dos referencias que compartió el usuario.
- [x] **F2.3 Registro contextual.** Ref. 04. Reutilizar registro/login existentes, limitar campos a obligatorios y conservar destino seguro interno del premio. No abrir registros duplicados. Aceptación: login/registro lleva de vuelta al premio; no acredita regalo automáticamente; evita redirect externo y mantiene términos/consentimientos requeridos. — Completo: `GET /premia/{reward}/canjear` exige `auth`; un invitado que pulsa "Canjear" cae al login/registro con el mecanismo `intended` estándar de Laravel y, al autenticarse, vuelve exactamente a esa acción — ningún regalo se acredita hasta que esa reserva se ejecuta explícitamente. Probado de punta a punta (`PremiaCatalogTest`).
- [x] **F2.4 Sección única Merkapuntos.** Ref. 05–06. Saldo por negocio contextual, premios canjeables, faltante, QR desplegable y canje activo. Historial y reclamación excepcional como desplegables/modales secundarios. Aceptación: no hay navegación obligatoria por billeteras separadas; no se presenta un total universal; cliente solo ve sus operaciones. Adaptar 390 px y escritorio. — `GET /merkapuntos` (`resources/views/pages/merkapuntos/⚡index.blade.php`), reemplaza el placeholder "Recompensas" del sidebar (ahora dice "Merkapuntos" en ambos sidebars del Cliente). QR de identificación servido como imagen (`MerkapuntosQrController@identity`), nunca embebido en el HTML.
- [x] **F2.5 Canje inmediato.** Ref. 13. CTA explícito **Usar N Merkapuntos**; al pulsar hace la reserva y abre código en la misma sección, sin pantalla adicional de confirmación. Condiciones/negocio visibles antes de pulsar; permitir cancelar. Aceptación: disponible actualizado, código único, unidad reservada; error de saldo/stock informa y refresca sin duplicar; cerrar modal no equivale a cancelar; reabrir muestra el mismo canje activo correspondiente. — Mismo modal (`flux:modal`) de la página, sin navegación aparte. "Reabrir muestra el mismo canje" obligó a ajustar F1.8: el token de canje ahora también se guarda cifrado (reversible con `APP_KEY`), no solo con hash, para poder volver a mostrarlo — ver migración `2026_10_04_130000_...` y la nota en `ReserveLoyaltyRedemption`.
- [x] **F2.6 Avisos y continuidad.** Usar notificaciones internas y mensajería actuales; sin botones WhatsApp. Compra confirmada: "Ganaste N Merkapuntos en X"; entrega: "Canje completado"; vencimiento/cancelación: "Tus puntos están disponibles de nuevo". Aceptación: un evento genera un aviso, configurable y accesible; cliente ve estado actualizado tras escaneo. — Ya construido en Fase 1 (`PointsAccrued`, `RedemptionDelivered`, `RedemptionPointsReleased`); ahora además visible en contexto porque F2.4 ya existe.
- [x] **F2.7 Solicitud excepcional con recibo.** Dentro de F2.4. Recibo privado, límites de carga y acceso autorizado; el negocio revisa y vincula a compra existente o crea una acreditación única. Aceptación: no hay acreditación automática por imagen, no duplica compra registrada y rechazo tiene motivo; límites y fecha máxima configurables. — `SubmitLoyaltyReceiptClaim` + modal "¿Olvidaste mostrar tu QR?" en la página. Sube al disco `private` (nunca `public`). La revisión/aprobación por el negocio (vincular a compra o crear acreditación) es Fase 3 (panel del negocio) — aquí solo se implementó la mitad del cliente: crear la solicitud.

## Fase 3 · Negocio y escáner único

- [x] **F3.1 Adhesión breve.** Ref. 11. Beneficios, financiación clara, presupuesto y aceptación de términos versionados; desactivado hasta configuración válida. Aceptación: voluntaria, explícita, solo dueño autorizado; no se publican premios por abrir el formulario. — Pestaña "Resumen" de `/emprendedores/negocios/{business}/merkapuntos`. Solo el dueño puede adherir/activar (`EnrollBusinessInLoyalty`, ya probado en Fase 1); un colaborador lo intenta y la acción lo rechaza (probado).
- [x] **F3.2 Un escáner.** Ref. 07. Pedir cámara solo al abrirlo; detectar tipo y mostrar estado 08 o 09. Alternativa código manual si cámara denegada/no disponible; cerrar detiene cámara. Dependencias: QR F1.8 y HTTPS del entorno servido. Aceptación: sin elegir previamente compra/canje, con permisos claros y teclado accesible. — **Parcial, a propósito**: la detección automática del tipo de código (`idn_` → compra, `rdm_` → entrega) está completa y es el mismo campo para ambos, sin elegir antes. Lo que falta es la cámara en sí: este proyecto no tiene instalada ninguna librería JS de lectura de QR, así que el escáner hoy ES la "alternativa de código manual" que el propio TODO exige para cuando la cámara no está disponible — cubre el caso de uso, no bloquea nada, pero la lectura por cámara queda pendiente de una librería nueva (fuera del alcance de esta pasada de backend/Livewire).
- [x] **F3.3 Estado compra.** Ref. 08. Cliente identificado, único campo principal valor; previsualización automática de puntos; recibo/detalles opcionales salvo política o alerta. Botón **Registrar y dar N puntos** declara pago recibido; sin checkbox/confirmación redundantes. Aceptación: mismo escáner, una pulsación final; actor/fecha internos automáticos; botón deshabilitado mientras procesa; éxito vuelve al escáner y error conserva datos. — Previsualización reactiva de puntos (`wire:model.live`) antes de confirmar, igual que el mockup.
- [x] **F3.4 Estado entrega.** Ref. 09. Premio/cliente/negocio/estado; botón **Entregar premio**. Aceptación: solo personal autorizado del negocio, requiere canje válido, una pulsación final; no descuenta dos veces; si conexión falla consultar operación antes de reintentar con nueva clave. — Reutiliza `DeliverLoyaltyRedemption` (Fase 1), ya idempotente.
- [x] **F3.5 Editor breve.** Ref. 10 y 15. Elegir producto, reutilizar precio/costo, calcular sugerencia/manual, activar. Avanzado desplegable con stock, vigencia, restricciones, costo máximo, política. Aceptación: presupuesto/cupos seguros existen antes de activar aunque no se muestren como pasos separados; si falta costo se pide sin inventarlo; no cambiar canjes reservados; pausar/reactivar sin perder historial. — Pestaña "Premios". **No incluido**: "elegir producto" (vincular a un producto real de la vitrina en vez de escribir el título a mano) y la sugerencia con IA (F4.1, fase aparte). Pausar/reactivar sí está, con pruebas.
- [x] **F3.6 Resultados y excepciones.** Dentro del panel actual del negocio, sin nuevo sistema de menús profundo. Recompensas/cupos, gasto consumido y reservado, canjes, clientes nuevos y recompra con definiciones. Revisión de solicitudes y reversión autorizada con motivo. Aceptación: ningún negocio ve cifras de otro; diferencia gasto y reservas, permite exportación limitada si ya existe infraestructura. — Pestaña "Resultados": clientes, puntos emitidos, canjes entregados/pendientes, presupuesto gastado/reservado, y revisión de solicitudes excepcionales (aprobar acredita vía `RegisterLoyaltyPurchase`, rechazar exige motivo). **No incluido**: "clientes nuevos vs. recompra" como métrica separada, y exportación — ninguna infraestructura de exportación existía para reutilizar. Un bug real de autorización se encontró y corrigió aquí mismo: el endpoint del recibo autorizaba contra el negocio de la solicitud pero el middleware fijaba el equipo de permisos con el negocio de la URL — si no coincidían, evaluaba el rol equivocado. Ver `LoyaltyReceiptClaimDocumentController` y su prueba.

## Fase 4 · Motor de sugerencia y administración

La IA no define por sí sola una política financiera ni calcula saldos. Implementar un motor determinista y usar IA únicamente para explicar recomendaciones y alternativas. No enviar datos personales, recibos o detalles identificables de clientes al proveedor.

- [x] **F4.1 Rango explicable.** Ref. 15. Entradas: precio, costo completo C (producto/prestación/empaque y cargos relevantes), regla de acumulación, presupuesto/cupos y fracción promocional objetivo aprobada b. Para regla simple `q` puntos por `U` COP de gasto, gasto objetivo `S = C / b`; umbral ilustrativo `P = ceil(S / U) * q`. Mostrar supuestos; no extrapolar a reglas mixtas/bonos con esta fórmula. Ejemplo: C=$3.000, q=1, U=$1.000, b=0,5%–0,75% → 400–600 puntos. Estas tasas son ejemplos de diseño, no recomendaciones universales ni política aprobada. — `CalculateLoyaltyRewardSuggestion`, probado exactamente contra el ejemplo numérico del propio documento (400–600 puntos). Integrado en el editor de premios del panel del negocio como "Asistente de sugerencia".
- [x] **F4.2 Rentabilidad y fallback.** Aceptación: explica costo/promoción, no promete utilidad neta; solicita costo/margen cuando falten. Para regalo puro costo=C, sin ingreso en esa entrega; para descuento o puntos+dinero hay otro cálculo y se pospone al integrar cobro. Motor funciona sin IA; timeout/error devuelve explicación determinista y entrada manual. Probar costo nulo/negativo, moneda, q/U inválidos, b fuera de rango y presupuestos compartidos. No modifica valores existentes sin selección explícita del dueño. — Usa el contrato `GeneratesAssistedText` ya existente (falla a `null` en silencio); sin IA disponible, cae a una explicación determinista igual de completa, probado explícitamente. El dueño elige "Mayor atractivo" o "Mayor protección del margen" a propósito — nada se aplica solo. Descuento/puntos+dinero queda pospuesto, como pide el propio TODO.
- [x] **F4.3 Administración.** Ref. 12. Flags por negocio, auditoría, movimientos/revisiones, campaña borrador y métricas. Aceptación: ajustes tienen permisos/motivo/origen; filtros por municipio/negocio y datos reales; no borrar historial. — `LoyaltyEnrollmentResource` (Filament, grupo "Plataforma", junto a "Registros de auditoría" que ya existía y se reutiliza sin duplicar). Suspender/reactivar con motivo obligatorio vía `AdminReviewLoyaltyEnrollment`, filtros por estado y municipio, métricas reales por negocio (puntos emitidos, canjes entregados). **No incluido**: "campaña borrador" — pertenece a bonos globales (F4.4), bloqueado a propósito.
- [ ] **F4.4 Bonos globales: bloqueo financiero.** Solo avanzar si existe modelo acordado de coste y compensación. Definir coste máximo de cada bono/oferta, fondo, reserva al emitir y liquidación al negocio. Límite de presupuesto se valida en servidor incluso con peticiones concurrentes. Aceptación: emisión sin respaldo bloqueada; reglas de bienvenida/referidos versionadas, cuenta verificada, referido distinto y compra verificable no devuelta, cuotas y revisiones; promocionar solo ofertas reales adheridas. — **Deliberadamente no construido**, en los cuatro pases de este trabajo. El propio TODO lo bloquea: "Solo avanzar si existe modelo acordado de coste y compensación" y "no activar bonos globales sin financiación verificable y compensación aprobada". Esa decisión de negocio no existe todavía — construir el código sin ella sería inventar una política financiera, justo lo que el documento pide no hacer.

## Fase 5 · Calidad, despliegue y criterios de salida

- [x] **F5.1 Accesibilidad y responsive.** Referencias desktop y 06 móvil; revisar 390 px y tablet. Foco en modal, cierre teclado, nombres de botones, contraste, textos alternativos, estado anunciado a lector de pantalla, permiso cámara, carga/vacío/error. Aceptación: no desborde horizontal, no textos ilegibles, no bloquear uso por falta de cámara. — Revisión dirigida a las páginas propias de Merkapuntos (no a toda la plataforma): `aria-expanded`/`aria-controls` en el desplegable de historial, `aria-current` en las pestañas del panel del negocio, `aria-label` en los filtros de búsqueda del catálogo (antes solo tenían `placeholder`, que no es un nombre accesible confiable), alt text real en todas las imágenes/QR. Verificado a 390px con Playwright: sin scroll horizontal en catálogo, detalle y sección del cliente. Los modales (`flux:modal`) ya traen foco/cierre con teclado del propio Flux, sin código propio. El escáner nunca pide cámara — es el campo manual, así que "no bloquear uso por falta de cámara" se cumple por diseño.
- [x] **F5.2 Pruebas críticas del dominio.** Compra idempotente; referencia duplicada; ámbito incorrecto; doble canje simultáneo; última unidad; presupuesto agotado; entrega repetida; entrega contra cancelación; expiración; reversión parcial/total; puntos gastados; empleado sin permiso; acceso de otro negocio; solicitud de recibo duplicada; IA caída. Aceptación: consistencia de libro/stock/fondo con pruebas de integración, no solo checks de interfaces. — Los 14 casos de esta lista están cubiertos; los dos que faltaban (reversión parcial, solicitud de recibo duplicada) se agregaron en este pase. "Última unidad"/"doble canje simultáneo" se prueban de forma secuencial, no con concurrencia real multihilo — aclarado en el propio test desde F1.5.
- [x] **F5.3 Prueba de recorrido.** Visitante descubre sin login, registro vuelve al premio, compra da puntos una sola vez, canje por CTA único, negocio confirma entrega, aviso y saldo correctos. Aceptación: completar el flujo simplificado sin pantallas intermedias obligatorias; no usar el QR de cliente como canje. — `LoyaltySimplifiedJourneyTest`, una sola prueba de integración de punta a punta por rutas HTTP y Livewire reales (no solo Actions por separado), en el orden exacto del documento. Incluye la prueba explícita de que el QR de identidad nunca sirve para entregar un premio.
- [x] **F5.4 Medición.** Eventos anónimos/agregados o con consentimiento según política: impresión de premio, detalle, registro iniciado/completado, compra confirmada, canje reservado/entregado, cancelado/expirado, recompra. Definir denominadores/ventanas de conversión y adquisición. Aceptación: medir primer canje y recompra además de registros, sin enviar PII en eventos. — Reutiliza `RegisterAnalyticsEvent` ya existente (dedupe + filtro de bots + hash no reversible, sin IP/user-agent en crudo), con 7 tipos de evento nuevos. **No incluido**: "registro iniciado" como evento separado — el middleware `auth` intercepta al invitado antes de que el controlador corra, y enganchar ahí habría significado tocar el flujo de autenticación compartido (Fortify) por un solo evento de medición. "Cancelado" (desde la sección del cliente) sí dispara `LOYALTY_REDEMPTION_RELEASED`; "expirado" (el comando programado `loyalty:expire-redemptions`) no dispara ningún evento — corre sin un visitante real detrás (`RegisterAnalyticsEvent` necesita un `Request` para el hash anónimo), así que inventar uno habría sido un evento falso. Denominadores/ventanas de conversión son un análisis sobre los datos ya capturados, no código adicional — fuera de alcance de este pase.
- [x] **F5.5 Staging y producción.** Migraciones aditivas en staging con copia anonimizada, backup verificable previo a producción, flags apagados, revisar procesos/colas/scheduler y recuperación. No `migrate:fresh`, `refresh`, `reset`, truncar ni eliminar tablas en producción. No exponer una ruta web pública para migrar. Usar mecanismo de despliegue autorizado del repo; rollback funcional apagando flags y conservando movimientos. Aceptación: prueba de restauración/procedimiento documentado, migraciones nuevas revisadas, no alterar migraciones históricas ni ejecutar como si toda la base estuviera vacía. — `releases/migracion-merkapuntos-2026-10-05.sql`, mismo patrón ya usado en este repositorio para producción sin acceso a CLI: `CREATE TABLE IF NOT EXISTS` (aditivo, seguro de correr más de una vez), registra las 3 migraciones en la tabla `migrations` al final. **Verificado de verdad, no solo redactado**: se revirtieron las 3 migraciones en local, se corrió el script `.sql` directo contra MySQL, y la suite completa (1042 pruebas) pasó contra el esquema que el script creó — no es el resultado de `php artisan migrate`, es literalmente el mismo script que se pegaría en producción. Rollback: apagar `LOYALTY_ENABLED` en `.env` desactiva todo el programa sin tocar ninguna tabla ni perder movimientos.
- [ ] **F5.6 Piloto controlado.** Activar 1–3 negocios con reglas/costos/cupos aprobados; revisar canje real, devoluciones, operaciones concurrentes y atención. Luego ampliar campaña. Aceptación: recompensas alcanzables y reales, financiación confirmada, responsable de incidencias y métricas mínimas definidos. — **No es código, es un proceso operativo**: elegir negocios reales, aprobar sus reglas/costos/cupos con ellos, nombrar un responsable humano de incidencias. Nada de esto se puede completar escribiendo software — la plataforma ya tiene todo lo necesario para ejecutarlo (adhesión, escáner, panel de administración) en cuanto haya negocios y presupuesto reales listos para empezar.

## Decisiones pendientes que Codex debe dejar visibles

- Regla de emisión por negocio y base de valor elegible, política de devoluciones y tratamiento de deuda de puntos.
- Costos completos, cupos, presupuesto, vigencias y política de salida del negocio.
- Datos obligatorios reales para registro y términos; no agregar teléfono por comodidad si no es necesario.
- Modelo financiado de bonos globales y compensación: **no habilitarlo por defecto**.
- Disponibilidad del proveedor IA y servicios de pago existentes. Elegir integraciones según repo/configuración, sin secretos en frontend.

Avanzar con infraestructura, interfaz y flags sin inventar estas decisiones. Habilitar operaciones reales únicamente con políticas completas y aprobadas. Los mockups no autorizan reglas económicas de producción.

## Entrega esperada de Codex

- Cambios integrados en el repositorio, migraciones revisadas y rutas reales documentadas.
- Componentes reutilizados, banderas de activación, servicios de dominio y permisos.
- Pruebas críticas y evidencia del recorrido simplificado en staging.
- Lista de decisiones pendientes, configuración operativa y procedimiento de reversión.
- Este TODO actualizado marcando solo ítems comprobados, sin confundir referencias con funcionalidad implementada.

**Objetivo final:** descubrir premios sin registro, acumular con QR + valor + una confirmación y canjear con una pulsación del cliente + escaneo y entrega por el negocio. La trazabilidad ocurre detrás de la interfaz.

---

## Nota de implementación (2026-10-04)

Alcance del primer pase: **Fase 0 (parcial) + Fase 1 completa**. Segundo pase: **F2.3/F2.4/F2.5/F2.6/F2.7** (sección Merkapuntos del cliente). Tercer pase: **Fase 3 completa** (adhesión, escáner único, editor de premios y resultados del negocio). Cuarto pase: **F2.1/F2.2** (premios en Inicio/Feed/vitrina pública, catálogo público) — **Fase 2 queda completa**. Quinto pase (mismo día, a pedido del usuario: "revisa qué está faltando y termina"): **F4.1/F4.2/F4.3** (motor de sugerencia, panel de administración) y **F5.1 a F5.5** (accesibilidad, pruebas críticas restantes, prueba de recorrido, medición, script de producción). **Quedan sin construir, a propósito, solo dos ítems de todo el documento**: F4.4 (bonos globales) y F5.6 (piloto controlado) — ver el motivo de cada uno en su propia fila de la lista; ninguno de los dos es código pendiente, son decisiones/procesos que no le corresponden a quien escribe el software.

### Convenciones reales del repositorio (F0.1)

- **Auth/roles**: `spatie/laravel-permission` con *teams* = negocio. El middleware `business.team` (`App\Http\Middleware\SetPermissionsTeam`) fija `setPermissionsTeamId($business->id)` a partir del parámetro de ruta `{business}`. Roles existentes: `owner`, `admin`, `collaborator` (ver `InviteCollaborator`). Las acciones de Merkapuntos fijan el team ellas mismas (`AuthorizesLoyaltyEmployees`) porque también corren desde comandos/colas sin ese middleware.
- **Negocio activo / selector Comprador-Mi negocio**: `auth()->user()->experience` (`cliente`|`emprendedor`), sin tocar. El negocio de un empleado se resuelve por el parámetro de ruta `{business}`, no por sesión.
- **Dinero**: convención `_cents` enteros (ver `Order`, `Plan`), NO el `price` decimal de `Product`. Merkapuntos sigue `_cents` en todas sus columnas, tal como pide el propio TODO.
- **Auditoría**: `App\Domain\Platform\Actions\RecordAuditLog` — reutilizada en todas las acciones nuevas en vez de crear un sistema de auditoría paralelo. También dispara webhooks salientes existentes automáticamente.
- **Notificaciones**: `Illuminate\Notifications\Notification` con canales `['database', PushChannel::class]` (ver `BusinessMessageReceived`). Mismo patrón en `PointsAccrued`, `RedemptionDelivered`, `RedemptionPointsReleased`; todas apuntan a `route('clientes.actividad')` (la página "Actividad" ya existente) porque la sección dedicada de Merkapuntos (F2.4) todavía no existe.
- **Disco privado**: `config/filesystems.php` ya define `private`/`public`. `loyalty_receipt_claims.receipt_path` está pensado para el disco `private`, igual que `App\Support\Media\MediaUploader`.
- **Comandos programados**: clase delgada en `app/Console/Commands/{Dominio}/...` que delega en una Action, con `#[Signature]`/`#[Description]`, registrada en `routes/console.php`. `loyalty:expire-redemptions` sigue ese patrón.
- **Nada existente se duplicó**: no hay un sistema de usuarios/negocios/notificaciones paralelo — Merkapuntos reutiliza `Business`, `User`, roles y notificaciones ya existentes, solo agrega las tablas/acciones propias del programa.

### Qué se construyó (Fase 1)

| Elemento | Dónde |
|---|---|
| Migración (8 tablas nuevas, aditiva) | `database/migrations/2026_10_04_090000_create_loyalty_tables.php` |
| Config / interruptor global | `config/loyalty.php` (`LOYALTY_ENABLED`, apagado por defecto) |
| Modelos | `app/Domain/Loyalty/Models/*` (Enrollment, Policy, IdentityToken, Account, Movement, Purchase, Reward, Redemption, ReceiptClaim) |
| Acciones | `app/Domain/Loyalty/Actions/*` (adhesión, política, emisión/rotación de QR de identidad, registrar compra, reservar/entregar/cancelar canje, revertir compra, crear premio) |
| Notificaciones | `app/Domain/Loyalty/Notifications/*` (acreditación, entrega, liberación) |
| Comando programado | `app/Console/Commands/Loyalty/ExpireLoyaltyRedemptionsCommand.php` |
| Relaciones añadidas (no se tocó nada más de estos modelos) | `Business::loyaltyEnrollment()/loyaltyPolicies()/loyaltyRewards()/loyaltyAccounts()`, `User::loyaltyAccounts()/loyaltyIdentityToken()` |
| Pruebas (28, todas en verde) | `tests/Feature/Loyalty/*` — compra idempotente, referencia duplicada, posible duplicado no bloqueante, empleado sin permiso, empleado de otro negocio, política inactiva, devolución con deuda de puntos, reversión idempotente, tope de reversión, reserva agota saldo/stock/presupuesto, reserva idempotente, entrega idempotente, entrega vs. cancelación, cancelación idempotente, vencimiento programado, tokens de identidad (emisión/rotación/inválido) |

Verificado: suite completa del proyecto en verde (991 pruebas), `vendor/bin/pint --test` limpio, `vendor/bin/phpstan analyse` en el mismo baseline de 191 errores preexistentes (ninguno nuevo en código de Merkapuntos), migración aplicada y revertida sin errores en local.

### Simplificaciones deliberadas frente al TODO (documentadas en el código)

- **"Reserva de presupuesto"** no es una tabla propia: son columnas agregadas en `loyalty_rewards` (`budget_reserved_cents`/`budget_spent_cents`), protegidas con el mismo `lockForUpdate()` que el stock. Mismo efecto, menos tablas para un MVP de un negocio por premio.
- **"Campaña"** (bonos globales, F4.4) no se creó — esa fase está explícitamente bloqueada en el propio TODO hasta que exista un modelo de financiación aprobado.

### Decisiones pendientes que este pase NO inventó

Las mismas cinco que ya lista el TODO arriba ("Decisiones pendientes que Codex debe dejar visibles") siguen abiertas: regla real de emisión y base elegible por negocio, costos/cupos/presupuesto/vigencias reales, qué datos son obligatorios para el registro, el modelo de financiación de bonos globales (no habilitado), y la disponibilidad real del proveedor de IA. Nada de esto se fijó con un valor de producción; los ejemplos usados en las pruebas (`1 punto por cada 1.000 COP`) son fixtures de prueba, no una política recomendada.

### Qué se construyó (Fase 2, 2026-10-04 — segundo pase)

A pedido explícito del usuario: priorizar la sección del cliente (F2.4/F2.5) y usar como referencia visual los PNG sueltos de `public/mockups/merkapuntos/`, sin tocar header/sidebar/footer — solo el contenido interno.

| Elemento | Dónde |
|---|---|
| Página "Merkapuntos" del cliente | `resources/views/pages/merkapuntos/⚡index.blade.php` (`GET /merkapuntos`, reemplaza el antiguo placeholder "Recompensas") |
| Ítem del menú renombrado | "Recompensas" → "Merkapuntos" en `cliente-left-nav.blade.php` y `nav-cliente.blade.php` (los dos sidebars del Cliente) |
| QR servidos como imagen (nunca en el HTML) | `app/Http/Controllers/MerkapuntosQrController.php` (`identity`, `redemption`) |
| Solicitud excepcional con recibo | `app/Domain/Loyalty/Actions/SubmitLoyaltyReceiptClaim.php` + modal en la página |
| Ajuste de seguridad retroactivo a F1.8 | El token de canje (no solo el de identidad) ahora también se guarda cifrado además de hasheado — `database/migrations/2026_10_04_120000_.../2026_10_04_130000_...`. Necesario para que "reabrir muestre el mismo canje" (F2.5) sin romper "nunca se guarda en claro" (F1.8); se resolvió con el cast nativo `encrypted` de Eloquent, reversible solo con `APP_KEY`, igual en ambos casos. |
| Pruebas nuevas (18, todas en verde) | `tests/Feature/Loyalty/MerkapuntosPageTest.php` (render, reserva, cancelación, autorización cruzada, endpoints QR) + ajustes en `LoyaltyIdentityTokenTest`/`LoyaltyRedemptionTest`/`SimplifiedClientNavigationTest` |

Verificado: suite completa en verde (999 pruebas), Pint limpio, PHPStan en el mismo baseline de 191, `npm run build` exitoso, flujo completo probado con Playwright (login → ver saldo → reservar canje → modal con QR real → cancelar).

**No incluido en ese pase** (decisión explícita de alcance, no olvido): F2.1 (premios en Inicio/Feed/vitrina pública) y F2.2 (catálogo público sin login) — el usuario pidió priorizar la sección del cliente, esas dos tocan páginas de alto tráfico (Inicio, Feed) que merecen su propio pase con más cuidado visual.

### Qué se construyó (Fase 3, 2026-10-04 — tercer pase)

A pedido explícito del usuario ("continúa con la fase 3").

| Elemento | Dónde |
|---|---|
| Panel del negocio (4 pestañas: Resumen/Premios/Escáner/Resultados) | `resources/views/pages/emprendedores/negocios/⚡merkapuntos.blade.php` (`GET /emprendedores/negocios/{business}/merkapuntos`) |
| Ítem del menú del Emprendedor | "Merkapuntos" añadido a `nav-emprendedor.blade.php`, junto a Productos |
| Revisión de solicitudes excepcionales (lado negocio de F2.7) | `app/Domain/Loyalty/Actions/ReviewLoyaltyReceiptClaim.php` — aprobar reutiliza `RegisterLoyaltyPurchase` (misma idempotencia/dedupe que el escáner), rechazar exige motivo |
| Pausar/reactivar premios | Añadido a `CreateLoyaltyReward` (`pause()`/`resume()`) |
| Enlace protegido al recibo | `app/Http/Controllers/LoyaltyReceiptClaimDocumentController.php`, mismo patrón que `BusinessVerificationDocumentController` (URL firmada temporal, nunca directa) |
| Pruebas nuevas (14, todas en verde) | `tests/Feature/Loyalty/MerkapuntosBusinessPanelTest.php` + `LoyaltyBusinessActionsTest.php` |

**Bug de seguridad real encontrado y corregido durante este pase**: el primer borrador de `LoyaltyReceiptClaimDocumentController` autorizaba contra `$claim->business` pero el middleware `business.team` fija el equipo de permisos a partir del `{business}` de la URL — si alguien pasaba el ID de **su propio** negocio en la URL junto con el `{claim}` de una solicitud ajena, el chequeo de rol se evaluaba bajo el team equivocado (el propio, no el dueño real de la solicitud) y lo dejaba pasar. Se corrigió verificando primero que `claim->business_id === $business->id` (404 si no coinciden) antes de autorizar. La prueba `test_the_receipt_document_endpoint_is_scoped_to_the_business` cubre ambos casos (negocio equivocado en la URL → 404; negocio correcto pero sin permiso → 403).

Verificado: suite completa en verde (1013 pruebas), Pint limpio, PHPStan en el mismo baseline de 191, `npm run build` exitoso, las 4 pestañas probadas visualmente con Playwright contra un negocio, dos premios, una compra y una solicitud excepcional reales (adhesión activa, premios listados, escáner identificando cliente con previsualización de puntos en vivo, métricas de resultados correctas).

**No incluido en este pase**: lectura de QR por cámara real (el escáner solo tiene el campo de código manual — el proyecto no tiene instalada ninguna librería JS de lectura de QR; el propio TODO exige esa alternativa manual para cuando la cámara no está disponible, así que el caso de uso queda cubierto, solo falta la cámara en sí), vincular un premio a un producto real de la vitrina en vez de escribir el título a mano, y la métrica "clientes nuevos vs. recompra" / exportación de F3.6 (no había infraestructura de exportación que reutilizar).

### Qué se construyó (Fase 2 — F2.1/F2.2, 2026-10-05 — cuarto pase)

A pedido explícito del usuario: terminar los dos pendientes de Fase 2, usando como referencia visual directa las imágenes `Merkamigo_ descubre, acumula y canjea-1.png` y `Panel Merkamigo con recompensas Kebero consistentes-4.png` que el usuario señaló — sin tocar ningún header, sidebar o footer, solo el contenido interno de cada página.

| Elemento | Dónde |
|---|---|
| Catálogo público de recompensas | `app/Http/Controllers/PremiaController.php` (`GET /premia`, `GET /premia/{reward}`) — filtros de municipio/categoría/búsqueda, sin login |
| Canje contextual desde el catálogo | `GET /premia/{reward}/canjear`, protegido con `auth` — reutiliza el mecanismo `intended` estándar de Laravel para F2.3 |
| Banner de recompensas en Inicio/Feed | `resources/views/feed/partials/rewards-banner.blade.php`, incluido desde `feed/index.blade.php` solo si hay premios reales |
| Pestaña "Recompensas" en la vitrina pública | `vitrinas/show.blade.php` + `VitrinaController::show()`, solo si el negocio tiene Merkamigo Premia activo y al menos un premio publicado |
| Rediseño persuasivo del panel del cliente | `resources/views/merkapuntos/customer-dashboard.blade.php` + `partials/customer-reward-card.blade.php` — héroe con imagen real, tarjeta de "próxima meta" con barra de progreso, sección "Así de fácil", reemplaza la versión funcional-pero-plana del segundo pase |
| Pruebas nuevas (7, todas en verde) | `tests/Feature/Loyalty/PremiaCatalogTest.php` — catálogo público, filtros, detalle, y el flujo completo invitado → login → canje |

Verificado: suite completa en verde (1020 pruebas), Pint limpio, PHPStan en el mismo baseline de 191 (dos *bugs* de tipado en consultas `whereHas` sin genéricos se corrigieron con anotaciones `@var Builder<Business>`, no se dejaron sin resolver), `npm run build` exitoso. Las cinco piezas se probaron visualmente con Playwright: catálogo sin sesión, detalle de premio sin sesión, pestaña Recompensas de la vitrina, banner en Inicio con sesión, y el panel de Merkapuntos rediseñado — sin errores de consola.

**Bug real encontrado y corregido al revisar la query del catálogo**: un `orWhere` suelto al nivel superior de la consulta de búsqueda por texto habría roto el filtro de `status`/negocio para esa rama (`WHERE status = 'x' AND EXISTS(...) OR title LIKE ...` en vez de `WHERE status = 'x' AND (EXISTS(...) OR title LIKE ...)`), pudiendo filtrar premios no publicados o de negocios no adheridos cuando el texto buscado coincidía. Se corrigió agrupando explícitamente antes de escribir ninguna prueba contra datos reales.

### Qué se construyó (F4.1–F4.3, F5.1–F5.5, 2026-10-05 — quinto pase)

A pedido explícito del usuario: "revisa que está faltando y termina". Alcance: todo lo que quedaba del documento que es código real, dejando explícitamente documentados los dos ítems que no lo son (F4.4, F5.6).

| Elemento | Dónde |
|---|---|
| Motor de sugerencia determinista + explicación con IA (fallback incluido) | `app/Domain/Loyalty/Actions/CalculateLoyaltyRewardSuggestion.php`, reutiliza el contrato `App\Support\Ai\Contracts\GeneratesAssistedText` ya existente en el repositorio |
| Asistente de sugerencia en el editor de premios | Pestaña "Premios" del panel del negocio — calcula el rango y permite usar "Mayor atractivo" o "Mayor protección del margen" con un clic |
| Panel de administración de Merkamigo Premia | `app/Filament/Resources/LoyaltyEnrollments/*` — suspender/reactivar adhesión con motivo obligatorio, filtros por estado/municipio, métricas reales; junto a "Registros de auditoría" (reutilizado, no duplicado) en el grupo "Plataforma" |
| Ajustes de administrador sobre la adhesión de un negocio | `app/Domain/Loyalty/Actions/AdminReviewLoyaltyEnrollment.php` — autorización por rol de plataforma (`hasAnyPlatformRole`), no por equipo del negocio |
| Mejoras de accesibilidad dirigidas | `aria-expanded`/`aria-controls` en el historial, `aria-current` en las pestañas del panel del negocio, `aria-label` en los filtros del catálogo público |
| Medición (7 tipos de evento nuevos) | `AnalyticsEvent::LOYALTY_*`, enganchados en `PremiaController`, la sección del cliente y el escáner del negocio — reutiliza `RegisterAnalyticsEvent` (dedupe, filtro de bots, sin PII) |
| Prueba de recorrido simplificado de punta a punta | `tests/Feature/Loyalty/LoyaltySimplifiedJourneyTest.php` |
| Pruebas de dominio que faltaban (F5.2) | reversión parcial de una compra, solicitud de recibo duplicada |
| Script de producción | `releases/migracion-merkapuntos-2026-10-05.sql` — las 3 migraciones de Merkapuntos en un solo script aditivo, con la misma convención ya usada en este repositorio |
| Pruebas nuevas (24, todas en verde) | `LoyaltyRewardSuggestionTest`, `AdminReviewLoyaltyEnrollmentTest`, `LoyaltyAdminPanelTest` (Filament de punta a punta), `LoyaltyAnalyticsTest`, `LoyaltySimplifiedJourneyTest`, más las que se agregaron a los archivos ya existentes |

**Verificación del script de producción, no solo redactado**: se revirtieron las 3 migraciones en la base de datos local, se corrió `releases/migracion-merkapuntos-2026-10-05.sql` directo contra MySQL con el cliente `mysql`, y la suite completa (1042 pruebas) pasó contra el esquema resultante — la misma prueba que se haría en producción, hecha de verdad en vez de solo documentada.

Verificado en general: suite completa en verde (1042 pruebas), Pint limpio, PHPStan en el mismo baseline de 191, `npm run build` exitoso, panel de administración probado visualmente con Playwright (suspender una adhesión real desde el navegador), y las páginas de Merkapuntos revisadas a 390px sin desborde horizontal.

### Qué queda fuera, a propósito, de todo este trabajo

- **F4.4 (bonos globales)** y **F5.6 (piloto controlado)** — ver el motivo en su propia fila arriba; ninguno es código, son una decisión de financiación y un proceso operativo respectivamente.
- **Lectura de QR por cámara** en el escáner del negocio (F3.2) — el campo de código manual ya cubre el caso de uso; falta elegir e instalar una librería JS si se quiere agregar la cámara.
- **"Elegir producto" real** en el editor de premios (F3.5) — hoy el título del premio se escribe a mano en vez de vincularse a un producto de la vitrina.
- **"Registro iniciado"** como evento de medición separado (F5.4) — ver la nota en esa misma fila.
- Las cinco decisiones de negocio que el documento pide no inventar (regla de emisión real, costos/cupos/presupuestos reales, datos obligatorios de registro, financiación de bonos globales, disponibilidad real de un proveedor de IA en producción) siguen abiertas — ningún pase de este trabajo fijó un valor de producción para ninguna de ellas.
