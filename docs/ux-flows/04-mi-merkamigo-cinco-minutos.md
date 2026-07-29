# Mi Merkamigo en cinco minutos

Wizard Livewire de 5 pasos: `resources/views/pages/emprendedores/⚡crear-vitrina.blade.php` (`route('emprendedores.crear-vitrina')`). Un solo componente maneja los cinco pasos (`public int $step`); no hay pantallas separadas que puedan perder estado al navegar.

1. **Información básica** — nombre, WhatsApp, municipio, categoría, zona (opcional).
2. **Cuéntanos sobre tu negocio** — descripción por texto. La entrada por audio queda para cuando exista transcripción en cola (ver "Pendiente").
3. **Fotografías** — logo y portada; pasan por `App\Support\Media\MediaUploader` (valida tipo/tamaño según `config/media.php` y redimensiona sin agrandar).
4. **Vista previa** — `<x-storefront-preview>` renderiza la vitrina tal como la verá un comprador; "Editar información" regresa al paso 1 sin perder lo capturado.
5. **Publicación** — si faltan datos obligatorios, se listan explícitamente y aparece "Ayúdame a terminar mi vitrina" (enlaza a `/soporte`, el canal de soporte semi-asistido decidido en 0.1). "Revisar y publicar" es una acción distinta de "Editar información": nunca publica sin ese paso explícito.

## Guardado y borrador

- El estado del wizard vive en las propiedades públicas del componente Livewire; salir y volver a entrar recupera el negocio en borrador (no se pierde información al avanzar/retroceder entre pasos).
- Ningún texto se publica sin que el paso 5 se confirme explícitamente.

## Pendiente

- Transcripción de audio en cola y "texto asistido por IA con revisión obligatoria" siguen sin proveedor elegido (ver `docs/architecture/decisiones.md`).
- Generar más de una variante/tamaño por imagen (hoy se guarda una sola versión ya redimensionada).
- Medir el tiempo real hasta la primera publicación está implementado a nivel de datos (`created_at` del negocio vs. `published_at`), pero no hay un reporte agregado todavía.
