# TODO — Integración Google Merchant Center / Google Shopping

**Fecha de inicio:** 13 de septiembre de 2026
**Estado general:** Completa (Fases 1-12)
**Objetivo:** que los productos publicados por los negocios de Merkamigo se sincronicen automáticamente con Google Merchant Center (Merchant API actual, no Content API) y sean elegibles para Google Shopping/Search/Images/Lens, soportando una futura cuenta *multi-seller* con `external_seller_id` estable por negocio.

## 0. Hallazgo de partida (Fase 1)

Ya existía una integración parcial (commit `861856ea`), que se **extiende**, no se reescribe:

- `config/services.php` (`google_merchant`), `.env.example` — service account, endpoint `merchantapi.googleapis.com` (API correcta, no Content API).
- `app/Support/GoogleMerchant/GoogleMerchantClient.php` — auth service account + `productInputs:insert`, `developerRegistration:registerGcp`, `dataSources`.
- `app/Support/GoogleMerchant/GoogleMerchantProductMapper.php` — mapper a `productInputs.insert` (sin `externalSellerId`, `gtin`/`mpn`/`condition` hardcodeados).
- `app/Console/Commands/GoogleMerchant/SyncGoogleMerchantProductsCommand.php` (`google-merchant:sync`) — sync síncrono por cron, hourly (`routes/console.php`).
- `app/Console/Commands/GoogleMerchant/RegisterGoogleMerchantDeveloperCommand.php` (`google-merchant:register`).
- `app/Http/Controllers/GoogleMerchantFeedController.php` — feed XML alterno (`route('feeds.google-merchant')`).
- `App\Support\Seo\SchemaBuilder::commerceEntity()` — ya emite JSON-LD `Product`/`Offer` en `vitrinas.product`.
- `tests/Feature/Storefronts/GoogleMerchantIntegrationTest.php` — 4 tests existentes.

Gaps identificados frente a la spec completa: sin marketplace (`externalSellerId`), sin opt-in por negocio, sin colas/eventos, sin hash de cambios, sin `gtin/mpn/brand/condition` reales, sin `productInputs.delete`, sin estado persistido, sin panel admin/emprendedor, sin validator con mensajes amigables.

Confirmado contra documentación oficial vigente (no asumido):
- `externalSellerId` va **dentro de `productAttributes`** (no top-level de `ProductInput`), string, requerido en cuentas *multi-seller*.
- GTIN es **`gtins` (array)** en Merchant API actual, no el `gtin` string legacy de Content API.
- `productstatuses` fue **eliminado**; el estado vive en `Product.productStatus`, vía `accounts.products.get`.
- `productInputs.delete` requiere `name` + query param `dataSource`, igual que insert.
- Una cuenta **multi-seller debe aprovisionarla/aprobarla Google** en Merchant Center — no es solo código.

## 1. Decisiones tomadas

1. **Feed XML existente → se mantiene** como fuente secundaria/respaldo, no se toca salvo bugs.
2. **Activación inicial** → tras implementar el validador (Fase 4), se corre un backfill que activa `google_merchant_enabled` en negocios/productos que YA cumplirían los requisitos mínimos hoy (preserva lo que de facto ya sincroniza el comando horario). El resto queda desactivado.
3. **Formulario del emprendedor** (`⚡productos.blade.php`) → se agrega sección opcional "Identificadores para Google Shopping" (GTIN, MPN, marca, condición) en Fase 9.
4. **Autenticación** → se mantiene service account (no se agrega OAuth de usuario, no aplica a integración server-to-server).
5. **Auditoría/logs** → se reutiliza la tabla genérica `audit_logs` + `RecordAuditLog` (acciones `google_merchant.synced|failed|deleted`), no se crea tabla nueva `google_merchant_sync_logs`.
6. **Comando de consola** → se extiende el `google-merchant:sync` ya existente (agregar `--all/--business=/--failed`), no se crea un `merchant:sync` duplicado.

## 2. Columnas nuevas (Fase 2)

**`businesses`**: `external_seller_id` (string, unique, backfill `merkamigo_{id}` inmediato), `google_merchant_enabled` (bool, default false), `google_merchant_status` (enum: no_configurado/pendiente/activo/error, default no_configurado), `google_merchant_last_sync_at` (timestamp nullable), `google_merchant_error` (text nullable).

**`products`**: `gtin` (string nullable), `mpn` (string nullable), `brand` (string nullable, fallback = nombre del negocio si null), `condition` (enum: nuevo/usado/reacondicionado, default nuevo), `google_merchant_status` (enum: no_publicado/pendiente/sincronizando/requiere_ajustes/publicado/error, default no_publicado), `google_merchant_product_id` (string nullable — `name` devuelto por Merchant API), `google_merchant_last_sync_at` (timestamp nullable), `google_merchant_last_error` (text nullable), `google_merchant_synced_hash` (string nullable).

## 3. Progreso por fase

- [x] **Fase 1** — Auditoría del proyecto y diagnóstico (ver arriba).
- [x] **Fase 2** — Base de datos + configuración.
- [x] **Fase 3** — Merchant client + authentication (extender `GoogleMerchantClient`: `delete()`, `get()`/status).
- [x] **Fase 4** — Mapper + validator (`GoogleMerchantProductValidator` nuevo; enriquecer `GoogleMerchantProductMapper` con externalSellerId/gtin/mpn/condition/brand reales; backfill de activación inicial).
- [x] **Fase 5** — Sincronización de productos (`GoogleMerchantService`: syncProduct/createProduct/updateProduct/deleteProduct/syncBusinessProducts/syncAllProducts/getProductStatus).
- [x] **Fase 6** — Jobs/eventos/queues (`ProductCreated`/`ProductUpdated`/`BusinessSuspended`/`BusinessRestored` — primeros eventos de dominio de `Product`/`Business`; `SyncProductToGoogleMerchant`/`DeleteProductFromGoogleMerchant` jobs en `app/Domain/Storefronts/Jobs/`, enganchados desde `CreateProduct`/`UpdateProduct`/`SuspendProduct`/`RestoreProduct`/`SuspendBusiness`/`RestoreBusiness`).
- [x] **Fase 7** — JSON-LD y SEO (enriquecer `SchemaBuilder::offer()`/`commerceEntity()` con gtin/mpn/condition reales).
- [x] **Fase 8** — Panel administrador (página Filament "Google Merchant"; columna de estado en `BusinessesTable`/`ProductsTable`).
- [x] **Fase 9** — Estado simplificado para el emprendedor (badge "Google" en `⚡productos.blade.php`; sección opcional GTIN/MPN/marca/condición en el formulario; mensajes de error traducidos).
- [x] **Fase 10** — Comandos, scheduler y conciliación (extender `google-merchant:sync` con `--all/--business=/--failed`; ajustar el `Schedule::command` horario para comparar hash antes de reenviar).
- [x] **Fase 11** — Tests (sumar casos a `GoogleMerchantIntegrationTest.php` + tests nuevos para validator/mapper/service/jobs; mockear Merchant API siempre).
- [x] **Fase 12** — Documentación (`docs/google-merchant.md`).

## 4. Archivos tocados por fase (se actualiza a medida que se avanza)

### Fase 2 — Base de datos + configuración (cerrada)
- `database/migrations/2026_09_14_090000_add_google_merchant_fields_to_businesses_table.php` (nueva, con backfill de `external_seller_id`)
- `database/migrations/2026_09_14_100000_add_google_merchant_fields_to_products_table.php` (nueva)
- `app/Domain/Businesses/Models/Business.php` — fillable/casts nuevos + `externalSellerIdFor()` (estático) + `externalSellerId()` (instancia, auto-backfill defensivo)
- `app/Domain/Storefronts/Models/Product.php` — fillable/casts nuevos (gtin, mpn, brand, condition, google_merchant_*)
- `config/services.php` — sin cambios, ya estaba completo
- Verificado: 3 negocios existentes backfillados con `merkamigo_{id}`; `GoogleMerchantIntegrationTest` (4/4) sigue en verde.
- **Incidente y resolución:** un `git stash`/`stash pop` para aislar una prueba de baseline chocó con un archivo de caché de tests ya sucio desde antes de la sesión (`--cache-result/test-results`) y el pop abortó, dejando temporalmente fuera del working tree todos los cambios de esta sesión (incluyendo el trabajo previo de video de fondo). Se recuperó todo con `git checkout stash@{0} -- <archivos>` (uno por uno, evitando el archivo conflictivo) y se verificó con grep que cada archivo tuviera sus marcadores esperados antes de hacer `git stash drop`. Nada se perdió. Lección: no volver a usar `git stash` en este repo sin `-u` y sin revisar antes si hay archivos ya sucios que puedan chocar.
- Nota aparte: `tests/Feature/Storefronts/PublicDiscoveryTest.php` tiene 6 fallos preexistentes en `main` (rutas `plaza.show`/`plaza.category` no definidas, texto "Disponible"/"Galería" no encontrado) — confirmado que ya fallaban en HEAD limpio, sin relación con esta integración. Fuera de alcance.

### Fase 3 — Merchant client + authentication (cerrada)
- `app/Support/GoogleMerchant/GoogleMerchantProductMapper.php` — nuevos `offerId(Product)` y `productResourceId(Product)` (`{contentLanguage}~{feedLabel}~{offerId}`, confirmado sin prefijo `channel~` a diferencia de Content API legacy); `map()` reutiliza `offerId()` en vez de duplicar el literal `'MKG-'.…`.
- `app/Support/GoogleMerchant/GoogleMerchantClient.php` — nuevos `delete(Product)` (`productInputs:delete` con `dataSource` query param, trata 404 como éxito) y `get(Product): ?array` (`accounts.products.get`, reemplazo del `productstatuses` eliminado; 404 → `null`).
- Verificado manualmente con `Http::fake` en tinker: URLs, método HTTP y query params exactos según la documentación oficial. `GoogleMerchantIntegrationTest` sigue en verde (4/4).

### Fase 4 — Mapper + validator (cerrada)
- `app/Support/GoogleMerchant/GoogleMerchantProductValidator.php` (nuevo) — `validate(Product): list<string>` / `isEligible(Product): bool`, mensajes en español listos para el emprendedor (negocio publicado/habilitado/external_seller_id, tipo producto, nombre, descripción, imagen, precio fijo > 0).
- `app/Support/GoogleMerchant/GoogleMerchantProductMapper.php` — `map()` ahora usa `$product->brand ?: $business->name` (nunca inventa marca), `$product->condition`, `$product->gtin`/`mpn`, `$business->externalSellerId()`; `apiPayload()` agrega `externalSellerId`, `gtins` (array, solo si hay valor real), `mpn`, `condition` mapeado (`nuevo/usado/reacondicionado` → `NEW/USED/REFURBISHED`), `identifierExists` calculado (true solo si hay gtin o mpn real, nunca inventado).
- **Backfill de activación ejecutado** (decisión de Fase 1): se evaluó cada negocio con el validador (forzando `google_merchant_enabled=true` de prueba) para ver si desbloquearía al menos un producto elegible. Resultado en este entorno: negocio #3 "Daviu Decco - Cortinas y Persianas" → activado (`google_merchant_enabled=true`, `google_merchant_status=pendiente`), sus 5 productos quedaron ELEGIBLES. Negocio #6 "AgroRojas Cajicá" → sin cambios (su único producto es un servicio con precio "consultar", igual que hoy en el comando existente — no pierde ni gana nada). Negocio #15 "Negocio Preview Test" → sin cambios (está en `borrador`).
- Verificado con tinker contra los datos reales del entorno; `GoogleMerchantIntegrationTest` sigue en verde (4/4).

### Fase 5 — Sincronización de productos (cerrada)
- `app/Support/GoogleMerchant/GoogleMerchantService.php` (nuevo) — `syncProduct`/`createProduct`/`updateProduct` (alias de `syncProduct`, sin duplicar lógica), `deleteProduct`, `syncBusinessProducts`, `syncAllProducts`, `getProductStatus`, `hash(Product)`.
  - Respeta el feature flag global (`services.google_merchant.enabled`): si está apagado, no-op total, no toca la base de datos.
  - Corre el validador antes de llamar a Google; si falla, guarda el motivo en `google_merchant_last_error` y decide `no_publicado` (bloqueado por el negocio o el producto no publicado) vs `requiere_ajustes` (el producto sí está publicado pero le falta algo que el emprendedor puede arreglar) sin usar match de strings — vuelve a evaluar las condiciones de negocio directamente.
  - Hash SHA-256 sobre el `apiPayload()`: si el producto ya está `publicado` y el hash no cambió, no llama a Google.
  - Diferencia errores temporales (429/5xx/`ConnectionException` → relanza para que el Job reintente) de permanentes (4xx → guarda `google_merchant_status=error` + `google_merchant_last_error`, no reintenta solo).
  - Reutiliza `RecordAuditLog` (`google_merchant.synced|deleted|failed`) en vez de una tabla de logs nueva, tal como se decidió en la Fase 1.
  - Logs `[GoogleMerchant] Product {id} ... Action ... SUCCESS/FAILED` sin tokens/secretos.
- **Verificado end-to-end con `Http::fake`** contra el producto #1 real (negocio #3, ya elegible tras el backfill de la Fase 4): primer `syncProduct()` llama a Google y guarda `publicado`; segundo `syncProduct()` sin cambios NO llama a Google (hash igual); tras cambiar el precio, sí vuelve a llamar. 1 → 1 → 2 llamadas HTTP registradas, exactamente como se esperaba.
- **Aviso de dato de prueba:** para verificar el hash cambié temporalmente `products.price` del producto #1 a 99999 y luego lo restauré a 250.000 — no anoté el valor original antes de mutarlo, así que 250.000 es una aproximación, no el valor exacto previo. Su `google_merchant_status`/`product_id`/`hash` se dejaron limpios (`no_publicado`, iguales a sus 4 productos hermanos del mismo negocio, ninguno de los cuales ha pasado por un sync real todavía — eso llega con los Jobs de la Fase 6).

### Fase 6 — Jobs / eventos / queues (cerrada)
- **Corrección aplicada antes de empezar la fase:** `GoogleMerchantService::syncProduct()` (Fase 5) solo actualizaba el estado local cuando un producto YA publicado en Google dejaba de ser elegible, sin retirarlo realmente — ahora, si `google_merchant_status === 'publicado'` y el validador falla, llama a `deleteProduct()` primero (criterio de aceptación 16-17: "elimino/despublico el producto, se retira de Google").
- **Eventos nuevos** (primeros de `Product`/`Business` en todo el proyecto): `app/Domain/Storefronts/Events/ProductCreated.php`, `ProductUpdated.php` (cubre edición, publicar/archivar y las suspensiones/restauraciones de moderación — todas terminan en "el producto cambió"); `app/Domain/Businesses/Events/BusinessSuspended.php`, `BusinessRestored.php`.
- **Jobs nuevos** en `app/Domain/Storefronts/Jobs/` (antes solo tenían `.gitkeep`): `SyncProductToGoogleMerchant` y `DeleteProductFromGoogleMerchant` — patrón del proyecto (IDs escalares, re-fetch en `handle()`, `$tries=5`, `backoff()` escalonado 30s→1h). `DeleteProductFromGoogleMerchant` usa `withTrashed()` por si el producto ya no existe cuando el Job corre.
- **Dispatch agregado** (un `event(...)` después del `RecordAuditLog` ya existente, sin tocar nada más) en: `CreateProduct`, `UpdateProduct`, `Moderation/SuspendProduct`, `Moderation/RestoreProduct`, `Moderation/SuspendBusiness`, `Moderation/RestoreBusiness`.
- **Listeners registrados** en `AppServiceProvider::configureGoogleMerchantSync()` (mismo patrón `Event::listen(closure)` que ya usaba el archivo para `Login`/`Registered`) — todos con `->afterCommit()` para no encolar el Job antes de que la transacción de la Action confirme: `ProductCreated`/`ProductUpdated` → `SyncProductToGoogleMerchant` (el propio servicio decide publicar/actualizar/retirar); `BusinessSuspended` → `DeleteProductFromGoogleMerchant` para cada producto tipo "producto" del negocio; `BusinessRestored` → `SyncProductToGoogleMerchant` para cada uno (se re-evalúa individualmente, restaurar el negocio no garantiza que cada producto siga siendo elegible).
- **Verificado sin regresiones:** `tests/Feature/Moderation/` (38/38, incluye suspender/restaurar producto) y `tests/Feature/Storefronts/GoogleMerchantIntegrationTest.php` (4/4) en verde. `tests/Feature/Storefronts/` completo tiene 14 fallos, confirmados como preexistentes y ajenos a esta integración (route names `plaza.show`/`plaza.category` ya no existen en `routes/web.php`, que no se tocó en esta sesión — `git log` confirma el último commit sobre ese archivo es anterior a hoy).
- **Verificado end-to-end con `Http::fake` + `QUEUE_CONNECTION=sync`** contra datos reales del negocio #3: editar un producto → 1 llamada (sync exitoso); suspender el negocio → retira los productos que sí estaban publicados en Google (no llama a Google para los que ya estaban `no_publicado`, por el guard de `deleteProduct()`); restaurar el negocio → 5 llamadas nuevas, una por producto, todas exitosas. Se limpiaron después los campos `google_merchant_*` de los 5 productos del negocio #3 (quedaron en `no_publicado`, sin `product_id`/`hash` falsos) — se dejaron los `audit_logs` de la prueba (historial real, no afecta nada).

### Fase 7 — JSON-LD y SEO (cerrada)
- `app/Support/Seo/SchemaBuilder.php`, `commerceEntity()`: `brand.name` ahora usa `$product->brand ?: $business->name` (antes siempre el nombre del negocio); `sku` reutiliza `GoogleMerchantProductMapper::offerId()` en vez de duplicar el literal `'MKG-'.…`; se agregan `gtin`/`mpn` (solo si el producto los tiene, `self::clean()` los quita cuando son null — nunca se inventan).
- `offer()`: se agrega `itemCondition` (nuevo helper `itemCondition()`: `nuevo/usado/reacondicionado` → `https://schema.org/NewCondition|UsedCondition|RefurbishedCondition`), en las dos ramas (con precio fijo y con precio "consultar").
- No se tocó nada de canonical/title/description/OpenGraph — ya estaban bien implementados por página (confirmado en la Fase 1), fuera del alcance de este cambio.
- Verificado con `tests/Feature/Storefronts/SeoMarkupTest.php` (8/8 en verde, incluye el test de Product/Service schema) y manualmente con un producto clonado en memoria (sin tocar la base de datos): `brand`, `gtin`, `mpn`, `itemCondition` y `sku` aparecen correctamente cuando el producto tiene esos datos.

### Fase 8 — Panel administrador (cerrada)
- `app/Filament/Pages/GoogleMerchant.php` (nuevo, `/admin/google-merchant`, navegación "Plataforma") — tarjetas de resumen (negocios habilitados, elegibles, publicados, requieren ajustes, error, última sincronización) + aviso si `GOOGLE_MERCHANT_ENABLED=false`; tabla de productos tipo "producto" con columna de estado (badge), motivo del error, última sincronización; filtros por estado y negocio; acción por fila "Sincronizar"; acción masiva "Sincronizar seleccionados" (cubre "sincronizar negocio": filtrar por negocio → seleccionar todos → esta acción); acciones de cabecera "Reintentar fallidos" y "Sincronización global". Todas las acciones **encolan** el Job (`SyncProductToGoogleMerchant::dispatch(...)`), nunca llaman a Google dentro de la petición del admin.
- `resources/views/filament/pages/google-merchant.blade.php` (nuevo) — tarjetas con `x-filament::section` + `{{ $this->table }}`.
- `app/Filament/Resources/Products/Tables/ProductsTable.php` — nueva columna "Google" (badge + tooltip con el error), filtro por estado Google, acción de fila "Sincronizar con Google" (solo visible si la integración está activada).
- `app/Filament/Resources/Businesses/Tables/BusinessesTable.php` — nuevo `ToggleColumn` "Google Shopping" (activación por negocio, tal como pide la Fase 1: "administrable inicialmente desde el panel administrativo") con `afterStateUpdated`: al activar dispara `SyncProductToGoogleMerchant` para cada producto tipo "producto" del negocio, al desactivar dispara `DeleteProductFromGoogleMerchant`; nueva columna/filtro de estado Google.
- **Nuevo test permanente:** `tests/Feature/Storefronts/GoogleMerchantAdminPageTest.php` — un admin puede abrir `/admin/google-merchant` (HTTP 200 real, tabla y acciones montadas), un moderador no pasa `GoogleMerchant::canAccess()`.
- Verificado con un test adicional desechable (creado, corrido y borrado en el mismo paso) que `/admin/products` y `/admin/businesses` siguen renderizando con las columnas nuevas.
- **Incidente menor:** al verificar manualmente con tinker usé `User::factory()->create()` para simular un admin — a diferencia de los tests (que usan `RefreshDatabase`), tinker escribe contra la base de datos real de desarrollo. Se creó un usuario de prueba real (`fhayes@example.net`, id 70); se detectó y se borró de inmediato (`$user->roles()->detach(); $user->delete();`), confirmado con un conteo de usuarios antes/después. Lección: para verificar UI con datos falsos, usar siempre un test con `RefreshDatabase` (como se hizo después), nunca factories sueltas en tinker contra el entorno de Herd.

### Fase 9 — Estado simplificado para el emprendedor (cerrada)
- `app/Domain/Storefronts/Actions/ValidatesProductData.php` — reglas nuevas `gtin`/`mpn`/`brand`/`condition` (todas opcionales) en el trait compartido por `CreateProduct`/`UpdateProduct` — sin esto, `Validator::validate()` habría descartado silenciosamente esos campos del array validado.
- `app/Support/GoogleMerchant/GoogleMerchantErrorTranslator.php` (nuevo) — traduce el motivo crudo (`google_merchant_last_error`) a un mensaje amigable en español por coincidencia de palabras clave (price/imagen/gtin/marca/vendedor/...), con una respuesta genérica de reserva. Solo se usa en la vista del emprendedor; el panel admin (Fase 8) sigue mostrando el motivo crudo tal cual, útil para soporte.
- `resources/views/pages/emprendedores/negocios/⚡productos.blade.php`:
  - Propiedades `gtin`/`mpn`/`brand`/`condition` en el componente, pobladas en `openEdit()`, reseteadas en `openCreate()`, incluidas en `save()`.
  - Nueva sección opcional "Identificadores para Google Shopping" en el formulario (GTIN, MPN, marca, condición), visible solo para `type === 'producto'`, con nota explícita de que un producto artesanal puede dejarlos vacíos.
  - Nuevo badge de estado ("Publicado en Google" / "Pendiente en Google" / "Sincronizando con Google" / "Requiere ajustes para Google" / "Error en Google" / "No publicado en Google") en la tarjeta de cada producto, **solo visible si el negocio tiene Google Shopping habilitado** (evita ruido para la mayoría de emprendedores que todavía no lo usan); tooltip con el mensaje traducido cuando hay error. Usa `$this->business` (no `$product->business`) para no generar N+1.
- **Tests nuevos permanentes:** `tests/Feature/Storefronts/GoogleMerchantSellerPanelTest.php` — guardar GTIN/MPN/marca/condición a través del formulario persiste correctamente en `products`; el badge de Google no aparece si el negocio no tiene la integración habilitada.
- Verificado sin regresiones: `tests/Feature/Storefronts/ProductManagementTest.php` (16/16) + los 2 tests nuevos + `GoogleMerchantIntegrationTest` (4/4), 22/22 en total.

### Fase 10 — Comandos, scheduler y conciliación (cerrada)
- `app/Console/Commands/GoogleMerchant/SyncGoogleMerchantProductsCommand.php` — reescrito manteniendo el mismo nombre (`google-merchant:sync`, no se creó un `merchant:sync` duplicado):
  - `--product=ID` (ya existía), `--business=ID`, `--all`, `--failed` (nuevos), `--dry-run`/`--allow-non-production` (sin cambios de comportamiento).
  - **Cambio de fondo:** antes llamaba a Google síncronamente dentro del propio comando (`$client->insert($product)`); ahora **encola** `SyncProductToGoogleMerchant` por producto — la app y el comando ya no dependen de que Google responda rápido, y los reintentos los maneja el Job (Fase 6), no el comando.
  - **Conciliación real:** sin flags (o con `--all` para saltársela explícitamente), la consulta base excluye lo que ya está `publicado` y no cambió (`updated_at <= google_merchant_last_sync_at`) — así ni siquiera se encola un Job de más; el hash de `GoogleMerchantService` (Fase 5) es la segunda capa de defensa para cuando `updated_at` cambió pero nada relevante para Google en realidad cambió.
  - `--business`/`--failed`/`--product` son selección explícita y manual: siempre se evalúan sin importar la fecha, tal como se espera de un comando de diagnóstico/reintento dirigido.
  - `routes/console.php`: sin cambios de código, solo un comentario aclarando que la tarea horaria ya hace conciliación real por diseño del comando.
- **Corrección necesaria en el fixture de un test existente:** `GoogleMerchantIntegrationTest::publishedProduct()` no ponía `google_merchant_enabled` en el negocio; con el nuevo filtro de conciliación (que ahora sí exige negocio habilitado, cerrando un hueco real: antes el cron horario ignoraba por completo el interruptor por negocio) el conteo de "Productos válidos" habría bajado a 0. Se agregó `'google_merchant_enabled' => true` al fixture — es la corrección correcta dado que el comportamiento nuevo es el que realmente se pidió desde la Fase 1, no un test que "se rompió".
- **Tests nuevos permanentes:** `tests/Feature/Storefronts/GoogleMerchantSyncCommandTest.php` (5 tests) — sin flags solo encola lo que necesita conciliación; `--business` filtra correctamente; `--failed` solo reintenta los que están en error; `--all` ignora la conciliación y encola todo lo elegible; un negocio deshabilitado nunca se encola.
- **Incidente evitado (no llegó a ejecutarse):** un primer borrador de estos tests incluía un caso que usaba `app(GoogleMerchantClient::class)` resuelto por el contenedor (en vez de construirlo a mano con un resolver de token falso, como sí hace el test existente de la Fase 3). Al depurar por qué no coincidían los conteos de `Queue::fake()`, se encontró que en este entorno **sí existe un archivo real de credenciales** (`storage/app/private/google-merchant-service-account.json`, confirmado correctamente en `.gitignore` y nunca commiteado) — ese test habría intentado pedir un token OAuth real a Google al correr la suite. Se eliminó ese caso por completo (la cobertura de "el cliente llama a Google correctamente" ya la tiene, con un resolver falso, el test de la Fase 3) y se corrigieron los demás re-fakeando la cola justo antes de invocar el comando bajo prueba (crear un producto ya dispara su propio intento de sync por el wiring de la Fase 6, y contaminaba el conteo).

### Fase 11 — Tests: hallazgo real + pedido del usuario sobre emojis

- **Bug real encontrado por los tests de la Fase 11** (no un fallo del test, un hueco real): `Business::externalSellerIdFor()` solo se aplicaba (a) al backfill de la migración de Fase 2 para negocios YA existentes, y (b) de forma perezosa dentro de `externalSellerId()` si algo llegaba a llamarlo — pero **ningún negocio nuevo creado después de esa migración recibía `external_seller_id` automáticamente**, así que quedaba `null` y el validador lo bloqueaba con "El negocio no tiene un identificador de vendedor válido." Corregido agregando un hook `Business::booted()` con `static::created(...)` que lo asigna apenas se conoce el ID autoincremental (no puede ir en `creating`, el ID no existe todavía ahí). Verificado que esto NO causa las 34 fallas preexistentes de la suite completa (se corrió con el hook completamente comentado y dieron exactamente las mismas 34 fallas, todas ajenas a esta integración: rutas de registro obsoletas `register.store`/`plaza.show`/`plaza.category`, un bug real de tipos en `OrderConfirmationController`→`SubmitRecommendation`, y algo en `BusinessStandAssignmentTest` no relacionado con negocios).
- **Pedido del usuario a mitad de la Fase 11:** Google Merchant rechaza (o bloquea editar) productos con emojis en `title`/`description`. Se agregó `GoogleMerchantProductMapper::stripEmoji()` (rangos Unicode de emoticones, símbolos, banderas, flechas, variation selector, ZWJ) aplicado **solo** al `title`/`description` que se arma para Google en `map()` — el nombre/descripción reales del producto en Merkamigo, la vitrina pública y el JSON-LD no se tocan, solo lo que viaja a la API. Verificado con `tests/Unit/GoogleMerchantMapperEmojiTest.php`: quita emojis correctamente y no toca tildes/ñ/signos de puntuación en español.
- **Cobertura de los 14 escenarios pedidos**, repartida entre los tests ya escritos en fases anteriores + los nuevos de esta fase:

| # | Escenario | Dónde está cubierto |
|---|---|---|
| 1 | Producto válido | `GoogleMerchantValidatorAndMapperTest::test_a_fully_eligible_product_passes_validation` |
| 2 | Producto sin precio | `...::test_a_product_without_a_price_is_rejected` |
| 3 | Producto sin imagen | `...::test_a_product_without_a_main_image_is_rejected` |
| 4 | Artesanal sin GTIN | `...::test_a_handmade_product_without_gtin_is_still_eligible_and_declares_no_identifier` |
| 5 | Producto con GTIN | `...::test_a_product_with_a_real_gtin_declares_identifier_exists_and_sends_it` |
| 6 | Vendedor sin habilitar | `...::test_a_disabled_seller_blocks_its_products` |
| 7 | Actualización de precio | `GoogleMerchantServiceTest::test_price_update_triggers_a_resync` |
| 8 | Agotado | `GoogleMerchantValidatorAndMapperTest::test_a_sold_out_product_maps_to_out_of_stock_availability` |
| 9 | Eliminación | `GoogleMerchantServiceTest::test_a_previously_published_product_that_becomes_ineligible_is_deleted_from_google` |
| 10 | Error temporal Google | `GoogleMerchantServiceTest::test_a_temporary_google_error_is_rethrown_and_does_not_mark_the_product_as_failed` |
| 11 | Error permanente Google | `GoogleMerchantServiceTest::test_a_permanent_google_error_is_recorded_without_rethrowing` |
| 12 | Retry | `GoogleMerchantServiceTest::test_jobs_are_configured_to_retry_with_backoff` |
| 13 | No cambió → no sincroniza | `GoogleMerchantServiceTest::test_unchanged_product_does_not_call_google_again` |
| 14 | Aislamiento entre negocios | `GoogleMerchantValidatorAndMapperTest::test_isolation_...` + `GoogleMerchantSyncCommandTest::test_business_flag_scopes_to_that_business_only` |

Los 14 escenarios pedidos tienen ahora un test PHPUnit permanente y automatizado — ninguno llama a Google real (`GoogleMerchantServiceTest` ata `GoogleMerchantClient` a un resolver de token falso en `setUp()`, el mismo patrón de seguridad descubierto en la Fase 10). Total de tests nuevos en la Fase 11: 8 (validator/mapper) + 6 (service, incluye retry) + 2 (emojis) = 16, más los 5 de la Fase 10 y los 4 preexistentes de `GoogleMerchantIntegrationTest` — **29 tests de Google Merchant, todos en verde**.

### Fase 12 — Documentación (cerrada)

- `docs/google-merchant.md` (nuevo) — arquitectura, configuración de Google Cloud/Merchant Center, variables `.env`, autenticación, cómo probar un producto/negocio/todo, troubleshooting, cómo deshabilitar, comandos disponibles, funcionamiento de colas, `external_seller_id`, checklist de requisitos para producción. `.env.example` no necesitó cambios (ya tenía las 7 variables `GOOGLE_MERCHANT_*` desde la integración previa).

## 5. Post-cierre: hallazgos en producción (13 sep, tarde)

El usuario compartió un resumen de una IA de Google (con citas a alertas reales de Search Console/Merchant Center recibidas por correo) sobre por qué "Jabón Artesanal de Cookies and Cream" (negocio real "Esencia de La Tierra", Zipaquirá) no aparecía en Google Shopping. Se investigó cada punto contra evidencia real, sin asumir nada:

1. **"Ejecuciones fallidas en tests - main"** — confirmado real pero ya resuelto: `gh run list` mostró un run de `tests` fallido a las 22:36 UTC (por Pint, exactamente el mismo problema de estilo que se corrigió más arriba en esta sesión), seguido 32s después por un run exitoso. El workflow (`.github/workflows/tests.yml`) además **solo corre `--testsuite=Unit`**, con un comentario explícito del equipo reconociendo que la suite Feature tiene fallos heredados pendientes por módulo — confirma independientemente que los 34 fallos preexistentes que ya se habían investigado en la Fase 11 son un estado conocido y aceptado, no algo nuevo ni causado por esta integración.
2. **Emojis en el JSON-LD de la página pública** — hallazgo real, no solo en el feed de Merchant. Se hizo `curl` a la página real de producción (`https://merkamigo.com/m/esencia-de-la-tierra/productos/jabon-artesanal-de-cookies-and-cream`) y se confirmó que el `description` del `@type: Product` en el JSON-LD tenía emojis (🤎, ✨) sin limpiar — el `stripEmoji()` de la tarea anterior solo cubría `GoogleMerchantProductMapper` (el feed hacia Merchant API), no `SchemaBuilder::commerceEntity()` (los datos estructurados de la página, que si Search Console los procesa como "merchant listing" también pueden rechazarse por esto). Corregido:
   - Nuevo `app/Support/Text/Emoji.php` — la limpieza de emojis, extraída a un lugar compartido para no duplicarla entre el mapper y el schema builder.
   - `GoogleMerchantProductMapper::stripEmoji()` ahora delega a `Emoji::strip()`.
   - `SchemaBuilder::commerceEntity()` ahora limpia `name`/`description` con `Emoji::strip()` antes de ponerlos en el JSON-LD.
   - Test nuevo en `tests/Unit/GoogleMerchantMapperEmojiTest.php` reproduciendo el texto real encontrado en producción.
   - Verificado sin regresiones: `SeoMarkupTest` (8/8) + todos los GoogleMerchant (30/30).
3. **"Faltan datos de inventario local" en Merchant Center** — aviso distinto al feed de productos online (ya implementado): pide `store_code`/`availability`/`price`/`quantity` de **inventario local** para fichas locales sin costo, lo que exige que cada negocio tenga vinculado su PROPIO Perfil de Empresa de Google real (nunca el de Merkamigo — usar un único perfil compartido para negocios independientes sería declarar una ubicación falsa ante Google, riesgo de suspensión de la cuenta). Decisión del usuario: la mayoría de negocios apenas están empezando y no tienen Perfil de Empresa todavía, así que se prepara el terreno en código pero queda **apagado hasta que haya negocios con perfiles reales**:
   - `businesses.has_physical_location` (bool, default false) y `businesses.google_business_store_code` (string nullable) — migración `2026_09_14_130000_...`. El emprendedor los marca él mismo (checkbox en el paso 1 del wizard `⚡crear-vitrina.blade.php`, editable después en la sección "Ubicación" de `⚡vitrina.blade.php`); nunca se infieren por geolocalización ni otra heurística (mismo principio ya establecido para el filtro de municipio de la Plaza).
   - `config('services.google_merchant.local_inventory_enabled')` (`GOOGLE_MERCHANT_LOCAL_INVENTORY_ENABLED`, default `false`) — interruptor global aparte de `GOOGLE_MERCHANT_ENABLED`; con ambos flags en `true` Y el negocio con `has_physical_location`+`google_business_store_code`, recién se envía inventario local.
   - `GoogleMerchantProductValidator::validateLocalInventory()`/`isEligibleForLocalInventory()`, `GoogleMerchantProductMapper::localInventoryPayload()` (`inventories/v1/.../localInventories:insert`, sin `dataSource` — confirmado contra la documentación vigente de Merchant API, distinto de `productInputs`), `GoogleMerchantClient::insertLocalInventory()`/`deleteLocalInventory()`.
   - `GoogleMerchantService::syncLocalInventory()`/`deleteLocalInventory()` — envío best-effort colgado de `push()`/`deleteProduct()`: una falla aquí nunca cambia `google_merchant_status` del producto ni bloquea el feed principal, solo queda en el log.
   - Tests nuevos (7): `GoogleMerchantValidatorAndMapperTest` (3), `GoogleMerchantServiceTest` (2, confirman que el flag apagado no genera ninguna llamada `localInventories:insert`), `OnboardingWizardTest` (1), `StorefrontEditorTest` (1). Todos en verde, ninguno llama a Google real.
   - **Pendiente de decisión del usuario, no implementado:** activar `GOOGLE_MERCHANT_LOCAL_INVENTORY_ENABLED` en producción una vez el primer negocio real tenga su Perfil de Empresa de Google vinculado y su `store_code` real cargado.
4. **Alerta de "merchant listings" en Search Console** — confirmado por búsqueda que es casi con certeza el aviso genérico y muy común de Google "Missing field 'hasMerchantReturnPolicy'"/"Missing field 'shippingDetails'" (introducido ~2023, es una **advertencia no crítica**, no bloquea indexación). Merkamigo no declara ninguno de los dos hoy (ni en el JSON-LD ni en el mapper de Merchant). **No implementado todavía** — inventar una política de devoluciones/envíos falsa violaría la regla explícita de la Fase 1 ("no colocar datos ficticios"), y Merkamigo no tiene ese dato modelado (coordinación es vía WhatsApp entre negocios independientes, sin política formal de envío/devolución unificada). Pendiente de decisión del usuario: ¿existe una política real (por negocio o una default de plataforma) que se pueda declarar honestamente?

## 6. Cierre

Las 12 fases quedaron completas. Resumen ejecutable de lo construido en esta sesión: 2 migraciones nuevas ejecutadas, 3 clases de soporte nuevas (`GoogleMerchantProductValidator`, `GoogleMerchantService`, `GoogleMerchantErrorTranslator`) + 2 extendidas (`Client`, `Mapper`), 4 eventos de dominio nuevos (primeros de `Product`/`Business` en el proyecto), 2 Jobs nuevos, 1 página Filament nueva + columnas nuevas en 2 tablas existentes, badge + campos opcionales en el panel del emprendedor, JSON-LD enriquecido, comando `google-merchant:sync` extendido con 3 flags nuevos, 29 tests nuevos (todos en verde, ninguno llama a Google real), y esta documentación. Un archivo `google-merchant-y-video-cta-2026-09-14.zip` en la raíz del repo empaqueta todos los archivos tocados en esta sesión (incluye también el trabajo previo de video de fondo en las tarjetas de Inicio) para desplegar manualmente vía cPanel — ver el mensaje de cierre de la conversación para las notas de despliegue (migraciones pendientes de correr en el servidor, credenciales de Google no incluidas por seguridad, `GOOGLE_MERCHANT_ENABLED` debe quedar en `false` hasta completar la progresión de prueba en producción).
