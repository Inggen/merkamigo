# Merkamigo

> Descubre lo local, conecta con tu comunidad.

Merkamigo reúne negocios, compradores y comunidades en una plataforma de comercio local. Permite crear vitrinas digitales, vender productos y servicios, publicar contenido, gestionar eventos y reservas, responder solicitudes, conversar con clientes y ofrecer recompensas. Incluye administración interna, una API versionada y experiencias inmersivas municipales. ♥️

Desarrollado por **Inggen**. Sitio de producción: [merkamigo.com](https://merkamigo.com).

**Características revisadas al 13 de octubre de 2026.** Este documento describe las capacidades implementadas en el repositorio. La disponibilidad de cada módulo depende del plan, los permisos, las banderas de activación y las integraciones configuradas; tener código implementado no implica que todos los servicios externos estén habilitados en producción.

## Características de la plataforma

### Descubrimiento y comunidad local

- Directorio de negocios por municipio, categoría y zona, con búsqueda, filtros y cercanía geográfica.
- Plazas municipales y páginas públicas de categorías, negocios y productos.
- Municipio preferido, negocios vistos recientemente y favoritos.
- Feed comunitario con publicaciones, imágenes y videos, reacciones y comentarios.
- Seguimiento de negocios y notificaciones de sus novedades.
- Estados, reels y directorio de transmisiones en vivo.
- Agenda pública de eventos y catálogo de recompensas Merkamigo Premia.
- Navegación adaptada a escritorio y móvil, selector de experiencia Cliente/Emprendedor y bienvenida para crear una vitrina.

### Vitrinas y catálogo

- Creación guiada, edición, vista previa, publicación y despublicación de vitrinas.
- Identidad del negocio, logo, portada, descripción, categoría, ubicación e información de contacto.
- Gestión de múltiples negocios y colaboradores desde una misma cuenta.
- Productos y servicios con imágenes, medios, precios, categorías y variantes.
- Productos digitales con archivos asociados y entrega mediante los flujos de compra autorizados.
- Duplicación de productos y traslado entre negocios con controles de autorización.
- Páginas individuales de productos y QR para compartir vitrinas y productos.
- Contacto por WhatsApp y mensajería interna, con registro de interacciones.
- Límites de uso y capacidades según el plan y los servicios contratados.

### Mensajería y asistentes de IA

- Conversaciones entre clientes y negocios, envío de mensajes y gestión de conversaciones.
- Asistente general de Merkamigo y chatbot configurable por negocio.
- Perfil del asistente, documentos de conocimiento, historial de conversaciones y resúmenes.
- Generación asistida de descripciones de vitrinas y productos, portadas e imágenes de productos.
- Textos comerciales para contenido social y sugerencias de contenido para WhatsApp.
- Configuración de OpenAI y de la base de conocimiento de la plataforma desde administración.

Las funciones de IA requieren credenciales y modelos configurados. La interfaz de transcripción de audio tiene una implementación nula de respaldo; no se presenta como un servicio de transcripción activo.

### Pídelo en Merkamigo

- Publicación de necesidades y solicitudes locales con medios adjuntos y borradores.
- Ofertas de negocios, consulta de propuestas, preselección y retiro de ofertas.
- Cierre, cancelación y vencimiento de solicitudes.
- Oportunidades para emprendedores y métricas del embudo de necesidades y ofertas.

### Ventas, cobros y suscripciones

- Checkout de productos con Wompi, pedidos y detalle de artículos vendidos.
- Conexión de credenciales Wompi por negocio: el pago del comprador se procesa con la cuenta del negocio.
- Retornos de checkout y webhooks para aplicar pagos aprobados.
- Paneles de ventas, pedidos y compras del cliente, incluidos productos digitales.
- Comisiones de plataforma, acumulación, cobro, reversión y seguimiento administrativo.
- Checkout de invitados para productos digitales, controlado por `MARKETPLACE_GUEST_CHECKOUT_ENABLED` y desactivado por defecto.
- Planes de plataforma, cupones, productos de facturación y servicios adicionales.
- Fuentes de pago, renovación de planes y servicios recurrentes, cancelaciones y aplicación de bajas de plan.
- Suscripciones de clientes a productos del negocio, periodos de prueba, renovaciones y derechos de acceso.

La configuración de Wompi admite sandbox y producción. Los cobros reales requieren las credenciales correspondientes y las tareas programadas activas.

### Eventos, reservas y check-in

- Agenda y detalle público de eventos, publicación de eventos en el feed.
- Gestión de espacios, platos, equipos y tarifas para reservas.
- Horarios de atención, disponibilidad, fechas bloqueadas y cálculo de cotizaciones.
- Reservas con retención temporal de disponibilidad, pago en línea y liberación por vencimiento.
- Inscripciones y entradas para eventos públicos, pagos y códigos QR.
- Check-in con lectura de QR mediante cámara y seguimiento de reservas, pagos y asistentes.
- Configuración de piloto por negocios mediante `EVENTS_PILOT_MODE` y `EVENTS_PILOT_BUSINESS_IDS`.

### Merkapuntos y Merkamigo Premia

- Adhesión voluntaria de negocios al programa, revisión administrativa y publicación de políticas.
- Cuentas, movimientos y registro o reversión de compras que otorgan puntos.
- Identificación del cliente mediante QR y tokens.
- Recompensas, sugerencias para su cálculo y catálogo público de Premia.
- Reserva, entrega y cancelación de canjes; liberación de puntos y recursos cuando vence la reserva.
- Solicitudes con comprobantes de compra y revisión por empleados autorizados.
- Notificaciones de acumulación de puntos, entrega y liberación de canjes.

El interruptor global `LOYALTY_ENABLED` está desactivado por defecto. El vencimiento de puntos no se considera una capacidad activa; requiere una política e implementación específicas.

### Live Shopping

- Creación y gestión de transmisiones, estudio de emisión y comprobación de señal.
- Publicación desde navegador u OBS, reproducción web y proxy HLS a través del dominio de la aplicación.
- Destinos de retransmisión y conexión con YouTube mediante OAuth.
- Video de introducción, productos durante el live y checkout asociado a la transmisión.
- Mensajes, reacciones, encuestas, votos, registro de visualizaciones y métricas de control.

La emisión real requiere MediaMTX, FFmpeg y conectividad de streaming. La existencia del estudio y del reproductor no confirma por sí sola la disponibilidad de esa infraestructura.

### Experiencias inmersivas

- Plazas municipales inmersivas y experiencias versionadas.
- Zonas, stands y asignación de negocios a espacios virtuales.
- Plantillas de objetos, editor espacial y generación asistida por IA.
- Elementos de escena, anuncios y contenido de las plazas.
- Herramientas para generar distribuciones a partir de imágenes y detectar colores de leyendas.
- Registro de eventos de interacción y auditoría de rendimiento de las experiencias.

### Confianza, soporte y moderación

- Solicitud y revisión de verificación de negocios, con recordatorios de vencimiento.
- Confirmación de pedidos, finalización, cancelación y disputas.
- Opiniones, recomendaciones y moderación de recomendaciones.
- Reportes de negocios, productos y contenido; suspensión y restauración de entidades.
- Tickets de soporte, resolución administrativa y páginas de ayuda, privacidad y términos.
- Auditoría de acciones sensibles e impersonación de usuarios para soporte autorizado.

### Métricas y administración

- Visitas, clics de WhatsApp, interacciones sociales, desempeño de productos y promociones.
- Embudos de conversión, necesidades/ofertas y métricas legibles por negocio.
- Informes semanales y detección de vitrinas incompletas o inactivas.
- Panel interno Filament en `/admin`, restringido por roles de plataforma.
- Gestión de usuarios, negocios, productos, municipios, categorías, planes, pagos, suscripciones, comisiones, reportes y tickets.
- Administración de experiencias inmersivas, Merkapuntos, configuración del sitio e integraciones.

### Integraciones, SEO y notificaciones

- Registro e ingreso, recuperación de contraseña, verificación de correo y teléfono, doble factor y passkeys.
- Acceso social con Google y Facebook cuando sus clientes OAuth están configurados.
- Notificaciones en la plataforma, correo y push mediante Firebase Cloud Messaging HTTP v1.
- Registro y revocación de dispositivos y preferencias de notificación.
- Correo mediante transportes Laravel SMTP, incluidos proveedores como Brevo.
- Google Merchant: feed XML, validación y mapeo de productos, sincronización y conciliación programada.
- Sitemap, metadatos, URL canónica y datos estructurados.
- Descubrimiento para agentes mediante `llms.txt`, catálogo de API y documentación OpenAPI.
- API `/api/v1`, autenticación Sanctum y suscripciones a webhooks salientes.

## Stack técnico

Las versiones PHP de la aplicación están fijadas en `composer.lock`; las dependencias de JavaScript, en `package-lock.json`.

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3+ y Laravel 13.23 |
| Administración | Filament 5.7 |
| Interfaz | Blade, Livewire 4.3 y Flux 2.15 |
| Autenticación | Fortify, Sanctum, Socialite y passkeys |
| Permisos | Spatie Laravel Permission con equipos por negocio |
| Estilos y compilación | Tailwind CSS 4 y Vite 8 |
| Base de datos | MySQL/MariaDB; SQLite para pruebas cuando corresponda |
| Cola y caché | Database por defecto en `.env.example`; Redis configurable |
| Integraciones | Wompi, Firebase, OpenAI, Google Merchant, YouTube y SMTP |
| Streaming y QR | MediaMTX/FFmpeg, HLS.js y ZXing |
| Calidad | Pest, Pint, Larastan/PHPStan y pruebas JavaScript |

## Instalación local

Requisitos: PHP 8.3+ con las extensiones exigidas por Composer, Composer 2, Node.js compatible con Vite 8 (20.19+ o 22.12+) y una base de datos configurada. Redis es necesario si se elige como almacenamiento de caché o cola.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Crear la base de datos y completar `DB_*` en `.env`. Después:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan app:make-superadmin admin@merkamigo.test
```

Con Laravel Herd, el directorio del proyecto se sirve en `https://merkamigo.test`. Sin Herd, iniciar `php artisan serve` y ajustar `APP_URL`. Para desarrollo de assets, ejecutar `npm run dev`.

Los trabajos asíncronos y las tareas programadas necesitan sus propios procesos:

```bash
php artisan queue:work --tries=3
php artisan schedule:work
```

No copiar credenciales de producción a un entorno compartido. En local, `MAIL_MAILER=log` permite revisar correos sin enviarlos y `SMS_OTP_DRIVER=log` registra los OTP sin un proveedor de SMS.

## Configuración de integraciones

Usar [`.env.example`](.env.example) como base y consultar los archivos de `config/`. Nunca incorporar `.env`, claves privadas o archivos de cuentas de servicio al repositorio.

| Integración | Configuración principal |
|---|---|
| Base de datos, cola y caché | `DB_*`, `QUEUE_CONNECTION`, `CACHE_STORE` |
| Correo | `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_*` |
| Firebase | `FCM_PROJECT_ID`, `FCM_CREDENTIALS`, `FIREBASE_WEB_*` |
| Wompi | `WOMPI_*`; conexión adicional por negocio para marketplace |
| OpenAI | Configuración administrativa y documentos de conocimiento |
| OAuth | `GOOGLE_*`, `FACEBOOK_*` y sus URL de retorno |
| Google Merchant | `GOOGLE_MERCHANT_*` |
| Streaming | `LIVE_STREAMING_*` y servicios MediaMTX/FFmpeg |
| Eventos | `EVENTS_PILOT_MODE`, `EVENTS_PILOT_BUSINESS_IDS` |
| Merkapuntos | `LOYALTY_ENABLED`, `LOYALTY_REDEMPTION_TTL_MINUTES`; ver `config/loyalty.php` |
| Almacenamiento | `FILESYSTEM_DISK`, `AWS_*` y configuración S3-compatible |

### Correo con Brevo

Se utiliza el transporte SMTP nativo de Laravel; no hace falta un SDK adicional:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=USUARIO_SMTP_DE_BREVO
MAIL_PASSWORD=CLAVE_SMTP_DE_BREVO
MAIL_FROM_ADDRESS=hola@merkamigo.com
MAIL_FROM_NAME="Merkamigo"
```

Verificar el remitente y autenticar el dominio en Brevo. Después de cambiar la configuración, limpiar o reconstruir la caché y reiniciar los workers. La autenticación SMTP y la aceptación de un mensaje no equivalen a confirmar su recepción en la bandeja del destinatario.

### Push web y móvil

Configurar tanto la cuenta de servicio del backend como el cliente web y su clave VAPID. El JSON privado debe permanecer fuera de `public/`, por ejemplo en `storage/app/private/firebase-service-account.json`.

El navegador necesita HTTPS, permisos de notificación y el service worker registrado. En iPhone/iPad, el flujo exige abrir la web instalada en la pantalla de inicio en una versión compatible de iOS. Mantener instalado el conjunto completo de dependencias de Composer, incluidas Google Auth y Firebase PHP-JWT, y tener un worker de cola activo.

## Arquitectura y autorización

La lógica se organiza en `app/Domain/{Módulo}` con acciones, modelos, políticas, eventos, jobs y notificaciones. Las acciones se reutilizan desde controladores, componentes Livewire, Filament y API para evitar duplicar reglas.

Módulos actuales: `Identity`, `Businesses`, `Storefronts`, `Discovery`, `Needs`, `Trust`, `WhatsApp`, `Analytics`, `Billing`, `Marketplace`, `Subscriptions`, `Messaging`, `Social`, `Events`, `Loyalty`, `Immersive`, `Moderation` y `Platform`.

- **Presentación:** `resources/views`, `resources/css` y `resources/js`; separar reglas de negocio del diseño para permitir personalizar temas.
- **Administración:** `app/Filament`, con roles `moderator`, `admin` y `superadmin`.
- **API:** `routes/api.php`, respuestas estandarizadas y Sanctum.
- **Integraciones:** clientes y contratos en `app/Support`.
- **Multinegocio:** organizaciones, negocios y membresías; roles `owner`, `admin` y `collaborator` por equipo de negocio.
- **Autorización:** políticas y middleware `business.team`; auditoría de operaciones sensibles.

## Rutas principales

| Ruta | Uso |
|---|---|
| `/` | Comunidad y feed |
| `/explorar`, `/plaza/{municipio}` | Descubrimiento local |
| `/m/{negocio}` | Vitrina pública |
| `/emprendedores/bienvenida`, `/emprendedores` | Bienvenida y panel del emprendedor |
| `/pidelo` | Necesidades y oportunidades |
| `/mensajes` | Mensajería |
| `/eventos` | Agenda pública |
| `/premia`, `/merkapuntos` | Recompensas y cuenta de puntos |
| `/reels`, `/en-vivo` | Contenido y transmisiones |
| `/clientes/pedidos`, `/clientes/compras` | Seguimiento de compras |
| `/exp/plaza/{municipio}` | Experiencia inmersiva |
| `/admin` | Administración de plataforma |
| `/api/v1`, `/docs/api/reference` | API y documentación |
| `/feeds/google-merchant.xml`, `/sitemap.xml`, `/llms.txt` | Descubrimiento externo |

Los nombres de parámetros son descriptivos; consultar `routes/web.php` para los bindings, restricciones y middleware exactos.

## Operación y despliegue

El servidor web debe apuntar a `public/`. Preparar un respaldo de base de datos y archivos antes de desplegar, instalar las dependencias completas y revisar las migraciones de la release:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

Mantener un worker con Supervisor u otra solución del hosting. En un hosting con cron puede usarse un worker de duración limitada, evitando ejecuciones simultáneas. La cola de trabajos y el scheduler cumplen funciones distintas: ejecutar uno no reemplaza al otro.

```cron
* * * * * /ruta/php /ruta/merkamigo/artisan schedule:run >> /dev/null 2>&1
```

`routes/console.php` programa expiraciones de solicitudes, reservas, entradas y canjes; renovación de planes, servicios y suscripciones; cobro de comisiones; informes; limpieza de métricas y sincronización de Google Merchant. Revisar sus horarios y banderas antes de activarlo con datos reales.

Configurar `APP_DEBUG=false`, HTTPS, almacenamiento de medios y backups por entorno. Las notas originales de arquitectura y despliegue describen etapas anteriores; sus pendientes históricos no deben confundirse con el inventario actual de funciones de este README. La disponibilidad de streaming, SMS, almacenamiento externo y entrega de notificaciones debe verificarse en cada entorno.

## Pruebas y calidad

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
node --test tests/JavaScript/*.test.mjs
npm run build
```

`composer test` reúne las comprobaciones PHP definidas en `composer.json`. `composer hooks:install` instala el hook local. `npm run audit:immersive` ejecuta la auditoría de experiencias inmersivas.

Las pruebas locales no sustituyen la validación del navegador, webhooks, pagos, emisión de video o recepción real de correo y push en producción.

## Documentación y evolución

- [Plan maestro](TODO_MERKAMIGO.md).
- [Eventos y reservas](TODO_desarrollo_sistema_eventos_Merkamigo.md).
- [Merkapuntos](TODO_Merkapuntos.md).
- [Contenido social](TODO_social.md) y [Live Shopping](TODO_social_live_shopping.md).
- [Marketplace](TODO-Marketplace-Checkout.md) y [ventas y rentabilidad](TODO_VENTAS_RENTABILIDAD.md).
- [Planes y servicios](TODO_Merkamigo_Planes_Servicios_v2.md).
- [Experiencias inmersivas](TODO-Merkamigo-Experiencia-Inmersiva.md).
- [Google Merchant](docs/google-merchant.md).
- [Manual de Pídelo](docs/manuales/pidelo-en-merkamigo.md).
- [Flujos de usuario](docs/ux-flows/README.md), [diseño](docs/design-system/README.md) y [API OpenAPI](docs/api/openapi.yaml).
- [Decisiones de arquitectura](docs/architecture/decisiones.md) y [estrategia de ramas](docs/architecture/estrategia-ramas.md).

Los TODO son la fuente de trabajo pendiente de cada módulo. La aplicación híbrida Ionic/Vue/Capacitor continúa como evolución prevista; no se presenta aquí como una aplicación móvil nativa ya entregada.

## Contribución

Revisar las habilidades del repositorio antes de trabajar y aplicar las relevantes al alcance:

- [Desarrollo de aplicaciones](.github/skills/desarrollo-aplicaciones/SKILL.md).
- [Diseño UI/UX](.github/skills/diseno-uiux/SKILL.md).
- [Revisión de seguridad](.github/skills/revision-seguridad/SKILL.md).

Usar convenciones de Laravel y Filament, reutilizar acciones de dominio y mantener las soluciones simples. Validar autorización, aislamiento entre negocios y criterios de aceptación; actualizar la documentación del módulo y seguir la Definition of Done del plan maestro.
