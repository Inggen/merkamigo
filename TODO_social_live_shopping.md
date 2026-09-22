# TODO SOCIAL — Actualización Live Shopping / Multistream

## Objetivo

Convertir la funcionalidad **En vivo** de Merkamigo en una experiencia de **Live Shopping**, donde un negocio pueda:

- Transmitir en vivo desde Merkamigo.
- Mostrar y destacar productos durante la transmisión.
- Permitir que los usuarios compren sin abandonar el Live.
- Completar el pago mediante una modal / bottom sheet.
- Transmitir simultáneamente hacia redes sociales mediante RTMP/RTMPS.
- Utilizar Facebook, Instagram, TikTok, YouTube y otros canales compatibles como fuentes de audiencia.
- Mantener Merkamigo como centro de conversión, productos, carrito, pagos y métricas.

## 1. Arquitectura general del Live

### Flujo esperado

Cámara / navegador / móvil / OBS  
↓  
Merkamigo Live Ingest  
↓  
Servicio de streaming  
↓  
Player Live de Merkamigo + productos + chat + ofertas + carrito + checkout  
↓  
Multistream hacia Facebook / Instagram / TikTok / YouTube / destinos RTMP compatibles

## 2. Crear un Live

- [x] Crear opción **"Iniciar en vivo"** dentro de Mi Negocio.
- [x] Crear vista de configuración previa a la transmisión.
- [ ] Permitir ingresar título, descripción, portada, categoría, municipio y fecha/hora. (Título, descripción, portada y fecha/hora listos; faltan categoría y municipio.)
- [x] Permitir seleccionar productos de la vitrina que estarán disponibles durante el Live.
- [ ] Permitir seleccionar promoción u oferta del Live.
- [ ] Permitir definir descuento especial y duración.
- [x] Mostrar vista previa antes de iniciar.
- [x] Usar la portada programada en el Live, el feed y las etiquetas sociales del enlace.
- [x] Botón principal: **Iniciar transmisión**.

## 3. Origen de la transmisión

Merkamigo debe actuar como origen/controlador principal del Live.

- [x] Permitir transmisión directamente desde navegador.
- [x] Permitir transmisión desde dispositivo móvil.
- [x] Preparar soporte para cámaras y micrófonos.
- [x] Preparar ingreso de señal desde OBS u otro encoder.
- [x] Generar URL de ingestión.
- [x] Generar Stream Key segura.
- [x] Nunca mostrar Stream Key públicamente.
- [x] Permitir regenerar Stream Key.
- [x] Detectar automáticamente inicio y pérdida de señal.
- [x] Estados: Esperando señal / Conectando / En vivo / Reconectando / Finalizado.

Implementado con MediaMTX como origen propio, autenticación HTTP contra Laravel,
credenciales cifradas y detección mediante la Control API. Falta desplegar el
nodo en infraestructura para validarlo con señal real fuera del entorno local.

## 4. Multistream hacia redes sociales

La transmisión debe enviarse una sola vez a Merkamigo y Merkamigo distribuirla a diferentes destinos.

- [x] Crear módulo **Destinos de transmisión**.
- [x] Permitir habilitar/deshabilitar destinos individualmente.
- [x] Soportar destinos RTMP/RTMPS.
- [x] Facebook. *(Relay de video/audio verificado localmente; la publicación final sigue requiriendo completar los datos del Live en Facebook.)*
- [ ] Instagram cuando esté disponible para la cuenta.
- [ ] TikTok cuando la cuenta tenga acceso a transmisión externa.
- [x] YouTube. *(Relay verificado con conexión excelente y señal visible en YouTube Studio.)*
- [ ] RTMP personalizado.

Por cada destino:

- [x] Nombre.
- [x] URL RTMP/RTMPS.
- [x] Stream Key.
- [x] Estado de conexión.
- [x] Activar / desactivar.
- [ ] Probar conexión.
- [x] Guardar configuración cifrada.

La configuración y protección de destinos ya están listas. El envío simultáneo
real y la prueba de conexión permanecen pendientes hasta desplegar el nodo y su
worker de relé; no se consideran cumplidos únicamente por guardar credenciales.

Actualización local 19 sep 2026: el relay MediaMTX → FFmpeg → RTMP/RTMPS ya
retransmite la señal real a Facebook y YouTube. El estudio compone en una sola
pista la cámara, inversión, filtros, video inicial, producto destacado,
encuestas y reacciones. Feed, Live público y destinos externos consumen esa
misma composición sin volver a invertirla ni aplicarle filtros. La instancia
WebRTC se conserva durante las actualizaciones Livewire y se reconecta ante una
caída real.

- [x] Incrustar producto destacado en la señal enviada a destinos externos.
- [x] Incrustar reacciones de Merkamigo en la señal enviada a destinos externos.
- [x] Incrustar la encuesta activa y sus resultados en la señal enviada a destinos externos.
- [x] Incrustar el video de inicio en la señal principal y en los destinos externos, incluso con la cámara apagada.
- [x] Aplicar inversión horizontal y filtros de cámara a la señal retransmitida, no solo a la previsualización local.
- [x] Evitar doble inversión/filtro y mantener resolución 1280×720 en feed, Live público y multistream.
- [x] Mantener la publicación activa al mostrar productos, encuestas o reacciones desde Livewire.
- [ ] Unificar comentarios de Facebook y YouTube mediante OAuth y sus APIs oficiales.
- [ ] Evaluar comentarios LIVE de TikTok cuando exista acceso oficial aprobado para la cuenta/app.

Estados:

- Conectado.
- Transmitiendo.
- Error.
- Desconectado.

**Importante:** Merkamigo no debe depender de las redes sociales para reproducir el Live principal. Las redes sociales se usan como canales de alcance y Merkamigo como canal de conversión.

## 5. Player Live Shopping

### Header

- [x] Foto/logo del negocio.
- [x] Nombre.
- [x] Municipio.
- [x] Badge 🔴 EN VIVO.
- [x] Número de espectadores.
- [x] Compartir.
- [x] Cerrar / volver.

### Área de video

- [x] Video vertical prioritario 9:16.
- [x] Soporte responsive.
- [x] Pantalla completa.
- [x] Mute/unmute.
- [x] Estado de conexión.
- [x] Reconexión automática.

## 6. Producto destacado durante el Live

El vendedor debe poder seleccionar en tiempo real qué producto está promocionando.

- [x] Actualización en tiempo real sin recargar el player. *(Sincronización Livewire cada 10 segundos.)*
- [x] Imagen.
- [x] Nombre.
- [x] Precio.
- [x] Precio anterior.
- [x] Descuento.
- [ ] Stock disponible.
- [x] Botón Comprar ahora.
- [x] Botón agregar al carrito.

## 7. Panel del vendedor durante el Live

Crear un **Live Control Center**.

- [x] Ver duración del Live.
- [x] Ver espectadores.
- [x] Ver productos seleccionados.
- [x] Iniciar el Live sin mostrar automáticamente ningún producto.
- [x] Destacar un producto.
- [x] Quitar producto destacado.
- [x] Cambiar producto destacado.
- [ ] Activar oferta.
- [ ] Cambiar precio promocional.
- [ ] Activar cuenta regresiva.
- [ ] Mostrar cupón.
- [ ] Crear combo.
- [x] Consultar comentarios.
- [x] Responder el chat desde el estudio sin salir de la transmisión.
- [x] Previsualizar en el estudio la misma interfaz vertical que ve la audiencia.
- [x] Mantener una previsualización compacta para dar más espacio a los controles.
- [x] Copiar, compartir y abrir el Live público directamente desde el estudio.
- [x] Detener y finalizar la transmisión desde el estudio, incluso después de recargarlo.
- [x] Subir y activar/desactivar rápidamente un video de inicio.
- [x] Crear y compartir encuestas con 2 a 4 opciones desde el estudio.
- [x] Guardar hasta 5 encuestas por Live y mostrar u ocultar cualquiera durante la transmisión.
- [x] Permitir votar y actualizar porcentajes dentro del Live.
- [x] Ocultar la encuesta desde el estudio sin borrar sus votos ni su configuración.
- [x] Ocultar y mostrar rápidamente la encuesta sobre la previsualización.
- [ ] Ver cantidad de clics.
- [ ] Ver productos agregados al carrito.
- [x] Ver pedidos realizados.
- [x] Ver ventas generadas durante el Live.

## 8. Compra sin salir del Live

Este requerimiento es prioritario.

Al pulsar **Comprar ahora**:

- Desktop: abrir modal.
- Mobile: abrir Bottom Sheet.
- El video debe permanecer activo detrás.

## 9. Quick Product Modal

- [x] Imagen.
- [x] Nombre.
- [x] Descripción corta.
- [x] Precio.
- [x] Precio anterior.
- [x] Descuento.
- [ ] Rating/reseñas si aplica.
- [x] Variantes.
- [ ] Color.
- [ ] Talla.
- [ ] Sabor.
- [x] Presentación.
- [x] Cantidad.
- [x] Disponibilidad.
- [ ] Stock.
- [ ] Oferta especial del en vivo con contador.
- [x] Botón Agregar al carrito.
- [x] Botón Comprar ahora.

## 10. Carrito del Live

- [x] Mantener carrito sin salir del video.
- [x] Permitir comprar varios productos presentados durante la transmisión.
- [x] Mostrar contador sobre icono de carrito.
- [x] Permitir modificar cantidades.
- [x] Eliminar producto.
- [x] Mostrar subtotal.
- [ ] Mostrar descuentos del Live.
- [ ] Aplicar cupón.

## 11. Checkout dentro del Live

Crear checkout rápido mediante modal / panel superpuesto.

Flujo recomendado:

1. Entrega.
2. Pago.
3. Confirmación.

No redireccionar al usuario a una página externa salvo que el proveedor de pagos lo exija.

## 12. Datos de entrega

- [ ] Dirección.
- [ ] Municipio.
- [ ] Barrio.
- [ ] Información adicional.
- [ ] Teléfono.
- [ ] Método de entrega.
- [ ] Domicilio.
- [ ] Recoger en negocio si aplica.
- [ ] Valor de envío.
- [ ] Mostrar resumen del pedido permanentemente.

## 13. Integración de pagos

Preparar arquitectura desacoplada del proveedor de pagos.

Métodos previstos:

- [ ] PSE.
- [ ] Tarjeta débito/crédito.
- [ ] Nequi.
- [ ] Otros métodos permitidos por la pasarela.

Estados:

- pending
- processing
- approved
- rejected
- cancelled
- refunded

**Importante:** confirmar pagos mediante webhook del proveedor.

## 14. Confirmación de compra

Cuando se confirme el pago:

- [ ] Mostrar `✅ ¡Compra realizada!`
- [ ] Mostrar número de pedido.
- [ ] Botón **Seguir viendo el Live**.
- [ ] Botón **Ver pedido**.
- [ ] El Live debe continuar reproduciéndose.

## 15. Ofertas exclusivas del Live

- [ ] Descuento porcentual.
- [ ] Precio fijo promocional.
- [ ] Stock limitado.
- [ ] Tiempo limitado.
- [ ] Combo especial.
- [ ] Cupón.
- [ ] Envío gratis.
- [ ] Regalo por compra.
- [ ] Finalización automática de oferta.

## 16. Chat e interacción

- [x] Chat en vivo.
- [x] Reacciones.
- [x] Likes.
- [x] Compartir.
- [ ] Responder mensajes.
- [ ] Moderación.
- [ ] Bloquear usuario.
- [ ] Reportar contenido.

## 17. Compartir Live

Generar URL pública:

`/live/{slug}`

Ejemplo:

`merkamigo.com/live/natusw`

Compartir hacia:

- [x] WhatsApp.
- [x] Facebook.
- [x] Instagram. *(Copia el enlace para publicarlo en la app.)*
- [x] TikTok. *(Copia el enlace para publicarlo en la app.)*
- [x] Copiar enlace.
- [ ] QR.

## 18. Integración con ubicación

Aprovechar Merkamigo Cerca.

- [ ] Priorizar Lives cercanos.
- [ ] Mostrar municipio.
- [ ] Mostrar distancia.
- [ ] Considerar categoría.
- [ ] Considerar intereses del usuario.

## 19. Feed Social

Agregar bloque:

### En vivo ahora

Cards horizontales con:

- avatar del negocio
- imagen/video preview
- nombre
- municipio
- audiencia
- producto destacado
- CTA **Ver en vivo**

## 20. Live programado

- [ ] Crear evento previamente.
- [ ] Cuenta regresiva.
- [ ] Recordatorios.
- [ ] Guardar Live.
- [ ] Notificación cuando comience.
- [ ] Compartir.

## 21. Replay comprable

Al finalizar:

- [ ] Mantener video.
- [x] Mantener productos.
- [x] Mantener momentos donde aparece cada producto.
- [x] Mantener botones de compra.
- [x] Mantener carrito.
- [ ] Mostrar productos relacionados.
- [ ] Desactivar ofertas expiradas.
- [ ] Permitir compra al precio normal.

## 22. Marcadores de producto en replay

Registrar timestamp cuando un vendedor destaca un producto.

Ejemplo:

- 00:03:18 — Brownie proteico
- 00:08:42 — Waffle proteico
- 00:14:20 — Combo saludable

- [ ] Permitir **Ver momento**.
- [ ] Hacer seek al timestamp correspondiente.

## 23. Métricas Live Commerce

### Audiencia

- [ ] Espectadores únicos.
- [ ] Espectadores simultáneos máximos.
- [ ] Duración media.
- [ ] Abandono.

### Producto

- [ ] Impresiones.
- [ ] Clics.
- [ ] Modal abierta.
- [ ] Agregado al carrito.
- [ ] Checkout iniciado.
- [ ] Compra.

### Venta

- [ ] Pedidos.
- [ ] Productos vendidos.
- [ ] Valor vendido.
- [ ] Ticket promedio.

### Conversión

views → product_click → add_to_cart → checkout → purchase

## 24. Dashboard posterior al Live

Mostrar:

- espectadores
- agregados al carrito
- compras
- valor vendido
- conversión
- productos más destacados

## 25. Modelo de datos sugerido

Crear según arquitectura existente:

- live_streams
- live_destinations
- live_products
- live_offers
- live_viewers
- live_events
- live_messages
- live_reactions
- live_product_events
- live_carts
- orders
- order_items
- payments
- live_metrics
- live_replays

Evitar duplicar productos. `live_products` debe relacionarse con productos existentes de la vitrina.

## 26. Eventos en tiempo real

- live.started
- live.ended
- viewer.joined
- viewer.left
- product.featured
- product.unfeatured
- offer.started
- offer.ended
- cart.added
- checkout.started
- payment.approved
- order.created

Utilizar WebSocket / sistema realtime existente cuando sea posible.

## 27. Estados del Live

- draft
- scheduled
- waiting
- live
- reconnecting
- ended
- processing
- replay
- cancelled

## 28. Seguridad

- [x] Stream Keys privadas.
- [x] Cifrar credenciales RTMP.
- [x] Autorización por negocio.
- [x] No permitir administrar Live de otro negocio.
- [x] Rate limit.
- [x] Validar stock desde backend.
- [x] Validar precio desde backend.
- [ ] Validar ofertas desde backend.
- [x] Confirmar pagos mediante webhook.
- [ ] Registrar auditoría de cambios de precio/oferta.

## 29. Responsive

### Mobile first

- [x] Live vertical 9:16.
- [x] Bottom Sheet para producto.
- [x] Bottom Sheet para carrito.
- [x] Video inmersivo a pantalla completa para el comprador móvil.
- [x] Accesos flotantes a productos, encuesta y compartir.
- [x] Chat superpuesto sobre el video en móvil.
- [x] Reacciones con emojis y animación ascendente sobre el Live.
- [x] Bottom Sheet para listado de productos y encuesta.
- [ ] Bottom Sheet para checkout.
- [ ] Evitar navegación entre páginas.

## 30. Experiencia visual Merkamigo

Seguir Manual de Marca.

Estilo:

- limpio
- profesional
- cercano
- comunitario
- minimalista
- menos es más

Colores:

- rojo principal
- rojo oscuro
- negro carbón
- grises neutros
- blanco

Foco visual:

1. Video.
2. Producto.
3. Comprar.

## 31. MVP PRIORITARIO

- [ ] Crear Live.
- [ ] Transmitir desde Merkamigo.
- [ ] Player 9:16.
- [ ] Vincular productos existentes.
- [ ] Destacar producto en tiempo real.
- [ ] Modal / Bottom Sheet de producto.
- [ ] Variantes.
- [ ] Cantidad.
- [ ] Carrito.
- [ ] Comprar ahora.
- [ ] Checkout.
- [ ] Pago.
- [ ] Confirmación sin salir del Live.
- [ ] Compartir Live.
- [ ] Métricas básicas.
- [ ] Replay comprable.

## 32. FASE 2

- [x] Multistream automático.
- [x] Facebook.
- [x] YouTube.
- [ ] TikTok.
- [ ] Instagram según disponibilidad.
- [ ] RTMP personalizado.
- [ ] Ofertas temporizadas.
- [ ] Cupones.
- [ ] Combos.
- [ ] Cuenta regresiva.
- [ ] Stock realtime.
- [ ] Chat.
- [ ] Reacciones.
- [ ] Recordatorios.
- [ ] Lives programados.
- [ ] Merkamigo Cerca.
- [ ] Analytics avanzado.

## 33. FASE 3 — IA

- [ ] Generar título del Live.
- [ ] Generar descripción.
- [ ] Sugerir productos.
- [ ] Sugerir orden de presentación.
- [ ] Crear ofertas.
- [ ] Generar guion de venta.
- [ ] Generar llamados a la acción.
- [ ] Detectar mejores momentos del Live.
- [ ] Crear clips automáticos.
- [ ] Crear publicaciones a partir del Live.
- [ ] Crear Estados Merkamigo.
- [ ] Crear textos para redes sociales.
- [ ] Generar resumen del Live.

## 34. Principio de producto

No construir simplemente una plataforma para hacer transmisiones.

Construir una plataforma para vender mientras se transmite.

Las redes sociales generan alcance.

Merkamigo genera:

DESCUBRIMIENTO  
↓  
INTERÉS  
↓  
PRODUCTO  
↓  
CARRITO  
↓  
PAGO  
↓  
VENTA

Todo sin sacar al comprador del Live.

## DEFINICIÓN FINAL

### Merkamigo Live Shopping

Una experiencia de comercio en vivo donde los negocios pueden presentar sus productos, interactuar con su comunidad y vender directamente durante una transmisión, permitiendo que el comprador descubra, seleccione, compre y pague sin abandonar el video.

**En vivo → producto → comprar → pagar → seguir viendo.**
