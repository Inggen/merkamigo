# Edición y publicación de vitrina

Editor lateral/seccionado (`E04`, `route('emprendedores.negocios.vitrina', $business)`, `resources/views/pages/emprendedores/negocios/⚡vitrina.blade.php`): en escritorio la navegación de pestañas queda fija a la izquierda (`lg:grid-cols-[14rem_1fr]`); en móvil la misma barra de pestañas se vuelve horizontal con scroll (`overflow-x-auto`) — no hay una versión reducida separada.

## Pestañas

Portada, Información, Horarios, Ubicación, WhatsApp y Estado de publicación — exactamente las seis áreas descritas en 1.6 del TODO.

## Guardado automático

- Los campos de texto usan `wire:model.live.debounce.900ms`; el hook `updated()` del componente guarda el cambio y actualiza `$savedAt`, mostrado como "Guardado automáticamente a las H:i" mientras no hay una petición en curso (`wire:loading.remove wire:target="save"`).
- Selects y checkboxes (`wire:model.live`) guardan al cambiar sin esperar el debounce.
- "Ver vista previa" abre `route('emprendedores.negocios.vista-previa', $business)` en una pestaña aparte — el emprendedor puede confirmar cómo se ve públicamente sin salir del editor.

## Estado de publicación

- La insignia (Publicado / Suspendido / estado crudo) refleja `Business::isPublished()` / `isSuspended()`.
- Publicar usa `App\Domain\Storefronts\Actions\PublishStorefront`, la misma acción del wizard de 1.2 — no hay una segunda regla de negocio duplicada para publicar desde el editor.

## Pendiente

- Estado de verificación (Pasaporte de confianza, Fase 3) todavía no tiene pestaña ni indicador aquí.
