# TODO_social.md — Merkamigo Social Commerce

> Objetivo: evolucionar Merkamigo hacia una red social de comercio local sin perder ni reemplazar lo ya construido.
> Regla principal: **todo lo existente debe conservarse y reutilizarse**. La nueva versión agrega una capa social y transaccional sobre vitrinas, productos, servicios, municipios, categorías, QR, WhatsApp, reseñas y demás funcionalidades actuales.

---

## 0. Reglas de implementación
> Tener como referencia grafica la imagen de la carpeta public/images/feed.png

- [ ] **NO eliminar funcionalidades existentes.**
- [ ] **NO duplicar entidades si ya existen** productos, servicios, vitrinas, municipios, categorías, usuarios, reseñas, etc.
- [ ] Antes de modificar cualquier módulo, inspeccionar:
  - [ ] Modelos actuales.
  - [ ] Migraciones.
  - [ ] Rutas.
  - [ ] Controladores / servicios.
  - [ ] Componentes frontend.
  - [ ] Roles y permisos.
  - [ ] Integraciones actuales.
- [ ] Mantener compatibilidad hacia atrás.
- [ ] Toda migración nueva debe ser reversible.
- [ ] Implementar nuevas funcionalidades de forma modular.
- [ ] Evitar reescribir el proyecto completo.
- [ ] Mantener la identidad visual actual de Merkamigo.
- [ ] Usar el rojo corporativo oficial en botones/acciones principales.
- [ ] Mantener interfaz limpia, profesional, simple y mobile-first.
- [ ] No convertir Merkamigo en una copia visual de Instagram/TikTok.
- [ ] La prioridad de Merkamigo debe seguir siendo:
  - [ ] comercio local,
  - [ ] cercanía,
  - [ ] comunidad,
  - [ ] confianza,
  - [ ] facilidad para vender.

---

# FASE 1 — Base de navegación y experiencia unificada

## 1.1 Cuenta única: Comprador + Mi negocio

### Objetivo
Eliminar la sensación de que el usuario “pierde” el ambiente de comprador cuando crea una vitrina.

- [x] Reemplazar la lógica visible de “rol” por una experiencia de **modo de uso**.
- [x] Mostrar un selector pequeño en sidebar:

```text
Ver cómo:
[ Comprador ] [ Mi negocio ]
```

- [x] No mostrar textos como “Mi rol en Merkamigo”.
- [x] Mantener el selector discreto y siempre accesible.
- [x] El usuario debe poder cambiar de modo sin cerrar sesión.
- [ ] En modo Comprador conservar acceso a:
  - [ ] Inicio.
  - [ ] Explorar.
  - [ ] Cerca de mí.
  - [ ] Categorías.
  - [ ] Solicitudes.
  - [ ] Compras.
  - [ ] Suscripciones.
  - [ ] Favoritos.
  - [ ] Guardados.
  - [ ] Mensajes.
  - [ ] Notificaciones.
- [ ] En modo Mi negocio mostrar:
  - [ ] Panel del negocio.
  - [ ] Productos.
  - [ ] Servicios.
  - [x] Publicaciones.
  - [x] Estados.
  - [x] Reels.
  - [x] Lives.
  - [x] Pedidos.
  - [ ] Clientes.
  - [x] Suscripciones.
  - [x] Métricas.
- [x] Si un usuario administra varios negocios:
  - [x] Permitir seleccionar negocio activo.
  - [x] Mantener opción “Comprador”.
  - [x] Mostrar `+ Crear negocio`.
- [x] No perder favoritos, compras, seguidores, mensajes ni suscripciones al cambiar de modo.

### Criterio de aceptación
- [x] Un usuario puede comprar y vender con la misma cuenta.
- [x] Cambiar entre Comprador y Mi negocio no altera ni borra información.

---

## 1.2 Municipios como contexto global

### Objetivo
Conservar y fortalecer la funcionalidad actual de municipios.

- [x] Identificar la entidad actual usada para municipios/ciudades.
- [x] No crear una entidad duplicada si ya existe.
- [x] Agregar selector global de municipio/localidad.
- [x] Debe permitir:
  - [x] municipio actual,
  - [x] “Cerca de mí”,
  - [x] otros municipios disponibles.
- [ ] El municipio seleccionado debe afectar:
  - [x] feed,
  - [x] vitrinas,
  - [x] publicaciones,
  - [x] promociones,
  - [x] Lives,
  - [x] solicitudes,
  - [ ] recomendaciones,
  - [x] destacados,
  - [x] negocios cercanos.
- [x] Conservar URLs/directorios actuales por municipio si existen.
- [x] Guardar preferencia de municipio del usuario.
- [ ] Si el usuario autoriza ubicación:
  - [ ] sugerir municipio automáticamente,
  - [x] nunca sobrescribir manualmente sin confirmación.

### Criterio de aceptación
- [x] El usuario nunca pierde la navegación por municipios.
- [ ] Todo el contenido social puede filtrarse por municipio.

---

## 1.3 Buscador principal

- [x] Mantener un único input de búsqueda.
- [x] Dentro del input incluir:
  - [x] botón `Cerca de mí`,
  - [x] botón rojo `Buscar`.
- [ ] El buscador debe encontrar:
  - [x] negocios,
  - [x] productos,
  - [ ] servicios,
  - [ ] publicaciones,
  - [x] categorías.
- [ ] Agregar filtros por:
  - [x] municipio,
  - [ ] distancia,
  - [x] categoría,
  - [x] precio,
  - [ ] tipo de contenido.
- [x] Mantener búsqueda actual si ya existe y ampliarla progresivamente.

---

# FASE 2 — Feed social

## 2.1 Inicio social

### Objetivo
Convertir Inicio en un feed de descubrimiento comercial local.

- [x] Crear feed principal.
- [x] No eliminar el directorio/vitrinas actuales.
- [x] Mover descubrimiento tradicional a `Explorar`. *(Sesión 15 sep 2026,
  a pedido del usuario: el feed pasó a ser la ruta `/` real —name `home`—
  y lo que antes vivía en Inicio (`ClientesController::home`) ahora es
  `Explorar` (`/explorar`), enlazado desde el header y la barra inferior.
  `/feed` se conserva como alias. Visual alineado a
  `public/images/feed.png`: columna de publicaciones + "Negocios cerca de
  ti" en la barra lateral.)*
- [ ] El feed debe mezclar:
  - [x] publicaciones de negocios seguidos,
  - [ ] publicaciones cercanas,
  - [x] publicaciones del municipio,
  - [x] productos destacados, *(los productos con promoción activa aparecen como patrocinados.)*
  - [x] promociones,
  - [x] Lives activos,
  - [ ] contenido recomendado.
- [ ] Crear pestañas:
  - [ ] Para ti.
  - [ ] Cerca de ti.
  - [x] Siguiendo.
  - [ ] Destacados.
- [ ] Agregar orden:
  - [x] recientes,
  - [ ] relevantes.

---

## 2.2 Publicaciones / Posts

- [x] Crear entidad `posts` o equivalente.
- [ ] Relacionar post con:
  - [x] usuario,
  - [x] negocio,
  - [ ] municipio,
  - [x] productos/servicios existentes.
- [ ] Tipos de publicación:
  - [x] texto,
  - [x] imagen,
  - [x] carrusel,
  - [x] video corto,
  - [x] promoción.
- [ ] Acciones:
  - [x] reaccionar / me gusta,
  - [x] comentar,
  - [x] compartir,
  - [x] guardar,
  - [ ] reportar.
- [x] Permitir etiquetar producto/servicio existente.
- [ ] Mostrar CTA:
  - [x] Comprar. *(solo cuando el negocio conectó Wompi y el producto tiene precio fijo, igual que en la vitrina.)*
  - [x] Ver producto.
  - [ ] Reservar.
  - [ ] Suscribirme.
  - [x] WhatsApp.
- [x] No duplicar producto al crear publicación.
- [ ] Permitir `Producto -> Crear publicación`.
- [ ] Permitir `Servicio -> Crear publicación`.
- [ ] Crear contador de:
  - [ ] vistas,
  - [ ] clics,
  - [ ] compras atribuidas,
  - [ ] guardados,
  - [ ] compartidos.

---

## 2.3 Seguir

- [ ] Permitir seguir:
  - [x] negocios,
  - [ ] emprendedores/perfiles,
  - [ ] categorías,
  - [ ] municipios.
- [x] Crear:
  - [x] seguidores,
  - [x] seguidos.
- [x] Crear feed `Siguiendo`.
- [x] Notificar contenido nuevo según preferencias del usuario.

---

# FASE 3 — Estados Merkamigo

## 3.1 Estados

- [x] Crear módulo de Estados.
- [x] Duración por defecto: 24 horas.
- [ ] Tipos:
  - [x] imagen,
  - [ ] video,
  - [x] promoción,
  - [x] producto,
  - [x] servicio.
- [ ] Permitir vincular:
  - [x] producto,
  - [x] servicio,
  - [ ] cupón,
  - [ ] Live.
- [ ] Acciones:
  - [x] Ver producto.
  - [ ] Comprar.
  - [ ] Reservar.
  - [x] WhatsApp.
- [x] Mostrar estados en carrusel superior del feed.
- [x] Permitir `Tu estado`.
- [ ] Registrar:
  - [x] visualizaciones,
  - [ ] clics,
  - [ ] conversiones.

---

# FASE 4 — Reels / videos cortos

## 4.1 Reels

Implementado (sesión 15 sep 2026) reutilizando `App\Domain\Social\Post`
con `type = video` en vez de un dominio paralelo: un reel es un post más
para reacciones/comentarios/seguir, solo cambia cómo se muestra. Ver
`App\Http\Controllers\ReelController`, ruta pública `/reels`, panel del
negocio en "Reels" (`emprendedores.negocios.reels`).

- [x] Crear módulo de video vertical corto.
- [x] Permitir carga desde móvil/web. *(input de archivo estándar, funciona en ambos.)*
- [x] Asociar uno o varios productos/servicios.
- [x] Mostrar:
  - [x] negocio,
  - [x] municipio,
  - [x] descripción,
  - [ ] CTA (Comprar/Ver producto — pendiente, ver nota abajo),
  - [x] producto relacionado.
- [x] Acciones:
  - [x] me gusta,
  - [x] comentar,
  - [x] compartir,
  - [x] guardar,
  - [x] seguir.
- [x] Crear vista de scroll vertical. *(Alcance reducido a propósito:
  scroll-snap dentro del layout normal del sitio —con header/footer—, no
  un modo de pantalla completa tipo TikTok; eso queda para cuando haya
  volumen real de reels que lo justifique.)*
- [x] Incluir sección `Reels para ti`. *(rail simple en la barra lateral del feed, enlaza a `/reels`.)*
- [x] Permitir compartir externamente. *(Web Share API nativo, mismo patrón que la vitrina pública.)*
- [ ] Optimización multimedia: los videos se guardan tal cual se suben
  (sin recompresión ni generación de miniatura del lado del servidor),
  mismo criterio que `municipality_hero_video`/`site_pidelo_video` ya
  existentes en el proyecto — evaluar ffmpeg/un proveedor externo (Mux,
  Cloudflare Stream) solo si el volumen real de reels lo justifica.
- [ ] CTA de producto en el reel (hoy solo enlaza al producto, sin el
  botón "Comprar" que sí tienen los posts del feed) — pendiente de una
  pasada de UI aparte.

---

# FASE 5 — Cerca de mí

> **Nota (16 sep 2026):** "Cerca de mí" ya existía como ORDEN (nunca
> excluye, `x-clientes.near-me-toggle` + `Support\Geo\Distance`, usado en
> `/buscar` y `/plaza/{municipio}`) — lo nuevo de esta sesión es el radio
> como FILTRO real (si se elige un radio, un negocio/producto fuera de
> él, o sin coordenadas propias, queda fuera de la lista en vez de
> aparecer al final). El motor real de la búsqueda es el Livewire
> `App\Livewire\CatalogResults` (`PlazaController` calcula lo mismo para
> el schema JSON-LD, pero ya no es lo que renderiza la lista visible).

## 5.1 Geolocalización

- [x] Reutilizar lógica actual si ya existe. *(`Support\Geo\Distance`, `nearMeCoordinates()`, `x-clientes.near-me-toggle` — nada nuevo para esto.)*
- [x] Solicitar permiso de ubicación de forma explícita. *(ya existía: solo al hacer clic en "Cerca de mí", `navigator.geolocation` nunca se llama solo.)*
- [x] No bloquear navegación si el usuario no acepta. *(ya existía: sin ubicación, todo sigue visible por municipio/recientes.)*
- [x] Calcular distancia negocio <-> usuario. *(ya existía.)*
- [ ] Mostrar:
  - [x] negocios cercanos,
  - [x] productos cercanos, *(nuevo: `CatalogResults::products()` ahora también ordena/filtra por la distancia del NEGOCIO del producto — un producto no tiene coordenadas propias.)*
  - [x] promociones cercanas, *(las campañas filtran por municipio y, con coordenadas autorizadas, por radio.)*
  - [ ] Lives cercanos *(los Lives ya filtran por municipio; falta aplicar radio por coordenadas.)*.
- [x] Filtros por radio:
  - [x] 1 km,
  - [x] 3 km,
  - [x] 5 km,
  - [x] 10 km,
  - [x] personalizado (`x-clientes.radius-filter`, campo numérico propio en km).
- [x] Mantener municipio como fallback. *(sin "Cerca de mí" activo, todo sigue filtrando/ordenando por municipio como antes; el radio sin coordenadas simplemente se ignora.)*

---

# FASE 6 — Solicitudes

## 6.1 Publicar “Estoy buscando…”

- [ ] Conservar funcionalidad actual si ya existe.
- [ ] Permitir crear solicitud con:
  - [ ] título,
  - [ ] descripción,
  - [ ] categoría,
  - [ ] municipio,
  - [ ] ubicación aproximada,
  - [ ] presupuesto,
  - [ ] fecha requerida,
  - [ ] imágenes.
- [ ] Negocios pueden responder con propuesta.
- [ ] Crear estado:
  - [ ] abierta,
  - [ ] con propuestas,
  - [ ] seleccionada,
  - [ ] cerrada.
- [ ] Permitir:
  - [ ] comparar propuestas,
  - [ ] iniciar chat,
  - [ ] aceptar propuesta,
  - [ ] pagar.

---

# FASE 7 — Checkout y pedidos

> **Nota (15 sep 2026):** esta fase se ejecutó adelantada, fuera de orden, a pedido directo del usuario (necesidad real de negocio, no planeación). La "capa Merkamigo Pay" original asumía que el dinero pasaba por una cuenta de Merkamigo — se investigó con el usuario y se descartó a propósito: Wompi no soporta split payments, y convertir a Merkamigo en recaudador de dinero de terceros traía una carga fiscal que el usuario no quería asumir. La arquitectura real construida es "cada negocio conecta su propia cuenta Wompi" — ver `TODO-Marketplace-Checkout.md` para el detalle completo. Las casillas de abajo reflejan honestamente qué de la visión original quedó cubierto por esa arquitectura distinta y qué no (carrito, multi-canal de pago, checkout desde post/estado/reel/Live siguen sin construirse).

## 7.1 Capa Merkamigo Pay

### Regla
Merkamigo controla la experiencia de compra. La pasarela procesa el dinero.

- [ ] Crear una abstracción `PaymentProvider`.
- [ ] No acoplar checkout a una única pasarela.
- [ ] Primera integración sugerida:
  - [x] Wompi.
- [ ] Dejar preparada segunda integración:
  - [ ] Mercado Pago.
- [x] Nunca almacenar tarjeta completa ni CVV.
- [x] Usar tokenización/fuentes de pago del proveedor.

---

## 7.2 Checkout

- [ ] Permitir comprar desde:
  - [x] producto,
  - [x] post,
  - [ ] estado,
  - [ ] reel,
  - [x] Live.
- [ ] Crear:
  - [ ] carrito,
  - [ ] resumen,
  - [ ] datos de entrega,
  - [x] método de pago,
  - [x] confirmación.
- [x] Mantener WhatsApp como alternativa.
- [x] No obligar a usar WhatsApp para completar compra.
- [ ] Registrar origen de conversión:
  - [ ] vitrina,
  - [ ] post,
  - [ ] estado,
  - [ ] reel,
  - [ ] Live.

---

## 7.3 Pedidos

- [x] Crear/ajustar entidad de pedidos.
- [ ] Estados:
  - [x] pendiente,
  - [x] pagado,
  - [ ] preparando,
  - [ ] listo,
  - [ ] enviado,
  - [ ] entregado,
  - [ ] cancelado,
  - [ ] reembolsado.
- [x] Mostrar pedido en:
  - [x] comprador,
  - [x] negocio.
- [x] Notificar cambios de estado.
- [x] Guardar historial.

---

# FASE 8 — Suscripciones y cobros recurrentes

> **Nota (16 sep 2026):** implementado en `App\Domain\Subscriptions`,
> reutilizando `Marketplace\Order`/`AccrueCommission` para el cobro
> periódico en vez de un segundo mecanismo de comisión — el dinero de
> cada periodo va directo a la cuenta Wompi del negocio, igual que una
> compra única (misma arquitectura de Fase 7). Un plan por producto en
> este alcance (sin niveles/tiers todavía).

## 8.1 Productos/servicios recurrentes

- [x] Permitir marcar producto/servicio como:
  - [x] compra única,
  - [x] suscripción.
- [x] Frecuencias:
  - [x] semanal,
  - [x] mensual,
  - [x] trimestral,
  - [x] anual.
- [x] Crear plan de suscripción.
- [x] Campos mínimos:
  - [x] nombre *(reutiliza el nombre del producto)*,
  - [x] precio *(reutiliza el precio del producto)*,
  - [x] periodicidad,
  - [x] beneficios,
  - [x] trial opcional,
  - [x] estado (`is_active`, para aceptar o no nuevos suscriptores).

---

## 8.2 Motor de suscripciones

- [x] Crear entidades:
  - [x] `subscription_plans`,
  - [x] `customer_subscriptions` *(nombre distinto de `subscriptions`, que ya existe para negocio → Merkamigo, intocable)*,
  - [ ] `subscription_payments` *(cada cobro es un `Marketplace\Order` con `customer_subscription_id`, no una tabla aparte)*,
  - [ ] `payment_sources` *(el token vive en `customer_subscriptions.wompi_payment_source_id`, una tarjeta por suscripción en este alcance)*,
  - [ ] `webhook_events` *(el cobro es síncrono al suscribirse/renovar, como `ChargeCommission`; no hay webhook de suscripción todavía)*.
- [x] Estados: `prueba`, `activa`, `pausada` *(reservado, sin acción todavía que la use)*, `cancelada`, `vencida`.
- [x] Guardar:
  - [x] fecha inicio (`current_period_starts_at`),
  - [x] próxima fecha de cobro (`current_period_ends_at`),
  - [ ] último pago *(se infiere de `orders` vía `customer_subscription_id`, no un campo aparte)*,
  - [x] proveedor *(Wompi, implícito — cada negocio solo tiene una integración)*,
  - [x] referencia externa (`wompi_payment_source_id` + `orders.wompi_transaction_id`).
- [x] Implementar:
  - [x] renovación automática (`RenewCustomerSubscriptions`, comando `subscriptions:renew`, diario),
  - [ ] reintentos,
  - [ ] periodo de gracia,
  - [x] cancelación (acceso sigue hasta el final del periodo ya pagado),
  - [ ] pausa,
  - [ ] reactivación.
- [ ] No cancelar acceso inmediatamente si el proveedor tarda en confirmar — **alcance reducido a propósito**: un cobro rechazado vence la suscripción de inmediato (ver nota en `ChargeSubscriptionPeriod`), sin el dunning con periodo de gracia que sí tiene `Billing\ProcessSubscriptionRenewals`. Revisar si se justifica una vez haya volumen real.
- [x] Validar siempre eventos por webhook *(no aplica aquí — no hay webhook de suscripción, el cobro se confirma sondeando la API igual que `ChargeCommission`)*.

---

# FASE 9 — Productos digitales

> **Nota (16 sep 2026):** `Product.type` gana `digital`; un archivo por
> producto en este alcance (`ProductFile`, disco `private`). Sirve tanto
> para compra única como para suscripción — el mismo `Entitlement` cubre
> los dos casos, solo cambia si `expires_at` es `null` (permanente) o
> tiene fecha (se refresca en cada cobro de periodo).

## 9.1 Digitales

- [x] Permitir vender:
  - [x] ebook,
  - [x] archivos,
  - [x] plantillas,
  - [x] cursos,
  - [x] videos,
  - [x] membresías,
  - [x] contenido premium. *(mismos tipos de archivo genéricos — pdf/zip/epub/mp4/mp3/docx/pptx — no hay categorías separadas por tipo de contenido todavía.)*
- [x] Controlar acceso por compra/suscripción.
- [x] Crear `entitlements` o mecanismo equivalente.
- [x] Al aprobar pago:
  - [x] habilitar acceso.
- [x] Al vencer suscripción:
  - [x] retirar acceso según reglas. *(pasivo: `Entitlement::isActive()` compara `expires_at` contra ahora — si la suscripción no se renueva, el acceso expira solo sin un job aparte que lo revoque activamente.)*
- [x] Proteger URLs de descarga.
- [x] Evitar URLs públicas permanentes.

---

# FASE 10 — Merkamigo Live

## 10.1 Live Commerce

> Implementar después de Posts, Estados, Checkout y Productos.

- [x] Crear módulo de transmisión en vivo. *(Sprint 8: `LiveStream`, panel del negocio y página pública.)*
- [x] Integración sugerida:
  - [x] proveedor de streaming externo. *(MVP desacoplado: YouTube, Vimeo o URL directa; no se contrató proveedor ni se creó infraestructura propia.)*
- [x] No desarrollar infraestructura de video en vivo desde cero si no es necesario.
- [x] Funciones:
  - [x] iniciar Live,
  - [x] terminar Live,
  - [x] contador de espectadores,
  - [x] chat,
  - [x] reacciones,
  - [x] compartir,
  - [x] seguir negocio.
- [x] Antes de iniciar:
  - [x] seleccionar productos/servicios.
- [x] Durante el Live:
  - [x] fijar producto,
  - [x] cambiar producto fijado,
  - [x] mostrar precio,
  - [x] mostrar inventario,
  - [x] botón Comprar.
- [x] Checkout sin abandonar la experiencia. *(El pago seguro se abre en una pestaña aparte y el Live continúa reproduciéndose en la pestaña original.)*
- [x] Al finalizar:
  - [x] guardar replay si aplica,
  - [x] mantener productos mostrados,
  - [x] permitir comprar desde grabación.

---

# FASE 11 — Notificaciones

- [x] Crear centro de notificaciones.
- [ ] Tipos:
  - [x] nuevo seguidor,
  - [x] reacción,
  - [x] comentario,
  - [ ] mensaje,
  - [ ] pedido,
  - [ ] pago recibido,
  - [ ] pago rechazado,
  - [ ] suscripción renovada,
  - [ ] suscripción por vencer,
  - [x] Live iniciado,
  - [ ] promoción,
  - [x] nueva propuesta a solicitud.
- [ ] Configuración por usuario:
  - [ ] in-app,
  - [ ] email,
  - [ ] push si existe app/PWA,
  - [ ] ubicación.
- [ ] Implementar reglas anti-spam.

---

# FASE 12 — Favoritos y guardados

- [x] Conservar favoritos actuales.
- [ ] Permitir favorito en:
  - [x] negocio,
  - [x] producto,
  - [x] servicio.
- [ ] Crear guardados para:
  - [x] posts,
  - [ ] reels,
  - [ ] Lives/replays.
- [ ] Permitir “Comprar después”.

---

# FASE 13 — Reseñas y confianza

- [ ] Conservar sistema actual de reseñas.
- [ ] Diferenciar:
  - [ ] reseña normal,
  - [ ] compra verificada.
- [ ] Mostrar:
  - [ ] puntuación,
  - [ ] número de ventas,
  - [ ] antigüedad,
  - [ ] tiempo de respuesta,
  - [ ] perfil verificado.
- [ ] Crear/reportar:
  - [ ] contenido,
  - [ ] negocio,
  - [ ] usuario.
- [ ] Mantener concepto de confianza de Merkamigo.

---

# FASE 14 — Mensajería

- [ ] Reutilizar mensajería actual si existe.
- [ ] Crear contexto de conversación:
  - [ ] producto,
  - [ ] servicio,
  - [ ] pedido,
  - [ ] solicitud.
- [ ] Permitir:
  - [ ] texto,
  - [ ] imágenes,
  - [ ] respuestas rápidas.
- [ ] Mantener opción de abrir WhatsApp.

---

# FASE 15 — IA para ventas

> Implementar cuando las funcionalidades base estén estables.

- [x] Generar descripción de producto. *(`GenerateProductDescription`, disponible en el editor de productos.)*
- [x] Mejorar texto de publicación.
- [x] Crear copy de estado.
- [x] Crear texto de Reel.
- [x] Sugerir promociones. *(El generador se reutiliza cuando la publicación/estado es de tipo promoción, siempre como borrador.)*
- [ ] Sugerir títulos.
- [ ] Crear variantes para:
  - [ ] Merkamigo,
  - [ ] WhatsApp,
  - [ ] Instagram,
  - [ ] Facebook.
- [x] No publicar automáticamente sin confirmación.
- [x] Reutilizar datos reales del catálogo.

---

# FASE 16 — Panel del negocio

- [ ] Crear dashboard resumido.
- [ ] KPIs:
  - [ ] ventas,
  - [ ] pedidos,
  - [ ] seguidores,
  - [ ] vistas,
  - [ ] clics,
  - [ ] conversión,
  - [ ] suscripciones activas,
  - [ ] MRR,
  - [ ] productos más vistos,
  - [ ] productos más vendidos,
  - [ ] clientes recurrentes.
- [x] Métricas por:
  - [x] post,
  - [x] estado,
  - [x] reel,
  - [x] Live.
- [x] Filtros por periodo. *(7, 30 y 90 días.)*

---

# FASE 17 — Panel del comprador

- [ ] Inicio.
- [ ] Explorar.
- [ ] Cerca de mí.
- [ ] Mis compras.
- [ ] Mis suscripciones.
- [ ] Favoritos.
- [ ] Guardados.
- [ ] Negocios seguidos.
- [ ] Mensajes.
- [ ] Notificaciones.
- [ ] Solicitudes.
- [ ] Reseñas.
- [ ] Configuración.

---

# FASE 18 — Promoción pagada

- [x] Preparar sistema de contenidos destacados. *(`ContentPromotion`, activación posterior a pago aprobado y prioridad visual en feed.)*
- [x] Permitir destacar:
  - [x] producto,
  - [x] post,
  - [x] estado,
  - [x] Live.
- [x] Segmentación:
  - [x] municipio,
  - [x] categoría,
  - [x] distancia. *(Se guarda radio de 1/3/5/10/20 km; cuando no hay ubicación del comprador se conserva el filtro por municipio.)*
- [x] Registrar:
  - [x] impresiones,
  - [x] clics,
  - [x] conversiones.
- [x] No implementar cobro hasta validar funcionalidad orgánica si aún no existe infraestructura. *(La capa orgánica y Wompi ya existían; se reutilizan los precios administrables de `BillingProduct`, sin crear tarifas nuevas.)*

---

# FASE 19 — Compartir fuera de Merkamigo

- [ ] Compartir:
  - [ ] vitrina,
  - [ ] producto,
  - [ ] servicio,
  - [ ] post,
  - [ ] reel,
  - [ ] Live.
- [ ] Canales:
  - [ ] WhatsApp,
  - [ ] Facebook,
  - [ ] enlace,
  - [ ] QR.
- [ ] Generar metadata Open Graph.
- [ ] Mantener links amigables.

---

# FASE 20 — Moderación y seguridad

- [ ] Reportar contenido.
- [ ] Bloquear usuario.
- [ ] Ocultar publicación.
- [ ] Moderación desde admin.
- [ ] Registro de auditoría.
- [ ] Política de contenido.
- [ ] Validar productos restringidos/prohibidos.
- [ ] Rate limiting para:
  - [ ] comentarios,
  - [ ] mensajes,
  - [ ] reacciones,
  - [ ] publicaciones.
- [ ] Validar subida de archivos.
- [ ] Limitar tamaños/formato.
- [ ] Proteger endpoints.

---

# FASE 21 — Base de datos y migraciones

## Recomendaciones

- [x] Revisar tablas actuales antes de crear nuevas.
- [ ] Agregar índices en:
  - [ ] `user_id`,
  - [ ] `business_id`,
  - [ ] `municipality_id`,
  - [ ] `created_at`,
  - [ ] `status`.
- [x] Indexar relaciones necesarias para feed.
- [x] Evitar consultas N+1.
- [x] Preparar paginación/cursor pagination.
- [x] Evitar guardar métricas pesadas directamente en consultas de feed.

### Tablas tentativas — crear solo si no existe equivalente

- [x] posts
- [x] post_media
- [x] post_products
- [x] post_reactions
- [x] post_comments
- [x] follows
- [x] saved_items
- [x] stories
- [x] story_views
- [ ] reels
- [ ] live_streams
- [ ] live_products
- [ ] carts
- [ ] cart_items
- [ ] orders
- [ ] order_items
- [ ] payments
- [ ] payment_sources
- [ ] subscription_plans
- [ ] subscriptions
- [ ] subscription_payments
- [ ] webhook_events
- [ ] notifications
- [ ] reports
- [ ] entitlements

---

# FASE 22 — API / backend

- [x] Crear servicios de dominio.
- [x] No colocar toda la lógica en controladores.
- [ ] Separar:
  - [ ] SocialService.
  - [ ] FeedService.
  - [ ] CommerceService.
  - [ ] PaymentService.
  - [ ] SubscriptionService.
  - [ ] NotificationService.
  - [ ] LocationService.
- [x] Agregar validaciones.
- [ ] Agregar policies/permisos.
- [ ] Crear endpoints versionados si aplica.
- [ ] Documentar endpoints nuevos.

---

# FASE 23 — Frontend / UX

## Navegación recomendada

### Comprador

```text
Inicio
Explorar
Cerca de mí
Categorías
Solicitudes
Mensajes
Notificaciones
Mis compras
Mis suscripciones
Favoritos
Guardados
```

### Negocio

```text
Panel
Mi vitrina
Productos
Servicios
Publicaciones
Estados
Reels
Lives
Pedidos
Suscripciones
Clientes
Métricas
Configuración
```

- [ ] Sidebar responsive.
- [ ] Mobile bottom navigation.
- [ ] Mantener CTA principal visible.
- [ ] Evitar sobrecargar pantallas.
- [ ] Usar skeleton loaders.
- [ ] Lazy loading de imágenes/video.
- [ ] Infinite scroll solo donde sea conveniente.

---

# FASE 24 — Rendimiento

- [ ] Comprimir imágenes.
- [ ] Generar thumbnails.
- [ ] Lazy load.
- [ ] CDN si aplica.
- [ ] Cache de feed.
- [ ] Cache de negocios cercanos.
- [ ] Paginar publicaciones.
- [ ] Optimizar queries por municipio.
- [ ] No cargar videos automáticamente sin control.
- [ ] Revisar Core Web Vitals.

---

# FASE 25 — SEO y compartir

- [ ] Mantener SEO actual de vitrinas.
- [ ] Indexar:
  - [ ] negocio,
  - [ ] producto,
  - [ ] servicio.
- [ ] Evaluar indexación de publicaciones públicas.
- [ ] URLs amigables.
- [ ] Schema.org cuando aplique.
- [ ] Open Graph.
- [ ] Twitter/X Cards si aplica.
- [ ] Canonicals.

---

# FASE 26 — QA

## Tests mínimos

- [ ] Registro/login.
- [ ] Cambio Comprador <-> Mi negocio.
- [ ] Cambio de municipio.
- [ ] Búsqueda.
- [ ] Crear post.
- [ ] Eliminar/editar post.
- [ ] Reaccionar.
- [ ] Comentar.
- [ ] Seguir negocio.
- [ ] Estado.
- [ ] Reel.
- [ ] Producto vinculado.
- [ ] Checkout.
- [ ] Pago aprobado.
- [ ] Pago rechazado.
- [ ] Webhook duplicado.
- [ ] Crear suscripción.
- [ ] Renovación.
- [ ] Cancelación.
- [ ] Pedido.
- [ ] Notificaciones.
- [ ] Permisos.
- [ ] Responsivo móvil.
- [ ] Compatibilidad con funcionalidades existentes.

---

# FASE 27 — Migración sin romper producción

- [ ] Crear feature flags:
  - [ ] social_feed,
  - [ ] stories,
  - [ ] reels,
  - [ ] checkout,
  - [ ] subscriptions,
  - [ ] live.
- [ ] Activar primero para administradores/testing.
- [ ] Activar por grupo de usuarios.
- [ ] Activar por municipio si conviene.
- [ ] Mantener fallback a interfaz actual.
- [ ] Registrar errores.
- [ ] Medir uso antes de habilitar siguiente fase.

---

# FASE 28 — Orden sugerido de ejecución para Codex

## Sprint 1
- [x] Auditar proyecto actual.
- [x] Documentar arquitectura actual.
- [x] Identificar usuarios, negocios, municipios, productos, servicios y roles.
- [x] Implementar cambio Comprador / Mi negocio.
- [x] Mejorar selector de municipio.
- [x] Mejorar buscador.

## Sprint 2
- [x] Crear modelo de posts.
- [x] Feed.
- [x] Reacciones.
- [x] Comentarios.
- [x] Guardados.
- [x] Seguir negocios.

## Sprint 3
- [x] Estados.
- [x] Notificaciones sociales.
- [x] Integración de productos en posts/estados.

## Sprint 4
- [x] Reels.
- [ ] Optimización multimedia. *(videos se guardan tal cual, sin recompresión — ver nota en Fase 4.)*
- [x] Feed de video. *(los reels también aparecen en el feed normal, además de en `/reels`.)*

## Sprint 5
- [x] Checkout.
- [x] Pedidos.
- [x] Integración inicial con pasarela.
- [x] Webhooks.

## Sprint 6
- [x] Suscripciones.
- [x] Cobros recurrentes.
- [x] Productos digitales.
- [x] Entitlements.

## Sprint 7
- [x] Merkamigo Cerca. *(radio de "Cerca de mí" como filtro real, ver Fase 5.)*
- [x] Mejoras por ubicación. *(negocios, productos y promociones; Lives se filtran por municipio.)*
- [x] Promociones cercanas. *(Segmentación por municipio y radio; si el comprador no comparte coordenadas se conserva el municipio.)*
- [ ] Feed por distancia. *(el feed social sigue filtrando por municipio, no por radio — el radio quedó en `/buscar`; llevarlo al feed es la extensión natural pendiente.)*

## Sprint 8
- [x] Live Commerce. *(MVP con transmisión externa; el proveedor administrado queda por decidir.)*
- [x] Productos fijados.
- [x] Checkout desde Live. *(Abre Wompi hospedado en otra pestaña para conservar la transmisión activa.)*
- [x] Replay comprable.

## Sprint 9
- [x] Métricas. *(Incluye rendimiento social y filtros de 7/30/90 días.)*
- [x] Panel de negocio. *(Se amplió el panel existente, sin duplicarlo.)*
- [x] IA de ventas. *(Borradores editables para publicación, estado, reel y Live; nunca autopublica.)*
- [x] Promoción pagada. *(Producto, post, estado y Live; reutiliza los paquetes/precios existentes, segmenta por municipio/categoría/radio y atribuye impresiones, clics y ventas.)*

---

# Definición de terminado global

La nueva versión se considera estable cuando:

- [ ] No se pierde ninguna funcionalidad previa de Merkamigo.
- [x] Un usuario puede comprar y vender con una sola cuenta.
- [x] El municipio sigue siendo parte central de la navegación.
- [x] Las vitrinas existentes siguen funcionando.
- [x] Los productos existentes pueden publicarse sin duplicarse.
- [x] Existe feed social.
- [x] Se puede seguir a negocios.
- [x] Se pueden crear publicaciones y estados.
- [x] Se puede comprar desde contenido social.
- [x] Existen pedidos.
- [x] Existen pagos.
- [x] Existen suscripciones recurrentes.
- [x] Existe soporte para productos digitales.
- [x] Live Commerce funciona como una capa adicional, no como requisito del sistema.
- [ ] La interfaz sigue siendo limpia, profesional, rápida y enfocada en comercio local.

---

# Nota final para Codex

Antes de ejecutar cada bloque:

1. Inspeccionar implementación actual.
2. Reutilizar lo existente.
3. Proponer el cambio mínimo necesario.
4. Evitar duplicación.
5. Crear migraciones reversibles.
6. Agregar pruebas.
7. Ejecutar pruebas.
8. Verificar que no se rompan vitrinas, municipios, catálogo, WhatsApp, QR, reseñas ni módulos existentes.
9. Hacer commits pequeños y descriptivos por funcionalidad.

**No hacer una reescritura completa del proyecto. Evolucionar Merkamigo por capas.**
