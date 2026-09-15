# TODO_social.md — Merkamigo Social Commerce

> Objetivo: evolucionar Merkamigo hacia una red social de comercio local sin perder ni reemplazar lo ya construido.
> Regla principal: **todo lo existente debe conservarse y reutilizarse**. La nueva versión agrega una capa social y transaccional sobre vitrinas, productos, servicios, municipios, categorías, QR, WhatsApp, reseñas y demás funcionalidades actuales.

---

## 0. Reglas de implementación

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

- [ ] Reemplazar la lógica visible de “rol” por una experiencia de **modo de uso**.
- [ ] Mostrar un selector pequeño en sidebar:

```text
Ver cómo:
[ Comprador ] [ Mi negocio ]
```

- [ ] No mostrar textos como “Mi rol en Merkamigo”.
- [ ] Mantener el selector discreto y siempre accesible.
- [ ] El usuario debe poder cambiar de modo sin cerrar sesión.
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
  - [ ] Publicaciones.
  - [ ] Estados.
  - [ ] Reels.
  - [ ] Lives.
  - [ ] Pedidos.
  - [ ] Clientes.
  - [ ] Suscripciones.
  - [ ] Métricas.
- [ ] Si un usuario administra varios negocios:
  - [ ] Permitir seleccionar negocio activo.
  - [ ] Mantener opción “Comprador”.
  - [ ] Mostrar `+ Crear negocio`.
- [ ] No perder favoritos, compras, seguidores, mensajes ni suscripciones al cambiar de modo.

### Criterio de aceptación
- [ ] Un usuario puede comprar y vender con la misma cuenta.
- [ ] Cambiar entre Comprador y Mi negocio no altera ni borra información.

---

## 1.2 Municipios como contexto global

### Objetivo
Conservar y fortalecer la funcionalidad actual de municipios.

- [ ] Identificar la entidad actual usada para municipios/ciudades.
- [ ] No crear una entidad duplicada si ya existe.
- [ ] Agregar selector global de municipio/localidad.
- [ ] Debe permitir:
  - [ ] municipio actual,
  - [ ] “Cerca de mí”,
  - [ ] otros municipios disponibles.
- [ ] El municipio seleccionado debe afectar:
  - [ ] feed,
  - [ ] vitrinas,
  - [ ] publicaciones,
  - [ ] promociones,
  - [ ] Lives,
  - [ ] solicitudes,
  - [ ] recomendaciones,
  - [ ] destacados,
  - [ ] negocios cercanos.
- [ ] Conservar URLs/directorios actuales por municipio si existen.
- [ ] Guardar preferencia de municipio del usuario.
- [ ] Si el usuario autoriza ubicación:
  - [ ] sugerir municipio automáticamente,
  - [ ] nunca sobrescribir manualmente sin confirmación.

### Criterio de aceptación
- [ ] El usuario nunca pierde la navegación por municipios.
- [ ] Todo el contenido social puede filtrarse por municipio.

---

## 1.3 Buscador principal

- [ ] Mantener un único input de búsqueda.
- [ ] Dentro del input incluir:
  - [ ] botón `Cerca de mí`,
  - [ ] botón rojo `Buscar`.
- [ ] El buscador debe encontrar:
  - [ ] negocios,
  - [ ] productos,
  - [ ] servicios,
  - [ ] publicaciones,
  - [ ] categorías.
- [ ] Agregar filtros por:
  - [ ] municipio,
  - [ ] distancia,
  - [ ] categoría,
  - [ ] precio,
  - [ ] tipo de contenido.
- [ ] Mantener búsqueda actual si ya existe y ampliarla progresivamente.

---

# FASE 2 — Feed social

## 2.1 Inicio social

### Objetivo
Convertir Inicio en un feed de descubrimiento comercial local.

- [ ] Crear feed principal.
- [ ] No eliminar el directorio/vitrinas actuales.
- [ ] Mover descubrimiento tradicional a `Explorar`.
- [ ] El feed debe mezclar:
  - [ ] publicaciones de negocios seguidos,
  - [ ] publicaciones cercanas,
  - [ ] publicaciones del municipio,
  - [ ] productos destacados,
  - [ ] promociones,
  - [ ] Lives activos,
  - [ ] contenido recomendado.
- [ ] Crear pestañas:
  - [ ] Para ti.
  - [ ] Cerca de ti.
  - [ ] Siguiendo.
  - [ ] Destacados.
- [ ] Agregar orden:
  - [ ] recientes,
  - [ ] relevantes.

---

## 2.2 Publicaciones / Posts

- [ ] Crear entidad `posts` o equivalente.
- [ ] Relacionar post con:
  - [ ] usuario,
  - [ ] negocio,
  - [ ] municipio,
  - [ ] productos/servicios existentes.
- [ ] Tipos de publicación:
  - [ ] texto,
  - [ ] imagen,
  - [ ] carrusel,
  - [ ] video corto,
  - [ ] promoción.
- [ ] Acciones:
  - [ ] reaccionar / me gusta,
  - [ ] comentar,
  - [ ] compartir,
  - [ ] guardar,
  - [ ] reportar.
- [ ] Permitir etiquetar producto/servicio existente.
- [ ] Mostrar CTA:
  - [ ] Comprar.
  - [ ] Ver producto.
  - [ ] Reservar.
  - [ ] Suscribirme.
  - [ ] WhatsApp.
- [ ] No duplicar producto al crear publicación.
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
  - [ ] negocios,
  - [ ] emprendedores/perfiles,
  - [ ] categorías,
  - [ ] municipios.
- [ ] Crear:
  - [ ] seguidores,
  - [ ] seguidos.
- [ ] Crear feed `Siguiendo`.
- [ ] Notificar contenido nuevo según preferencias del usuario.

---

# FASE 3 — Estados Merkamigo

## 3.1 Estados

- [ ] Crear módulo de Estados.
- [ ] Duración por defecto: 24 horas.
- [ ] Tipos:
  - [ ] imagen,
  - [ ] video,
  - [ ] promoción,
  - [ ] producto,
  - [ ] servicio.
- [ ] Permitir vincular:
  - [ ] producto,
  - [ ] servicio,
  - [ ] cupón,
  - [ ] Live.
- [ ] Acciones:
  - [ ] Ver producto.
  - [ ] Comprar.
  - [ ] Reservar.
  - [ ] WhatsApp.
- [ ] Mostrar estados en carrusel superior del feed.
- [ ] Permitir `Tu estado`.
- [ ] Registrar:
  - [ ] visualizaciones,
  - [ ] clics,
  - [ ] conversiones.

---

# FASE 4 — Reels / videos cortos

## 4.1 Reels

- [ ] Crear módulo de video vertical corto.
- [ ] Permitir carga desde móvil/web.
- [ ] Asociar uno o varios productos/servicios.
- [ ] Mostrar:
  - [ ] negocio,
  - [ ] municipio,
  - [ ] descripción,
  - [ ] CTA,
  - [ ] producto relacionado.
- [ ] Acciones:
  - [ ] me gusta,
  - [ ] comentar,
  - [ ] compartir,
  - [ ] guardar,
  - [ ] seguir.
- [ ] Crear vista de scroll vertical.
- [ ] Incluir sección `Reels para ti`.
- [ ] Permitir compartir externamente.

---

# FASE 5 — Cerca de mí

## 5.1 Geolocalización

- [ ] Reutilizar lógica actual si ya existe.
- [ ] Solicitar permiso de ubicación de forma explícita.
- [ ] No bloquear navegación si el usuario no acepta.
- [ ] Calcular distancia negocio <-> usuario.
- [ ] Mostrar:
  - [ ] negocios cercanos,
  - [ ] productos cercanos,
  - [ ] promociones cercanas,
  - [ ] Lives cercanos.
- [ ] Filtros por radio:
  - [ ] 1 km,
  - [ ] 3 km,
  - [ ] 5 km,
  - [ ] 10 km,
  - [ ] personalizado.
- [ ] Mantener municipio como fallback.

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

## 7.1 Capa Merkamigo Pay

### Regla
Merkamigo controla la experiencia de compra. La pasarela procesa el dinero.

- [ ] Crear una abstracción `PaymentProvider`.
- [ ] No acoplar checkout a una única pasarela.
- [ ] Primera integración sugerida:
  - [ ] Wompi.
- [ ] Dejar preparada segunda integración:
  - [ ] Mercado Pago.
- [ ] Nunca almacenar tarjeta completa ni CVV.
- [ ] Usar tokenización/fuentes de pago del proveedor.

---

## 7.2 Checkout

- [ ] Permitir comprar desde:
  - [ ] producto,
  - [ ] post,
  - [ ] estado,
  - [ ] reel,
  - [ ] Live.
- [ ] Crear:
  - [ ] carrito,
  - [ ] resumen,
  - [ ] datos de entrega,
  - [ ] método de pago,
  - [ ] confirmación.
- [ ] Mantener WhatsApp como alternativa.
- [ ] No obligar a usar WhatsApp para completar compra.
- [ ] Registrar origen de conversión:
  - [ ] vitrina,
  - [ ] post,
  - [ ] estado,
  - [ ] reel,
  - [ ] Live.

---

## 7.3 Pedidos

- [ ] Crear/ajustar entidad de pedidos.
- [ ] Estados:
  - [ ] pendiente,
  - [ ] pagado,
  - [ ] preparando,
  - [ ] listo,
  - [ ] enviado,
  - [ ] entregado,
  - [ ] cancelado,
  - [ ] reembolsado.
- [ ] Mostrar pedido en:
  - [ ] comprador,
  - [ ] negocio.
- [ ] Notificar cambios de estado.
- [ ] Guardar historial.

---

# FASE 8 — Suscripciones y cobros recurrentes

## 8.1 Productos/servicios recurrentes

- [ ] Permitir marcar producto/servicio como:
  - [ ] compra única,
  - [ ] suscripción.
- [ ] Frecuencias:
  - [ ] semanal,
  - [ ] mensual,
  - [ ] trimestral,
  - [ ] anual.
- [ ] Crear plan de suscripción.
- [ ] Campos mínimos:
  - [ ] nombre,
  - [ ] precio,
  - [ ] periodicidad,
  - [ ] beneficios,
  - [ ] trial opcional,
  - [ ] estado.

---

## 8.2 Motor de suscripciones

- [ ] Crear entidades:
  - [ ] `subscription_plans`,
  - [ ] `subscriptions`,
  - [ ] `subscription_payments`,
  - [ ] `payment_sources`,
  - [ ] `webhook_events`.
- [ ] Estados:
  - [ ] active,
  - [ ] past_due,
  - [ ] paused,
  - [ ] canceled,
  - [ ] expired.
- [ ] Guardar:
  - [ ] fecha inicio,
  - [ ] próxima fecha de cobro,
  - [ ] último pago,
  - [ ] proveedor,
  - [ ] referencia externa.
- [ ] Implementar:
  - [ ] renovación automática,
  - [ ] reintentos,
  - [ ] periodo de gracia,
  - [ ] cancelación,
  - [ ] pausa,
  - [ ] reactivación.
- [ ] No cancelar acceso inmediatamente si el proveedor tarda en confirmar.
- [ ] Validar siempre eventos por webhook.

---

# FASE 9 — Productos digitales

## 9.1 Digitales

- [ ] Permitir vender:
  - [ ] ebook,
  - [ ] archivos,
  - [ ] plantillas,
  - [ ] cursos,
  - [ ] videos,
  - [ ] membresías,
  - [ ] contenido premium.
- [ ] Controlar acceso por compra/suscripción.
- [ ] Crear `entitlements` o mecanismo equivalente.
- [ ] Al aprobar pago:
  - [ ] habilitar acceso.
- [ ] Al vencer suscripción:
  - [ ] retirar acceso según reglas.
- [ ] Proteger URLs de descarga.
- [ ] Evitar URLs públicas permanentes.

---

# FASE 10 — Merkamigo Live

## 10.1 Live Commerce

> Implementar después de Posts, Estados, Checkout y Productos.

- [ ] Crear módulo de transmisión en vivo.
- [ ] Integración sugerida:
  - [ ] proveedor de streaming externo.
- [ ] No desarrollar infraestructura de video en vivo desde cero si no es necesario.
- [ ] Funciones:
  - [ ] iniciar Live,
  - [ ] terminar Live,
  - [ ] contador de espectadores,
  - [ ] chat,
  - [ ] reacciones,
  - [ ] compartir,
  - [ ] seguir negocio.
- [ ] Antes de iniciar:
  - [ ] seleccionar productos/servicios.
- [ ] Durante el Live:
  - [ ] fijar producto,
  - [ ] cambiar producto fijado,
  - [ ] mostrar precio,
  - [ ] mostrar inventario,
  - [ ] botón Comprar.
- [ ] Checkout sin abandonar la experiencia.
- [ ] Al finalizar:
  - [ ] guardar replay si aplica,
  - [ ] mantener productos mostrados,
  - [ ] permitir comprar desde grabación.

---

# FASE 11 — Notificaciones

- [ ] Crear centro de notificaciones.
- [ ] Tipos:
  - [ ] nuevo seguidor,
  - [ ] reacción,
  - [ ] comentario,
  - [ ] mensaje,
  - [ ] pedido,
  - [ ] pago recibido,
  - [ ] pago rechazado,
  - [ ] suscripción renovada,
  - [ ] suscripción por vencer,
  - [ ] Live iniciado,
  - [ ] promoción,
  - [ ] nueva propuesta a solicitud.
- [ ] Configuración por usuario:
  - [ ] in-app,
  - [ ] email,
  - [ ] push si existe app/PWA,
  - [ ] ubicación.
- [ ] Implementar reglas anti-spam.

---

# FASE 12 — Favoritos y guardados

- [ ] Conservar favoritos actuales.
- [ ] Permitir favorito en:
  - [ ] negocio,
  - [ ] producto,
  - [ ] servicio.
- [ ] Crear guardados para:
  - [ ] posts,
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

- [ ] Generar descripción de producto.
- [ ] Mejorar texto de publicación.
- [ ] Crear copy de estado.
- [ ] Crear texto de Reel.
- [ ] Sugerir promociones.
- [ ] Sugerir títulos.
- [ ] Crear variantes para:
  - [ ] Merkamigo,
  - [ ] WhatsApp,
  - [ ] Instagram,
  - [ ] Facebook.
- [ ] No publicar automáticamente sin confirmación.
- [ ] Reutilizar datos reales del catálogo.

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
- [ ] Métricas por:
  - [ ] post,
  - [ ] estado,
  - [ ] reel,
  - [ ] Live.
- [ ] Filtros por periodo.

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

- [ ] Preparar sistema de contenidos destacados.
- [ ] Permitir destacar:
  - [ ] producto,
  - [ ] post,
  - [ ] estado,
  - [ ] Live.
- [ ] Segmentación:
  - [ ] municipio,
  - [ ] categoría,
  - [ ] distancia.
- [ ] Registrar:
  - [ ] impresiones,
  - [ ] clics,
  - [ ] conversiones.
- [ ] No implementar cobro hasta validar funcionalidad orgánica si aún no existe infraestructura.

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

- [ ] Revisar tablas actuales antes de crear nuevas.
- [ ] Agregar índices en:
  - [ ] `user_id`,
  - [ ] `business_id`,
  - [ ] `municipality_id`,
  - [ ] `created_at`,
  - [ ] `status`.
- [ ] Indexar relaciones necesarias para feed.
- [ ] Evitar consultas N+1.
- [ ] Preparar paginación/cursor pagination.
- [ ] Evitar guardar métricas pesadas directamente en consultas de feed.

### Tablas tentativas — crear solo si no existe equivalente

- [ ] posts
- [ ] post_media
- [ ] post_products
- [ ] post_reactions
- [ ] post_comments
- [ ] follows
- [ ] saved_items
- [ ] stories
- [ ] story_views
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

- [ ] Crear servicios de dominio.
- [ ] No colocar toda la lógica en controladores.
- [ ] Separar:
  - [ ] SocialService.
  - [ ] FeedService.
  - [ ] CommerceService.
  - [ ] PaymentService.
  - [ ] SubscriptionService.
  - [ ] NotificationService.
  - [ ] LocationService.
- [ ] Agregar validaciones.
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
- [ ] Auditar proyecto actual.
- [ ] Documentar arquitectura actual.
- [ ] Identificar usuarios, negocios, municipios, productos, servicios y roles.
- [ ] Implementar cambio Comprador / Mi negocio.
- [ ] Mejorar selector de municipio.
- [ ] Mejorar buscador.

## Sprint 2
- [ ] Crear modelo de posts.
- [ ] Feed.
- [ ] Reacciones.
- [ ] Comentarios.
- [ ] Guardados.
- [ ] Seguir negocios.

## Sprint 3
- [ ] Estados.
- [ ] Notificaciones sociales.
- [ ] Integración de productos en posts/estados.

## Sprint 4
- [ ] Reels.
- [ ] Optimización multimedia.
- [ ] Feed de video.

## Sprint 5
- [ ] Checkout.
- [ ] Pedidos.
- [ ] Integración inicial con pasarela.
- [ ] Webhooks.

## Sprint 6
- [ ] Suscripciones.
- [ ] Cobros recurrentes.
- [ ] Productos digitales.
- [ ] Entitlements.

## Sprint 7
- [ ] Merkamigo Cerca.
- [ ] Mejoras por ubicación.
- [ ] Promociones cercanas.
- [ ] Feed por distancia.

## Sprint 8
- [ ] Live Commerce.
- [ ] Productos fijados.
- [ ] Checkout desde Live.
- [ ] Replay comprable.

## Sprint 9
- [ ] Métricas.
- [ ] Panel de negocio.
- [ ] IA de ventas.
- [ ] Promoción pagada.

---

# Definición de terminado global

La nueva versión se considera estable cuando:

- [ ] No se pierde ninguna funcionalidad previa de Merkamigo.
- [ ] Un usuario puede comprar y vender con una sola cuenta.
- [ ] El municipio sigue siendo parte central de la navegación.
- [ ] Las vitrinas existentes siguen funcionando.
- [ ] Los productos existentes pueden publicarse sin duplicarse.
- [ ] Existe feed social.
- [ ] Se puede seguir a negocios.
- [ ] Se pueden crear publicaciones y estados.
- [ ] Se puede comprar desde contenido social.
- [ ] Existen pedidos.
- [ ] Existen pagos.
- [ ] Existen suscripciones recurrentes.
- [ ] Existe soporte para productos digitales.
- [ ] Live Commerce funciona como una capa adicional, no como requisito del sistema.
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
