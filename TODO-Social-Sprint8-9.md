# TODO-Social-Sprint8-9.md — Seguimiento de ejecución

**Fecha:** 16 de septiembre de 2026  
**Fuente de verdad:** `TODO_social.md`, Sprints 8 y 9.

## Construido

### Sprint 8 — Live Commerce

- Dominio reusable: `LiveStream`, productos asociados, espectadores activos, mensajes y reacciones.
- Panel del negocio en `/emprendedores/negocios/{business}/lives` para preparar, iniciar, fijar/cambiar producto y finalizar.
- Reproducción pública en `/en-vivo/{slug}` mediante YouTube, Vimeo o video externo; Merkamigo no opera infraestructura propia de streaming.
- Contador de espectadores activos, chat autenticado, reacciones, compartir y seguir negocio.
- Notificación a seguidores cuando inicia el Live.
- Producto fijado con precio, disponibilidad y acceso al checkout real del negocio en una pestaña separada, sin detener el Live.
- Replay opcional que conserva todos los productos y sigue permitiendo comprar.
- Live activo visible en el feed principal.

### Sprint 9 — Métricas e IA

- Panel existente ampliado; no se creó un dashboard paralelo.
- Periodos de 7, 30 y 90 días para resumen, embudo y productos.
- Rendimiento unificado de publicaciones, estados, reels y Lives: visualizaciones e interacciones.
- Registro de impresiones sociales reutilizando `analytics_events` y su deduplicación/filtrado de bots.
- Acción única `GenerateSocialSalesCopy`, reutilizada por Publicaciones, Estados, Reels y Lives.
- La IA usa solamente catálogo y datos reales, genera borradores editables y nunca publica automáticamente.
- Promoción pagada de producto, post, estado y Live reutilizando los paquetes y precios administrables de destacado.
- Segmentación por municipio, categoría y radio; contenido patrocinado priorizado e identificado en el feed.
- Atribución de impresiones, clics y ventas visible en Métricas.

## Pendiente deliberado

- Elegir un proveedor administrado de streaming y aprobar su costo antes de integrar creación automática de canales/llaves.
- Checkout embebido dentro del mismo DOM del Live: por seguridad se mantiene Wompi hospedado en una pestaña aparte, conservando la transmisión abierta.
- Variantes IA específicas para WhatsApp, Instagram y Facebook desde el mismo borrador social.

## Verificación

- Cobertura automatizada del ciclo completo del Live, aislamiento de productos, notificación, replay, chat y reacciones.
- Cobertura de métricas sociales, periodos e IA sin autopublicación.
- Cobertura de preparación, pago, activación y atribución de promociones de contenido.
- Suite focal de Social, Analytics, Métricas, IA y catálogo de cobros en verde.
