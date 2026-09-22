# TODO-Social-Sprint2.md — Seguimiento de ejecución de TODO_social.md

**Fecha:** 15 de septiembre de 2026
**Alcance:** Sprint 2 de `TODO_social.md` (Fase 28) — posts, feed, reacciones, comentarios, guardados, seguir negocios. Continuación de `TODO-Social-Sprint1.md`.

## 0. Alcance reducido a propósito (documentado antes de programar)

Siguiendo la Fase 27 del TODO ("mantener fallback a interfaz actual", "activar primero para administradores/testing, medir antes de habilitar la siguiente fase"), se tomaron estas decisiones deliberadas para no arriesgar la app en producción:

1. **`Inicio` NO se reemplazó por el feed todavía.** El feed nuevo vive en `/feed` (nav "Feed"), separado de `ClientesController::home()`. Convertir el home actual en el feed social es un cambio de UX grande sobre la página más visitada del sitio — se deja como decisión de producto aparte, una vez el feed tenga contenido real y se valide con negocios reales publicando.
2. **Los posts se publican de inmediato** (sin flujo de borrador/edición) — reduce el alcance para este sprint. `status: borrador` existe a nivel de dato pero no hay UI para gestionar borradores todavía.
3. **Tipo de post "video corto" no se implementó** — depende de infraestructura de video que no existe (`MediaUploader` solo maneja imágenes); es además el objeto central de la Fase 4 (Reels), un sprint aparte.
4. **CTAs "Comprar/Reservar/Suscribirme" no se agregaron** — dependen de checkout/pedidos/suscripciones (Sprints 5-6), que no existen todavía. Sí se agregó "Ver producto" (enlaza al detalle) y "WhatsApp" (ya existente en el negocio).
5. **"Compartir" y "Reportar" en el post no tienen botón en el feed todavía** — el reporte SÍ está preparado del lado de datos (`Report::reportableLabel()` reconoce `Post`), falta el formulario público (mismo patrón que `reportes.crear.negocio/producto/recomendacion`, solo que no se construyó para no ampliar más el alcance).
6. **Contadores de vistas/clics/compartidos no se implementaron** — `posts.views_count` existe en el esquema pero nada lo incrementa todavía; ninguna vista tiene ese dato. Reacciones y comentarios sí se cuentan y muestran en tiempo real.
7. **"Seguir" se limitó a negocios** — perfiles de emprendedor, categorías y municipios (también listados en 2.3) quedan fuera; el propio checklist del Sprint 2 en la Fase 28 solo pide "Seguir negocios".

## 1. Qué se construyó

### Base de datos (todas reversibles, `down()` con `dropIfExists`)
- `posts`, `post_media`, `post_product` (pivote), `post_reactions`, `post_comments`, `follows`.
- `posts.status` reutiliza el mismo vocabulario que ya usa el resto del proyecto (`borrador/publicado/oculto`), `post_comments.status` reutiliza `publicado/oculto`.

### Dominio nuevo `App\Domain\Social` (mismas convenciones que el resto del proyecto — Actions con `handle()` único, no "Services")
- Modelos: `Post` (usa `Favoritable` — **cero tablas nuevas para "guardados", se reutiliza `favorites` tal cual**, ya era polimórfica), `PostMedia`, `PostReaction`, `PostComment`, `Follow`.
- Acciones: `CreatePost`, `DeletePost`, `TogglePostReaction`, `CreatePostComment`, `ToggleFollowBusiness`.
- Evento `PostPublished` + Job `NotifyFollowersOfNewPost` + notificación `NewPostFromFollowedBusiness` (canal `database` + `PushChannel` ya existente) — mismo patrón `afterCommit()` que la integración de Google Merchant. **La vista de Actividad del comprador (`clientes.actividad`) no necesitó ningún cambio** — ya renderiza cualquier notificación de forma genérica.
- `Business::posts()`/`follows()`/`isFollowedBy()`, `User::following()`/`isFollowing()` — relaciones nuevas sobre modelos existentes, sin tocar sus migraciones (excepto la tabla `follows`, que sí es nueva).
- `Report::reportableLabel()` reconoce `Post` (moderación admin lista para cuando se agregue el botón "Reportar" en el feed).

### UI pública
- `/feed` (`FeedController`), pestañas **Recientes** (filtra por municipio preferido, mismo mecanismo de cookie que el resto del sitio) y **Siguiendo** (requiere sesión). Tarjeta de post: negocio + ícono, cuerpo, fotos, productos etiquetados (enlazan al producto real, nunca lo duplican), botón de reacción, WhatsApp, guardar (reutiliza `livewire:favorite-button`), comentarios colapsables.
- Botón "Seguir" (`livewire:follow-button`, mismo patrón que `favorite-button`) agregado a la vitrina pública, junto a "Guardar".
- Nav "Feed" agregado al sidebar del Cliente (desktop). **No se agregó a la barra inferior móvil** — ya tiene 5 accesos y la Fase 23 del TODO pide explícitamente "evitar sobrecargar pantallas"; es una decisión de producto (qué quitar para hacerle espacio), no de código.

### UI del emprendedor
- `emprendedores/negocios/{business}/publicaciones` (nuevo, mismo patrón de autorización que "Productos": `business.team` + `$this->authorize('update', $business)`) — crear post (tipo, texto, fotos, etiquetar productos) y listar/eliminar los propios.
- Nav "Publicaciones" agregado al sidebar del Emprendedor, junto a "Productos".

## 2. Verificado

- 14 tests nuevos (`tests/Feature/Social/PostsTest.php` × 11, `PublicationsPageTest.php` × 3), todos en verde.
- Suite completa: **777 passed / 34 failed** (mismos 34 fallos preexistentes de siempre, ninguno relacionado — antes de este sprint eran 763/34).
- Verificado manualmente con Playwright de punta a punta (negocio + post de prueba, creados y limpiados después): feed público, reacción, comentario, y la página de Publicaciones del panel — sin errores de consola.
- Pint sin cambios de estilo pendientes.

## 3. Hallazgo relevante para sprints futuros

Al revisar `routes/web.php` para agregar la ruta de `feed`, aparecieron controladores ya existentes: `App\Http\Controllers\Billing\CheckoutController`, `PaymentSourceController`, `WompiWebhookController` — **hay infraestructura de pagos/checkout ya construida** (`config/services.php` ya tenía una sección `wompi` completa desde antes de esta sesión). Esto es muy relevante para el Sprint 5 (Checkout) y 6 (Suscripciones): antes de construir nada ahí, hay que auditar qué tan completo está esto — probablemente cambie bastante el alcance real de esos sprints, igual que pasó con el Sprint 1.

## 4. Siguiente paso sugerido

Sprint 3 (`TODO_social.md`, Fase 28): Estados (contenido efímero de 24h), notificaciones sociales adicionales, integración de productos en posts/estados (ya cubierto en parte por este sprint). Pedir confirmación de alcance antes de arrancarlo.
