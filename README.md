# Merkamigo

> Descubre lo local, conecta con tu comunidad.

Merkamigo es una plataforma local que permite a emprendedores crear una vitrina digital sencilla, ser encontrados por compradores cercanos y concretar oportunidades por WhatsApp. La visión completa, el modelo de datos y el backlog por fases están documentados en [`TODO_MERKAMIGO.md`](TODO_MERKAMIGO.md).

## Antes de trabajar en este repositorio

**Paso obligatorio:** revisar `.github/skills/**/SKILL.md` antes de analizar, planear o implementar cualquier tarea. Las habilidades condicionan cómo se debe abordar el diseño, el desarrollo y la seguridad del proyecto.

Convención: `/.github/skills/{nombre-de-la-habilidad}/SKILL.md`.

### Índice de habilidades disponibles

| Habilidad | Cuándo usarla |
|---|---|
| [`diseno-uiux`](.github/skills/diseno-uiux/SKILL.md) | Diseñar o mejorar una pantalla, flujo o sistema de diseño; aplicar accesibilidad y consistencia visual. |
| [`desarrollo-aplicaciones`](.github/skills/desarrollo-aplicaciones/SKILL.md) | Planear arquitectura, elegir stack, construir un MVP o validar calidad/despliegue de extremo a extremo. |
| [`revision-seguridad`](.github/skills/revision-seguridad/SKILL.md) | Revisar autenticación, autorización, manejo de secretos, validación de entradas o preparar una auditoría de seguridad. |

Si una tarea involucra más de un dominio (p. ej. una pantalla nueva con lógica de negocio), aplica las habilidades relevantes en conjunto y deja constancia en el resumen del cambio de cuáles se revisaron y cómo condicionaron la implementación.

## Alcance actual

Este repositorio implementa la **fundación técnica de la Fase 0** (0.3 a 0.6 del TODO): repositorio y stack, arquitectura modular, autenticación multinegocio y una base mínima de seguridad. Las decisiones de producto (0.1) y el sitemap/tokens de marca (0.2) están documentados en versión breve en `docs/product/`; el diseño visual completo y el design system con componentes quedan para una sesión de diseño dedicada.

**Fuera de alcance todavía** (ver `TODO_MERKAMIGO.md` secciones 1 y 8): vitrina completa, plaza de municipio, "Pídelo en Merkamigo", pasaporte de confianza, cobros, y la aplicación híbrida. Nada de esto se construye antes de completar y validar las fases anteriores.

## Stack y versiones instaladas

| Capa | Versión instalada |
|---|---|
| PHP | 8.4 (mínimo 8.3) |
| Laravel | 13.23 |
| Filament | 5.7 (solo panel `/admin` interno) |
| Livewire | 4.3 |
| Laravel Fortify | 1.37 (backend de autenticación) |
| Sanctum | 4.3 |
| spatie/laravel-permission | 8.3 (con *teams* para roles por negocio) |
| Tailwind CSS | 4.x |
| Vite | 8.x (`laravel-vite-plugin` 3.x) |
| Node.js | 22.x |
| Base de datos | MySQL 9 (o MariaDB equivalente) |
| Colas y caché | Redis |

La matriz de compatibilidad validada y las decisiones de arquitectura completas están en [`docs/architecture/decisiones.md`](docs/architecture/decisiones.md). **No combinar Filament 5 con Livewire 3.**

## Requisitos

- PHP 8.3+ (8.4 recomendado) con extensiones estándar de Laravel + `redis`.
- Composer 2.
- Node.js 20+ y npm.
- MySQL 8+/MariaDB o compatible.
- Redis.

## Instalación local

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Crear la base de datos "merkamigo" en tu servidor MySQL/MariaDB local
# y ajustar DB_USERNAME/DB_PASSWORD en .env.

php artisan migrate --seed
npm run build   # o `npm run dev` durante desarrollo

php artisan app:make-superadmin admin@merkamigo.test

# Hook de calidad local (corre Pint antes de cada commit)
composer hooks:install
```

Si usas [Laravel Herd](https://herd.laravel.com), el proyecto se sirve automáticamente en `https://merkamigo.test` al estar en `~/Herd/merkamigo`.

Estrategia de ramas, versionado y releases: [`docs/architecture/estrategia-ramas.md`](docs/architecture/estrategia-ramas.md).

## Variables de entorno

Ver [`.env.example`](.env.example). No contiene secretos: las credenciales de base de datos, Redis, correo, almacenamiento S3-compatible (`AWS_*`) y proveedor de OTP (`SMS_OTP_DRIVER`) se definen localmente por cada entorno. En desarrollo local, `FILESYSTEM_DISK=local` es aceptable temporalmente; **en staging/producción el almacenamiento de fotos, audios y documentos debe ser S3-compatible**, nunca disco local del servidor (ver TODO, sección 2).

## Arquitectura modular

La lógica de negocio vive fuera de controladores, Livewire y Filament, en `app/Domain/{Módulo}/{Actions,Models,Policies,Events,Jobs,Notifications}`:

`Identity`, `Businesses`, `Storefronts`, `Discovery`, `Needs`, `Trust`, `WhatsApp`, `Analytics`, `Billing`, `Moderation`, `Platform`.

En esta fase solo tienen lógica implementada `Identity` (registro dual correo/teléfono) y `Businesses`/`Storefronts` (creación de vitrina). El resto de módulos existen como carpetas con la convención lista, a la espera de su fase correspondiente.

**Ejemplo de reutilización sin duplicar reglas:** `App\Domain\Storefronts\Actions\CreateStorefront` se invoca desde:
- una prueba Pest (`tests/Feature/Storefronts/CreateStorefrontTest.php`),
- un componente Livewire (`resources/views/pages/emprendedores/⚡crear-vitrina.blade.php`),
- `POST /api/v1/businesses` (`App\Http\Controllers\Api\V1\BusinessController`).

## Separación de capas

- **Sitio público y experiencia Clientes/Emprendedores:** Blade + Livewire 4 (`resources/views`, `app/Livewire`).
- **Administración interna:** Filament 5, panel `/admin`, restringido a roles de plataforma (`moderator`, `admin`, `superadmin`) vía `User::canAccessPanel()`.
- **API versionada:** `routes/api.php`, prefijo `/api/v1`, autenticación Sanctum, formato de respuesta estándar (`App\Support\Api\ApiResponse` / `ApiError`).

## Multinegocio y autorización

- `organizations` → `businesses` → `business_memberships` (estado invitado/activo/revocado).
- Roles por negocio (`owner`, `admin`, `collaborator`) mediante `spatie/laravel-permission` con *teams*, usando `business_id` como team. El middleware `business.team` fija el team activo a partir del parámetro de ruta `{business}`.
- Roles de plataforma (`moderator`, `admin`, `superadmin`) son roles globales del mismo paquete, con `User::PLATFORM_TEAM_ID` como team explícito (evita ambigüedad con "sin team").
- `BusinessPolicy` verifica pertenencia; ver pruebas de aislamiento en `tests/Feature/Businesses/BusinessIsolationTest.php`.
- Toda acción sensible (login, alta de negocio, cambio de rol) se registra en `audit_logs` vía `App\Domain\Platform\Actions\RecordAuditLog`.

## Migraciones y seeders

`php artisan migrate --seed` crea el esquema completo y siembra:
- Municipios piloto: Cajicá y Zipaquirá (`MunicipalitySeeder`).
- Categorías iniciales provisionales (`CategorySeeder`), sujetas a ajuste con datos reales del piloto.

## Archivos, colas y notificaciones

- Colas y caché sobre Redis (`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`).
- Verificación de teléfono: OTP generado en cola; en local se escribe al log (`SMS_OTP_DRIVER=log`) hasta contratar un proveedor real.
- Almacenamiento de archivos preparado para disco S3-compatible vía `config/filesystems.php`.

## Pruebas y calidad

```bash
php artisan test          # o ./vendor/bin/pest
./vendor/bin/pint         # formato (--test para solo verificar)
./vendor/bin/phpstan analyse
npm run build
```

`GET /api/v1/health` verifica conectividad de base de datos, Redis, cola y almacenamiento.

## Despliegue, backup y rollback

**Pendiente de definir** en una sesión posterior: no hay todavía entorno de staging/producción, simulacro de backup/restore, ni proveedor de hosting elegido. Ver la lista explícita de pendientes en `docs/architecture/decisiones.md`.

## Versionado de `/api/v1`

La API nace versionada desde `/api/v1`. Cambios incompatibles requerirán una nueva versión (`/api/v2`); no se introducen breaking changes dentro de `v1` sin período de aviso.

## Camino hacia la aplicación híbrida

La Fase 6 (Ionic + Vue 3 + Capacitor, ver TODO) consumirá exclusivamente `/api/v1`. No se duplican reglas de negocio en la futura app: toda la lógica vive en `app/Domain`.

## Manual de marca

Colores y tipografías de referencia en [`docs/product/sitemap.md`](docs/product/sitemap.md#tokens-de-marca). El sistema de diseño completo con componentes está pendiente de una sesión de diseño dedicada.

## Contribución y Definition of Done

Antes de dar por terminada una tarea, revisar la sección 9 (Definition of Done) de `TODO_MERKAMIGO.md`. En resumen: habilidades de `.github/skills` revisadas y referenciadas, criterios de aceptación cumplidos, autorización y validación en servidor, pruebas automáticas proporcionales al riesgo, sin fallas de aislamiento entre negocios, sin secretos en logs, documentación y changelog actualizados.
