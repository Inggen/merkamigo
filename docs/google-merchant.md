# Integración con Google Merchant Center

Sincroniza los productos publicados de los negocios de Merkamigo con Google Merchant Center (Merchant API actual, no Content API for Shopping) para que sean elegibles en Google Shopping, Search, Images y Lens. Preparada para una cuenta **multi-seller** (marketplace), con `external_seller_id` estable por negocio.

## 1. Arquitectura implementada

```
Producto/Negocio cambia (Action)
        │
        ▼
  event(ProductCreated|ProductUpdated|BusinessSuspended|BusinessRestored)
        │  (AppServiceProvider::configureGoogleMerchantSync)
        ▼
  Job encolado (->afterCommit(), nunca dentro de la transacción)
   SyncProductToGoogleMerchant  │  DeleteProductFromGoogleMerchant
        │
        ▼
  GoogleMerchantService::syncProduct()/deleteProduct()
        │
   ┌────┼──────────────┬───────────────┐
   ▼    ▼              ▼               ▼
Validator  Mapper   Client (HTTP)   audit_logs
(reglas)  (payload)  (Merchant API)  (historial)
```

- **`app/Support/GoogleMerchant/GoogleMerchantClient.php`** — auth por *service account* + llamadas HTTP crudas (`productInputs:insert`, `productInputs:delete`, `products.get`, `developerRegistration:registerGcp`, `dataSources`). No sabe nada de reglas de negocio.
- **`GoogleMerchantProductMapper.php`** — convierte un `Product` de Merkamigo al payload de Merchant API. También calcula `offerId`/`productResourceId` (usados también por el `sku` del JSON-LD) y limpia emojis de `title`/`description` antes de enviarlos (Google rechaza/no deja editar productos con emojis).
- **`GoogleMerchantProductValidator.php`** — reglas mínimas de elegibilidad, en español, listas para mostrar al emprendedor. Nunca deja pasar un producto inválido al mapper/cliente.
- **`GoogleMerchantService.php`** — el único punto de entrada real. Decide si hace falta llamar a Google (hash de cambios), diferencia errores temporales de permanentes, y es lo único que los Jobs/comandos deben usar.
- **Eventos** (`app/Domain/Storefronts/Events/`, `app/Domain/Businesses/Events/`) + **Jobs** (`app/Domain/Storefronts/Jobs/`) — primera vez que `Product`/`Business` disparan eventos de dominio en este proyecto. Ver `AppServiceProvider::configureGoogleMerchantSync()` para el mapeo evento → Job.
- **Panel admin**: página Filament `/admin/google-merchant` (`app/Filament/Pages/GoogleMerchant.php`) + columnas nuevas en `ProductsTable`/`BusinessesTable`.
- **Panel del emprendedor**: badge de estado + campos opcionales GTIN/MPN/marca/condición en `resources/views/pages/emprendedores/negocios/⚡productos.blade.php`.
- **JSON-LD**: `App\Support\Seo\SchemaBuilder::commerceEntity()`/`offer()` ya emitían `Product`/`Offer` antes de esta integración; se enriquecieron con `gtin`, `mpn`, `itemCondition` y `brand` reales.
- **Feed XML** (`app/Http/Controllers/GoogleMerchantFeedController.php`, ruta `feeds.google-merchant`) — canal secundario/de respaldo, no se modificó.

## 2. Configuración de Google Cloud

1. Crea (o reutiliza) un proyecto de Google Cloud.
2. Habilita la **Merchant API** (`merchantapi.googleapis.com`) en ese proyecto — no la "Content API for Shopping", que está deprecada.
3. Crea una **cuenta de servicio** (Service Account) en ese proyecto y descarga su clave JSON.
4. Guarda esa clave en `storage/app/private/google-merchant-service-account.json` (ruta configurable, ver `GOOGLE_MERCHANT_CREDENTIALS`). **Nunca la subas al repositorio** — `storage/app/private/.gitignore` ya la excluye con un `*`.

## 3. Configuración de Merchant Center

1. En Merchant Center, agrega el correo de la cuenta de servicio como usuario con acceso a tu cuenta.
2. Ejecuta una sola vez `php artisan google-merchant:register {tu-email}` — registra el proyecto de Google Cloud ante Merchant API y lista los *data sources* disponibles.
3. Crea (o identifica) un **data source primario** de tipo API y copia su ID a `GOOGLE_MERCHANT_DATA_SOURCE_ID`.
4. Si vas a operar como **marketplace** (varios vendedores bajo una sola cuenta Merchant Center), esa cuenta debe configurarse como **multi-seller** — esto lo aprueba/aprovisiona Google, no es algo que el código resuelva por sí solo. Sin ese tipo de cuenta, `externalSellerId` se envía igual mid pero Google puede rechazarlo o ignorarlo si la cuenta no es multi-seller.

## 4. Variables de entorno

```env
GOOGLE_MERCHANT_ENABLED=false
GOOGLE_MERCHANT_ACCOUNT_ID=
GOOGLE_MERCHANT_DATA_SOURCE_ID=
GOOGLE_MERCHANT_CREDENTIALS=storage/app/private/google-merchant-service-account.json
GOOGLE_MERCHANT_CONTENT_LANGUAGE=es
GOOGLE_MERCHANT_FEED_LABEL=CO
GOOGLE_MERCHANT_CURRENCY=COP
GOOGLE_MERCHANT_ENDPOINT=https://merchantapi.googleapis.com
GOOGLE_MERCHANT_TIMEOUT=30
```

Todas viven en `config/services.php` bajo la clave `google_merchant`. Con `GOOGLE_MERCHANT_ENABLED=false` (el default), **la integración completa queda apagada**: no se hacen llamadas HTTP, no se rompe la creación/edición de productos, los Jobs se ejecutan pero salen inmediatamente sin hacer nada.

## 5. Autenticación

Service account (OAuth 2.0 de servidor), no OAuth de usuario — es la opción correcta para un backend server-to-server sin usuario interactivo de por medio. `GoogleMerchantClient` lee el JSON de `GOOGLE_MERCHANT_CREDENTIALS`, valida que sea `type: service_account`, y usa `google/auth` (`ServiceAccountCredentials::fetchAuthToken()`) para obtener el access token. El token se cachea en memoria por instancia del cliente (no persiste entre requests).

## 6. Cómo probar un producto

```bash
# Sin credenciales ni llamadas HTTP: solo valida el payload
php artisan google-merchant:sync --product=123 --dry-run

# Envío real de un solo producto (requiere producción o --allow-non-production)
php artisan google-merchant:sync --product=123 --allow-non-production
```

Por seguridad, un envío real (sin `--dry-run`) fuera de `APP_ENV=production` se bloquea a menos que agregues `--allow-non-production` explícitamente.

## 7. Cómo sincronizar un negocio completo

```bash
php artisan google-merchant:sync --business=45 --allow-non-production
```

Encola `SyncProductToGoogleMerchant` para cada producto tipo "producto" de ese negocio (sin importar si ya estaban al día). Desde el panel admin (`/admin/google-merchant`), lo mismo se logra filtrando la tabla por negocio, seleccionando todos los resultados y usando la acción masiva "Sincronizar seleccionados".

## 8. Cómo ejecutar sincronización global

```bash
# Progresión recomendada para la primera vez en producción:
php artisan google-merchant:sync --product=ID --allow-non-production   # 1. un producto
php artisan google-merchant:sync --business=ID --allow-non-production  # 2. un negocio
php artisan google-merchant:sync --all                                 # 3. todo (ya en producción)
```

`--all` ignora la conciliación por fecha y encola **todos** los productos elegibles de negocios habilitados, sin importar si ya estaban sincronizados — úsalo para reconstruir el catálogo completo (p. ej. tras cambiar el mapeo de campos), no como rutina diaria. La tarea programada (`routes/console.php`, cada hora) corre **sin** `--all`: solo encola lo que nunca se sincronizó, quedó en error/requiere ajustes, o cambió desde su último envío exitoso.

## 9. Troubleshooting

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| El comando dice "Falta configurar: ..." | Falta `GOOGLE_MERCHANT_ACCOUNT_ID`, `GOOGLE_MERCHANT_CREDENTIALS` o `GOOGLE_MERCHANT_DATA_SOURCE_ID` | Completa las variables en `.env` |
| "No se puede leer el archivo de credenciales" | La ruta en `GOOGLE_MERCHANT_CREDENTIALS` no existe o no es legible | Verifica la ruta y permisos del archivo JSON |
| Un producto queda en "Requiere ajustes" | El validador local lo rechazó (falta imagen, precio, descripción...) | El motivo exacto está en `google_merchant_last_error` (panel admin) o el badge del panel del emprendedor |
| Un producto queda en "Error" tras sincronizar | Google lo rechazó (permanente: 4xx) | Revisa `google_merchant_last_error`; el panel del emprendedor muestra la versión traducida (`GoogleMerchantErrorTranslator`) |
| Un producto no se reintenta solo tras un error temporal (5xx/429) | Revisa que el worker de colas (`php artisan queue:work`) esté corriendo — el Job reintenta solo, pero necesita un worker activo | `php artisan queue:work` o supervisor en producción |
| Nada se sincroniza nunca | `GOOGLE_MERCHANT_ENABLED=false`, o el negocio tiene el toggle "Google Shopping" apagado | Revisa ambos interruptores (global y por negocio) |
| El texto del producto llega distinto a Google | Se limpian emojis automáticamente antes de enviarlo (Google los rechaza) — el nombre/descripción reales en Merkamigo no cambian | Comportamiento esperado, ver `GoogleMerchantProductMapper::stripEmoji()` |

## 10. Cómo deshabilitar la integración

Pon `GOOGLE_MERCHANT_ENABLED=false` y despliega. Efecto inmediato: ningún Job hace llamadas HTTP, el comando programado sigue corriendo pero no hace nada, y ni la creación/edición de productos ni el resto del sitio se ven afectados. Los datos ya sincronizados (`google_merchant_*`) no se borran — simplemente dejan de actualizarse hasta que se reactive.

## 11. Comandos disponibles

| Comando | Uso |
|---|---|
| `google-merchant:register {email}` | Registro único del proyecto de Google Cloud ante Merchant API |
| `google-merchant:sync` | Sin flags: conciliación (solo lo que cambió/falló/nunca se sincronizó) |
| `google-merchant:sync --product=ID` | Un solo producto |
| `google-merchant:sync --business=ID` | Todos los productos elegibles de un negocio |
| `google-merchant:sync --failed` | Reintenta solo los que quedaron en estado "Error" |
| `google-merchant:sync --all` | Todo el catálogo elegible, ignorando la conciliación |
| `google-merchant:sync --dry-run` | Valida sin enviar nada (combinable con cualquier flag de arriba) |
| `google-merchant:sync --allow-non-production` | Autoriza un envío real fuera de `APP_ENV=production` |

## 12. Funcionamiento de las colas

`QUEUE_CONNECTION=database` (config existente del proyecto, sin cambios). Los Jobs (`SyncProductToGoogleMerchant`, `DeleteProductFromGoogleMerchant`) reciben solo el ID del producto (nunca el modelo completo) y lo vuelven a buscar en `handle()` — evita datos obsoletos si el producto cambió entre que se encoló el Job y que un worker lo tomó. `$tries = 5` con `backoff()` escalonado (30s, 2min, 5min, 15min, 1h): un error temporal (rate limit, 5xx de Google, timeout) se relanza para que el Job reintente solo; un error permanente (producto/vendedor inválido según Google) se guarda en `google_merchant_last_error` y **no** se reintenta automáticamente — aparece en el panel admin con la acción "Reintentar fallidos" una vez corregido.

En producción, asegúrate de tener un worker corriendo (`php artisan queue:work` vía Supervisor u otro proceso persistente) — sin uno, los Jobs se acumulan en la tabla `jobs` sin procesarse.

## 13. `external_seller_id`

Identificador estable del vendedor (negocio) ante Google, requerido en cuentas *multi-seller*. Se genera como `merkamigo_{id_interno_del_negocio}` — **nunca** a partir del slug (que sí puede cambiar). Se asigna automáticamente:

- A los negocios que ya existían, vía el backfill de la migración `2026_09_14_090000_add_google_merchant_fields_to_businesses_table`.
- A cualquier negocio nuevo, vía `Business::booted()` (evento `created`), sin importar el punto de entrada (formulario del emprendedor, seeder, comando, etc.).

Una vez asignado, no cambia automáticamente. Se envía en `productAttributes.externalSellerId` de cada `productInputs.insert` (confirmado contra la documentación vigente de Merchant API: va dentro de los atributos del producto, no en el nivel raíz de `ProductInput`).

## 14. Requisitos para pasar de testing a producción

- [ ] Cuenta de Merchant Center creada y, si aplica marketplace, configurada como **multi-seller** (requiere aprobación de Google).
- [ ] Cuenta de servicio de Google Cloud creada, con acceso otorgado en Merchant Center.
- [ ] `.env` de producción con las 8 variables `GOOGLE_MERCHANT_*` completas y `GOOGLE_MERCHANT_ENABLED=true`.
- [ ] Archivo de credenciales presente en el servidor de producción, **fuera** del control de versiones, con permisos restringidos.
- [ ] `php artisan google-merchant:register {email}` ejecutado una vez contra la cuenta real.
- [ ] Data source primario creado en Merchant Center y su ID copiado a `GOOGLE_MERCHANT_DATA_SOURCE_ID`.
- [ ] Worker de colas corriendo de forma persistente (Supervisor u equivalente).
- [ ] Progresión de prueba real completada en este orden: `--product=ID` → `--business=ID` → `--all` (nunca activar todo el catálogo de una sola vez sin antes probar uno y un negocio).
- [ ] Negocios reales activados uno por uno desde `/admin/google-merchant` o la columna "Google Shopping" en `/admin/businesses` — no hay activación masiva automática por diseño.
