# Inicio y panel del Emprendedor

## Sin cuenta: bienvenida (`E01`, `/emprendedores/bienvenida`, `EmprendedoresController::bienvenida`)

- Propuesta de valor, portada del municipio preferido cuando existe (misma cookie `municipio` que usa el Cliente) con fallback a la imagen genérica, tres beneficios y CTA "Crear mi vitrina" → `register`.

## Con cuenta: inicio (`E06`, `/emprendedores`, `EmprendedoresController::home`)

- Resume cada negocio del usuario (`$request->user()->businesses()`).
- Para negocios sin publicar: guía "qué te falta para vender" (`PublishStorefront::missingFieldsFor`).
- Para negocios publicados: vistazo rápido de métricas semanales (`CalculateReadableMetrics`).

## Navegación (`resources/views/layouts/app/nav-emprendedor.blade.php`)

Sidebar compartido escritorio/móvil: Inicio, Mi vitrina y Productos (una vez que existe un negocio; antes, "Crear mi vitrina"), Oportunidades (deshabilitado con insignia "Pronto" — Fase 2), Promocionar (Copiloto de WhatsApp) y Ayuda (enlace directo a `/soporte`, el mismo canal de soporte semi-asistido de 0.1/1.2).

## Pendiente

- El sidebar solo resuelve `$primaryBusiness` como el primer negocio del usuario; un emprendedor con más de un negocio no tiene todavía un selector explícito en la navegación (sí puede administrar cada uno desde `/emprendedores/negocios`).
- Vista previa y guardado automático durante la edición del panel (fuera del wizard de 1.2) sigue pendiente.
