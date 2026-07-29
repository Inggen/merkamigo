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
