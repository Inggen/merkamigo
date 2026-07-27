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

Se acordó con el usuario limitar el primer pase de Fase 0 a la fundación técnica (0.3, 0.4, 0.5 y una porción mínima de 0.6). 0.1 y 0.2 se documentaron en versión breve (`docs/product/alcance-fase0.md`, `docs/product/sitemap.md`) reutilizando las decisiones ya escritas en `TODO_MERKAMIGO.md`, sin producir prototipos visuales todavía.

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

Se implementa el esquema de datos y el flujo de generación de OTP en cola, pero como no hay proveedor de SMS contratado, en el entorno local el código se escribe a un canal de log en vez de enviarse. Esto es intencional y sigue el principio de la Fase 5 ("contratos internos para IA/SMS sin acoplarse a un proveedor"): se define la interfaz ahora y se conecta un proveedor real más adelante.

## Explícitamente diferido a una sesión posterior

- Textos legales definitivos (términos, privacidad) — hoy son placeholders.
- Simulacro real de backup/restore.
- Entorno de staging/producción con hosting real (requiere que el usuario decida proveedor de hosting).
- Proveedor real de SMS/OTP y de almacenamiento S3 compatible.
- Documentación OpenAPI completa (solo se documentan los endpoints construidos en este pase).
- Ejecución de CI en la nube (el workflow de GitHub Actions queda listo, pero no hay remoto configurado todavía).
- Prototipos visuales y design system completo de 0.2.
