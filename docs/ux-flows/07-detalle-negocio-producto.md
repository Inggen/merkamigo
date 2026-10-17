# Detalle de negocio y producto

## Vitrina (`C04`, `/m/{slug-negocio}`, `resources/views/vitrinas/show.blade.php`)

Pestañas con estado Alpine (`x-data="{ tab: 'inicio' }"`, sin recargar página): Inicio, Productos, Opiniones e Información — exactamente la organización pedida en 1.3 del TODO.

- **Inicio:** portada, identidad, atributos administrables (p. ej. "Producto artesanal", "Hecho en Cajicá"), productos destacados y botón de WhatsApp.
- **Productos:** listado completo con tarjeta reutilizable (`resources/views/vitrinas/partials/product-card.blade.php`).
- **Opiniones:** oculta/estado vacío hasta que existan recomendaciones reales (Fase 3) — nunca se muestran estrellas o conteos ficticios.
- **Información:** horarios, estado abierto/cerrado calculado, redes sociales, información de pago externa (nunca datos de tarjeta), enlace/QR para compartir.

Acciones disponibles desde cualquier pestaña: guardar/quitar de favoritos, compartir, reportar contenido.

## Producto (`C05`, `/m/{slug-negocio}/productos/{slug-producto}`, `resources/views/vitrinas/product.blade.php`)

Imagen, descripción, precio (exacto, "desde", "consultar" o sin precio, según configuración del producto), variantes simples (porción/tamaño/presentación), disponibilidad, compartir, guardar y WhatsApp con mensaje específico del producto.

## Pendiente

- Insignia de verificación (Pasaporte de confianza) aparece cuando exista en Fase 3; hoy no se muestra porque todavía no hay verificaciones reales.

## Presentación de productos: Clásica / Elegante (2026-10-15)

En **Mi vitrina → Contenido → Vista de los productos**, el negocio elige:

- **Clásica** (predeterminada): conserva la galería y los controles actuales.
- **Elegante**: galería de fotos y video como fondo; miniaturas, título, datos del negocio, controles de compra y pestañas superpuestos.

La preferencia se guarda por vitrina en `storefronts.product_view_style`. Las acciones de compra, cantidad, suscripción, reseñas y métodos de pago se comparten entre ambos temas. No cambian las reglas ni el canal de contacto configurado.

Las tarjetas usan fondo `rgba(12, 12, 12, .4)`, borde `1px solid #ffffff20`, desenfoque y texto claro. El título no tiene fondo ni borde; usa sombra para reforzar la lectura. El control de reproducción/pausa aparece junto a Compartir cuando está seleccionado el video. Se revisó con fotografías claras/oscuras y fondos blancos/negros, en 1440 y 390 píxeles, además de cantidad, pagos, galería ampliada y reproducción/pausa del video. La preferencia de movimiento reducido impide el autoplay del video de fondo; el usuario puede reproducirlo manualmente.

La galería y cantidad se implementan en `resources/js/product-view.js`; los estilos elegantes están en `resources/css/pages/product-elegant.css` y la composición en `resources/views/vitrinas/partials/product-elegant.blade.php`.

Despliegue: aplicar la migración que agrega `product_view_style`, compilar assets, limpiar vistas y comprobar ambos temas. Las vitrinas existentes permanecen en Clásica hasta que su propietario cambie la opción.
