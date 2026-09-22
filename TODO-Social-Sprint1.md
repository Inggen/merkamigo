# TODO-Social-Sprint1.md — Seguimiento de ejecución de TODO_social.md

**Fecha de inicio:** 15 de septiembre de 2026
**Alcance:** Sprint 1 de `TODO_social.md` (Fase 28), confirmado con el usuario — NO incluye feed, posts, estados, reels, checkout, pagos, suscripciones ni Live (eso son sprints 2-9, cada uno un bloque de trabajo aparte).

> `TODO_social.md` es un roadmap de 28 fases / 9 sprints, no una tarea puntual. Este archivo documenta específicamente el Sprint 1, siguiendo la "Nota final para Codex" del propio TODO_social.md: inspeccionar antes de tocar, reutilizar lo existente, cambio mínimo necesario, migraciones reversibles, pruebas, verificar que nada existente se rompa.

## 0. Auditoría (pasos 1-3 del Sprint 1)

Antes de escribir código se inspeccionó el estado actual (roles/permisos, navegación, municipios, buscador). Hallazgo principal: **la mayoría de lo pedido en 1.1 y 1.2 ya existía**, solo con otro nombre.

### Roles / cambio de experiencia (1.1)
- El selector "Comprador/Mi negocio" del TODO **ya está implementado** como `experience` (`cliente`/`emprendedor`):
  - Columna `users.experience` (migración `2026_07_27_181657_add_experience_to_users_table.php`).
  - `App\Domain\Identity\Actions\SwitchExperience` — persiste en el usuario si está autenticado, o en cookie de 1 año si es invitado. No toca favoritos/compras/mensajes/suscripciones.
  - `App\Http\Controllers\ExperienceController` + `POST /experience` (`experience.update`).
  - UI: `resources/views/components/experience-switch-menu.blade.php` (menú compacto) y `experience-picker.blade.php` (selector grande).
  - Gating de layout: `resources/views/layouts/app/sidebar.blade.php:15-25` incluye `nav-cliente` o `nav-emprendedor` según `experience`.
  - Ya cumple los criterios de aceptación del TODO (no se pierde nada al cambiar de modo, misma cuenta para comprar y vender).
- **Gap real encontrado:** `nav-emprendedor.blade.php` hacía `auth()->user()->businesses()->first()` — un usuario con 2+ negocios nunca podía ver ni cambiar al segundo desde el sidebar persistente (sí podía desde `/emprendedores` home, que ya lista todos). Esto es exactamente lo que pide el TODO: *"si un usuario administra varios negocios: permitir seleccionar negocio activo, mantener + Crear negocio."*
- **Corregido:**
  - `resources/views/layouts/app/nav-emprendedor.blade.php` — el "negocio activo" ahora se resuelve del `{business}` de la URL cuando la página actual ya está dentro de un negocio (route model binding, sin inventar un estado global nuevo que duplicaría la navegación por URL ya existente); cae a `first()` solo en páginas sin negocio en la URL (Inicio). Se agregó un `flux:dropdown` (mismo patrón que el menú de usuario) que aparece **solo si el usuario tiene 2+ negocios** (evita ruido para la mayoría): lista todos los negocios con check en el activo, y un `+ Crear negocio` al final.
  - Verificado manualmente con Playwright (usuario de prueba con 2 negocios, limpiado después): el dropdown lista ambos, cambiar de negocio navega correctamente y el resto del sidebar (Productos, Oportunidades, etc.) sigue al negocio activo.
  - Tests nuevos en `tests/Feature/Storefronts/EmprendedorHomeTest.php`: `test_the_sidebar_hides_the_business_switcher_for_a_single_business_owner`, `test_the_sidebar_offers_a_business_switcher_and_create_option_for_multiple_businesses`.

### Municipios (1.2)
- **Ya implementado**, sin cambios de código necesarios:
  - Cookie `municipio` (1 año) vía `App\Domain\Discovery\Actions\SetPreferredMunicipality`, `POST clientes/municipio`.
  - Dos selectores UI: dropdown simple en `cliente-nav.blade.php` y el hero de `clientes/search-hero.blade.php`.
  - **"Municipio por defecto = Todos" ya se respeta en código**, no solo en la memoria de este proyecto: `ClientesController::home()`/`PlazaController::buscar()` explícitamente NO filtran cuando no hay municipio seleccionado.
  - **"Cerca de mí" ya es explícito y no persiste nada**: `clientes/near-me-toggle.blade.php` requiere clic del usuario, usa la Geolocation API del navegador, nunca se solicita automáticamente. Coincide exactamente con el criterio del TODO ("nunca sobrescribir manualmente sin confirmación").
  - El municipio ya afecta feed/vitrinas/destacados/negocios cercanos vía `Business::servesMunicipality()` — el resto (posts, Lives, promociones) no aplica todavía porque esas entidades no existen (sprints 2+).

### Buscador (1.3)
- Ya existente: un único input (`search-hero.blade.php`), botón "Cerca de mí" + botón rojo "Buscar" dentro del mismo formulario, busca negocios + productos (por nombre), filtros de municipio/categoría/zona/disponibilidad/distancia.
- **Gap real encontrado:** no había filtro de precio.
- **Agregado** (ampliación progresiva, no reescritura):
  - `PlazaController::priceRange()` (nuevo helper privado) — lee `precio_min`/`precio_max`, ignora el máximo si es menor que el mínimo (evita un resultado vacío por error de tipeo) en vez de fallar.
  - Aplicado en `PlazaController::buscar()` y `PlazaController::plazaData()` (la vista de municipio sin búsqueda activa) sobre `products.price`.
  - `App\Livewire\CatalogResults` — nuevas props `minPrice`/`maxPrice`, aplicadas en la query de productos (es el componente que realmente renderiza los resultados, no el `$products` calculado en el controlador, que solo se usa para decidir si la sección se muestra).
  - Inputs numéricos "Precio mín. – Precio máx." agregados junto a "Solo disponibles" en `plaza/buscar.blade.php` y `plaza/show.blade.php`, con passthrough como campo oculto en el formulario de filtro por zona para no perderse al cambiar de zona.
  - Tests nuevos: `CatalogResultsTest::test_products_can_be_filtered_by_a_price_range`.

## 1. Verificado sin regresiones

- Suite completa: 763 passed / 34 failed (mismos 34 fallos preexistentes documentados en `TODO-Google-Merchant.md`, ninguno relacionado — rutas de registro obsoletas, `PlazaFiltersTest`/`PublicDiscoveryTest` con rutas ya inexistentes, etc.). Antes de este trabajo: 760 passed / 34 failed.
- Pint sin cambios de estilo pendientes.
- Verificación visual manual con Playwright: selector de negocio activo (abierto/cerrado/tras cambiar), filtro de precio en `/plaza/{municipio}` — sin errores de consola.

## 2. Explícitamente fuera de alcance de este sprint

Todo lo que depende de entidades que no existen todavía (`posts`, `stories`, `reels`, `live_streams`, `carts`, `orders`, `subscriptions`, etc.) — eso es Sprint 2 en adelante, según el propio orden sugerido en `TODO_social.md` (Fase 28). No se creó ninguna tabla nueva en este sprint: todo lo pedido en 1.1-1.3 se resolvió reutilizando o extendiendo lo existente, sin duplicar entidades — justo la regla principal del TODO ("todo lo existente debe conservarse y reutilizarse").

## 3. Siguiente paso sugerido

Sprint 2 (`TODO_social.md`, Fase 28): modelo de `posts`, feed, reacciones, comentarios, guardados, seguir negocios — requiere migraciones nuevas y es un bloque de trabajo separado. Pedir confirmación de alcance antes de arrancarlo, igual que se hizo para este sprint.
