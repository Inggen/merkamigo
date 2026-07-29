# Design system (0.2 del TODO)

> No es una librería de componentes aparte: documenta los tokens, componentes y reglas de marca ya construidos sobre [Flux UI](https://fluxui.dev) (Livewire) y Tailwind 4, para que cualquier pantalla nueva los reutilice en vez de reinventarlos. El sitemap y las rutas por experiencia están en [`docs/product/sitemap.md`](../product/sitemap.md); los flujos completos en [`docs/ux-flows/`](../ux-flows/).

## Tokens (`resources/css/app.css`, bloque `@theme`)

| Token | Valor | Uso |
|---|---|---|
| `--color-brand-500` | `#D7352A` | Rojo principal |
| `--color-brand-600` | `#B9241B` | Rojo oscuro (hover, énfasis) |
| `--color-carbon` | `#1F1F21` | Negro carbón |
| `--color-graphite` | `#4C4C50` | Gris grafito |
| `--color-mist` | `#F4F4F4` | Gris claro |
| `--font-sans` | Poppins | Títulos |
| `--font-secondary` | Inter | Texto |

`--color-brand-*` incluye la escala completa 50-900 para fondos, bordes y estados hover/dark sin salirse de la paleta de marca. El panel de administración de Filament usa la misma familia (`Color::Red` en `AdminPanelProvider`) en vez del ámbar por defecto del framework.

## Componentes de estado (`resources/views/components/states/*`)

Base común `<x-states.state>` (ícono + título + descripción opcional + slot de acción) especializada en ocho variantes, una por cada estado exigido en 0.2:

`loading`, `empty`, `success`, `error`, `offline`, `forbidden`, `suspended`, `maintenance`.

Cada una trae texto por defecto en español y puede sobreescribirse (`title`, `description`) o extenderse con contenido en el slot (por ejemplo, un botón de reintentar).

## Logo (`resources/views/components/app-logo.blade.php`, `app-logo-icon.blade.php`)

- **Ícono** (`app-logo-icon.blade.php`): SVG de dos colores (rojo de marca `#c7312a` y carbón `#2b2a2a`), usado en el favicon (`public/favicon.svg`), PWA icons (`public/icons/icon-192.png`, `icon-512.png`), `apple-touch-icon.png` y en el encabezado (`<x-app-logo-icon>`).
- **Versión monocromática:** `public/logo-mono.svg`, para fondos donde el rojo no tenga suficiente contraste o para impresión a un color.
- **Lockup completo** (`app-logo.blade.php`): ícono + nombre "Merkamigo", con variante `sidebar` para el panel Filament/Livewire y variante inline para el encabezado público.

### Zona de protección y tamaño mínimo

- Dejar como mínimo un espacio libre alrededor del ícono igual a la altura de la "M" del isotipo antes de colocar cualquier otro elemento (texto, borde, otro logo).
- No usar el ícono por debajo de 24px de alto (el tamaño del favicon en pestaña); por debajo de eso usar solo el color sólido de marca sin el detalle interno del isotipo.
- Nunca deformar el SVG con `stretch` o cambiar su relación de aspecto (`viewBox="0 0 392 414"`); escalar siempre con `size-*` de Tailwind, que preserva proporción.
- Sobre fondos oscuros o fotografías, usar `logo-mono.svg` en blanco en vez de forzar el rojo de marca sobre un fondo que no garantice contraste — así se implementó en la portada de la bienvenida del Emprendedor (`resources/views/emprendedores/bienvenida.blade.php`), que superpone un degradado oscuro sobre la foto del municipio antes de mostrar texto/logo en blanco.

## Layouts diferenciados

- `resources/views/layouts/cliente.blade.php` + `components/cliente-nav.blade.php`: experiencia Cliente.
- `resources/views/layouts/app/nav-emprendedor.blade.php` (sidebar Flux): experiencia Emprendedor.
- Ambos comparten los mismos tokens, componentes de estado y el mismo componente de logo; lo que cambia es la navegación y qué acciones se muestran (ver `docs/architecture/decisiones.md`).

## Pendiente

- No existe todavía una librería Storybook/aislada de componentes; los componentes viven donde se usan (`resources/views/components/**`) y este documento es el índice de referencia.
- Validación de legibilidad, tamaño de botones y navegación con una mano con usuarios reales (0.2) sigue pendiente de pruebas manuales — el principio ya se adoptó en el diseño (botones grandes de Flux, jerarquía tipográfica de dos fuentes) pero falta la sesión de validación.
