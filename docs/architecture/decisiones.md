# Decisiones de arquitectura — Fase 0 (fundación técnica)

## Matriz de versiones validada (2026-07-27)

| Paquete | Versión | Nota |
|---|---|---|
| PHP | 8.4.23 (Herd) | Cumple mínimo 8.3, preferido 8.4 |
| Laravel | 13.22.0 | |
| Filament | 5.7.3 | Requiere `livewire/livewire ^4.1` |
| Livewire | 4.3.3 | **Nunca combinar Filament 5 con Livewire 3** |
| Sanctum | 4.3.3 | Requiere Laravel ^11\|^12\|^13 |
| Tailwind CSS | 4.3.3 | |
| laravel-vite-plugin | 3.1.3 | Peer `vite ^8.0.0` |
| Node / npm | 22.23.1 / 10.9.8 | |

Se revalidará esta matriz antes de cada cambio mayor de versión.

## Alcance de este pase de Fase 0

El primer pase se limitó a la fundación técnica (0.3, 0.4, 0.5 y una porción mínima de 0.6). 0.1 y 0.2 se documentaron en versión breve (`docs/product/alcance-fase0.md`, `docs/product/sitemap.md`) reutilizando las decisiones ya escritas en `TODO_MERKAMIGO.md`, sin producir prototipos visuales todavía.

Un segundo pase cerró la mayor parte de lo restante de Fase 0: tokens de marca y logo aplicados (`resources/css/app.css`, `resources/views/components/app-logo-icon.blade.php`, `public/favicon.svg` y demás íconos), componentes de estado reutilizables (`resources/views/components/states/*`), navegación y layouts diferenciados para Cliente y Emprendedor con selector de experiencia (`App\Domain\Identity\Actions\SwitchExperience`), borrador de textos legales, estrategia de ramas, hook de calidad, stub de OpenAPI y política de versionado de API. El límite explícito de este segundo pase: no se construyó el contenido completo de las pantallas de Fase 1 (C01-C06, E01-E06) — solo la arquitectura (navegación, layouts, rutas placeholder) que esas pantallas usarán.

## Módulos del monolito modular

`Identity`, `Businesses`, `Storefronts`, `Discovery`, `Needs`, `Trust`, `WhatsApp`, `Analytics`, `Billing`, `Moderation`, `Platform` — cada uno bajo `app/Domain/{Modulo}/{Actions,Models,Policies,Events,Jobs,Notifications}`.

En este pase solo se implementan `Identity` (registro de usuarios) y `Businesses`/`Storefronts` (acción `CreateStorefront`). El resto de módulos quedan como carpetas con su convención, sin lógica, a la espera de su fase correspondiente. Las acciones de dominio que el TODO lista pero no pertenecen a este pase (`PublishNeed`, `SubmitOffer`, `ConfirmOrder`, `VerifyBusiness`, `GenerateWhatsAppPromotion`, `RegisterStoreView`, `RegisterWhatsAppClick`, `CalculateReadableMetrics`) se construyen en las fases donde el TODO las ubica (1 a 3).

## `storefronts` como entidad separada de `businesses`

`businesses` representa la entidad interna/legal (pertenece a una `organization`, tiene membresías y roles). `storefront` representa el contenido público de la vitrina (portada, estado de publicación de cara al comprador). Mantenerlas separadas evita mezclar datos internos de gestión con el contenido que se sirve públicamente, y es coherente con la distinción que el TODO hace entre negocio y vitrina en su modelo de datos (sección 5).

## Roles y permisos: `spatie/laravel-permission` con *teams*

El TODO pide roles por negocio (owner/admin/collaborator vía `business_memberships`) y roles de plataforma (moderador, administrador, superadministrador), además de tablas `roles`/`permissions` en el modelo de datos. En vez de construir un sistema de permisos a medida, se usa `spatie/laravel-permission`:

- La función *teams* del paquete se activa con `business_id` como team, de modo que un mismo usuario puede tener rol `owner` en un negocio y `collaborator` en otro sin conflicto.
- Los roles de plataforma (`moderator`, `admin`, `superadmin`) se asignan como roles globales del mismo paquete (sin team).
- `business_memberships` deja de duplicar el rol: solo registra el estado de la relación (invitado/activo/revocado); el rol vive en las tablas del paquete (`model_has_roles` con `team_id`).
- El paquete se integra de forma nativa con Filament 5 para restringir el panel `/admin` a roles de plataforma.

## Almacenamiento

Regla del TODO: nunca guardar fotos/audios/documentos de usuarios en disco local en producción. En este pase `config/filesystems.php` define el disco `s3` compatible listo por variables de entorno (sin credenciales reales todavía). Mientras no exista un bucket real, el entorno **local** usa el disco `local` solo para desarrollo — nunca se activará esta ruta en staging/producción.

## Verificación de teléfono

Se implementó el esquema de datos (`phone_verified_at`, `hasVerifiedPhone()`, `markPhoneAsVerified()`), pero **no** el flujo real de envío/validación de OTP: no hay proveedor de SMS contratado. Construir ese flujo (generar código, enviarlo por un proveedor, pantalla para ingresarlo) queda pendiente para cuando se elija un proveedor (Fase 5.4: "contratos internos para SMS sin acoplarse a un proveedor").

## Textos legales

`docs/legal/terminos.md`, `docs/legal/privacidad.md` y `docs/legal/reglas-comunidad.md` son un **borrador razonable, no revisado por un abogado**. Cubren tratamiento de datos personales (Ley 1581 de 2012 y Decreto 1377 de 2013 de Colombia), uso de WhatsApp, verificación y moderación. Antes de publicarlos como definitivos falta: revisión legal real, designar formalmente un responsable del tratamiento, y decidir cómo se registra la aceptación y versión de estos documentos por usuario (checkbox de registro, tabla de auditoría, etc. — todavía no implementado).

## Métricas comprensibles

El TODO pide `analytics_events` y `daily_business_metrics` como entidades separadas, con un job en cola para precalcular la segunda. Simplificaciones de este pase:

- No existe tabla `daily_business_metrics`: `CalculateReadableMetrics` agrupa `analytics_events` por día en el momento de la consulta (`GROUP BY DATE(created_at)`). Al volumen del piloto (dos municipios) es igual de rápido que leer una tabla precalculada y siempre queda conciliable con los eventos reales sin depender de que un job en cola haya corrido. Si el volumen crece, se puede introducir la tabla y un job sin cambiar la firma pública de la acción.
- El filtro de tráfico automatizado (`RegisterAnalyticsEvent`) es una heurística simple por substring de user-agent (googlebot, curl, python-requests, etc.), no un detector de bots real. Suficiente para el piloto; no debe presentarse como protección contra abuso deliberado.
- La deduplicación es por hash de IP+user-agent dentro de una ventana de 30 minutos, sin guardar la IP ni el user-agent en crudo (0.6 del TODO: no guardar más datos personales de los necesarios).
- El clic en "Compartir" se registra mediante un `fetch()` sin token CSRF (`POST /m/{slug}/compartir` está excluido de la verificación CSRF): es un beacon de analítica pública sin sesión, no muta datos sensibles, y ya está protegido por la deduplicación y el throttling generales de la app.

## Administración y moderación (Filament)

El TODO pide "motivos estandarizados y notificación al afectado" al suspender contenido, y "gestión de solicitudes de soporte". Simplificaciones de este pase:

- Los motivos de suspensión son un `Select` con opciones fijas (contenido inapropiado, información falsa, incumple reglas, solicitud del propietario, otro) — no texto libre, para mantenerlos comparables en auditoría.
- La "notificación al afectado" es un banner visible en el panel del Emprendedor (Inicio y editor de vitrina) con el motivo exacto de la suspensión, no un correo/SMS real: no hay proveedor de notificaciones contratado todavía (ver "Verificación de teléfono" arriba). Cuando se elija uno, `SuspendBusiness`/`SuspendProduct` son el punto único donde añadir el disparo de notificación real.
- Un negocio suspendido no puede auto-restaurarse: `PublishStorefront` lanza `BusinessSuspendedException` si el negocio está suspendido, y el editor de vitrina oculta el botón "Publicar" en ese estado. Solo un moderador/administrador puede restaurarlo desde Filament.
- No se construyó un sistema de solicitudes de soporte (modelo, bandeja, flujo de tickets): el contacto de soporte sigue siendo el enlace directo a WhatsApp de `/soporte`. Construir una bandeja real de tickets queda para cuando el volumen del piloto lo justifique.
- Tampoco existe una moderación de "imágenes" independiente de la del negocio/producto que las contiene — moderar una imagen individual implica suspender el producto o negocio completo, no hay un recurso `product_media` separado en Filament.
- El estado `pendiente_revision` existe en el enum de `businesses.status` desde la Fase 0, pero ningún flujo lo asigna todavía: el emprendedor se autopublica sin revisión previa (`PublishStorefront`), y la moderación actual es reactiva (suspender después de publicado), no una cola de aprobación previa.

## PWA y QA (1.10)

- Service worker mínimo (`public/sw.js`) registrado desde `resources/js/app.js`: solo cachea `offline.html` y sirve esa página cuando falla una navegación sin red. No cachea vitrinas, plaza ni el panel — no promete operación offline real, tal como pide el criterio de aceptación de 1.10.
- El manifest (`public/site.webmanifest`) y los íconos ya existían desde la Fase 0; con el service worker registrado, la app ahora cumple los criterios mínimos de instalabilidad de Chrome/Edge (manifest válido + service worker con `fetch`).
- "Pruebas de permisos, aislamiento y archivos" se consideran cubiertas por la suite automatizada existente (aislamiento multi-negocio en cada módulo, autorización por rol de plataforma, validación de tipo/tamaño de archivos en `MediaUploader`) — no por una ronda de pruebas manual adicional.

## Explícitamente diferido a una sesión posterior

- Revisión legal real de los textos de `docs/legal/`.
- Simulacro real de backup/restore.
- Entorno de staging/producción con hosting real (requiere que el usuario decida proveedor de hosting).
- Proveedor real de SMS/OTP, notificaciones (correo/SMS reales de moderación) y de almacenamiento S3 compatible.
- Logs centralizados y alertas (requiere un servicio externo tipo Sentry/Papertrail).
- Validación de precios e indicadores con usuarios reales del piloto.
- Prototipos visuales completos y design system con todos los componentes de Fase 1 — el logo, tokens de marca, tipografías (Poppins/Inter vía Bunny Fonts) y componentes de estado ya están aplicados, pero el contenido completo de cada pantalla de Fase 1 no se ha construido.
- Sistema de solicitudes de soporte (bandeja de tickets) y cola de aprobación previa a publicación (`pendiente_revision`).
- Pruebas manuales de responsive en dispositivos reales, matriz de navegadores soportados, accesibilidad con lector de pantalla, rendimiento (Lighthouse) y carga (k6/Apache Bench): requieren herramientas de QA y dispositivos que no están disponibles en este flujo de trabajo basado en agente de código; quedan como checklist manual para antes del piloto.
- Pruebas de colas, reintentos y trabajos fallidos: no aplican todavía — la aplicación no tiene jobs en cola reales (todo el procesamiento es síncrono).
