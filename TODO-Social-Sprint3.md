# TODO-Social-Sprint3.md — Seguimiento de ejecución de TODO_social.md

**Fecha:** 15 de septiembre de 2026
**Alcance:** Sprint 3 de `TODO_social.md` (Fase 28) — Estados, notificaciones sociales, integración de productos en posts/estados. Continuación de `TODO-Social-Sprint2.md`.

## 1. Qué se construyó

### Estados (Fase 3)
- Tablas nuevas `stories` y `story_views` (reversibles). `stories.product_id` referencia un producto ya existente (nunca lo duplica, mismo criterio que `post_product`).
- Modelo `Story` (`isExpired()`/`isActive()`/`isViewedBy()`), `StoryView`.
- Acciones `CreateStory` (expira a las 24h exactas por defecto, sin opción de configurar duración todavía), `RecordStoryView` (idempotente — una vista cuenta una sola vez por usuario, solo autenticados, mismo criterio que `recently_viewed_businesses`), `DeleteStory`.
- `livewire:stories-rail` — carrusel de estados arriba del feed: círculos con anillo degradado (no visto) o gris (visto), agrupados por negocio; al hacer clic abre un visor de pantalla completa (barra de progreso, texto, CTA "Ver producto"/WhatsApp, anterior/siguiente).
- Página del emprendedor `emprendedores/negocios/{business}/estados` ("Tu estado"): crear (tipo, foto, texto, producto opcional) y ver/eliminar los propios estados activos con su conteo de vistas.

### Notificaciones sociales (Fase 11, parcial — solo lo que pide el Sprint 3)
- `NewFollower` — a todos los miembros activos del negocio (no solo el dueño) cuando alguien los sigue.
- `PostReacted` / `PostCommented` — al autor del post, nunca a sí mismo si reacciona/comenta su propio contenido.
- Las tres reutilizan el centro de actividad ya existente (`clientes.actividad`) sin tocarlo — sigue siendo genérico.
- **No se tocó** el resto de tipos de la Fase 11 (mensaje, pedido, pago, suscripción, Live, promoción) — no existen las entidades detrás todavía (mensajería, pedidos, pagos, suscripciones son sprints aparte). Se marcó también "nueva propuesta a solicitud" como ya cumplida: es `OfferSubmitted`, una notificación que **ya existía antes de este TODO** (Pídelo en Merkamigo), descubierta al auditar la Fase 11 completa.
- **Configuración por usuario (in-app/email/push/ubicación) no se implementó** — no hay página de preferencias de notificación; todo llega siempre por el canal `database` (+ push si existe). Es trabajo de la Fase 11 real, no de este sprint.
- **Reglas anti-spam no se implementaron** — comentarios ya pasan por `NoLinks` (Sprint 2), pero no hay rate limiting nuevo. Es Fase 20 (Moderación y seguridad), sprint aparte.

### Integración de productos en posts/estados
- Ya cubierto por diseño: posts (Sprint 2) y ahora estados enlazan productos reales del negocio vía `product_id`/`post_product`, nunca los duplican.

## 2. Alcance reducido a propósito (mismo criterio que Sprint 2)

- **Tipo "video" no implementado** (posts y estados) — sigue dependiendo de infraestructura de video que no existe; es el objeto de la Fase 4 (Reels).
- **Vincular cupón o Live a un estado**: no existen esas entidades todavía.
- **CTA "Comprar"/"Reservar"**: dependen de checkout (Sprint 5).
- **Clics y conversiones no se registran** — solo visualizaciones (`views_count` + `story_views`). Registrar clics en los CTA y conversiones reales requiere el checkout que tampoco existe aún.

## 3. Verificado

- 10 tests nuevos (`tests/Feature/Social/StoriesTest.php` × 6, `SocialNotificationsTest.php` × 4), todos en verde.
- Suite completa: **787 passed / 34 failed** (mismos 34 fallos preexistentes de siempre — antes de este sprint eran 777/34).
- **Bug real encontrado y corregido durante la verificación**: `livewire:stories-rail` rompía dos tests existentes de `PostsTest` (`the feed shows recent posts...`, `the following feed tab...`) con "Livewire encountered a missing root tag" — el componente no tenía una etiqueta raíz que se renderizara siempre (cuando no hay estados activos, el `@if` de más afuera no deja nada montado). Mismo bug ya documentado en el comentario de `favorite-button.blade.php` de una sesión anterior; se corrigió envolviendo todo el componente en un único `<div>` raíz.
- Verificado manualmente con Playwright de punta a punta (negocio + estado + post de prueba, creados y limpiados después): carrusel con anillo de "no visto", visor de estado (imagen, texto, WhatsApp), panel "Estados" del emprendedor mostrando el conteo de vistas — sin errores de consola.
- Pint sin cambios de estilo pendientes.

## 4. Siguiente paso sugerido

Sprint 4 (`TODO_social.md`, Fase 28): Reels, optimización multimedia, feed de video. Esto sí depende de resolver primero la limitación de video que se viene arrastrando desde el Sprint 2 (`MediaUploader` solo procesa imágenes) — antes de programar, hay que decidir con el usuario cómo manejar video: ¿procesarlo en el servidor (requiere ffmpeg u otra dependencia nueva), subirlo tal cual sin recomprimir, o usar un proveedor externo? Pedir confirmación de alcance antes de arrancar, igual que los sprints anteriores.
