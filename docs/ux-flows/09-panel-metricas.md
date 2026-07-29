# Panel y métricas

`E06`, `route('emprendedores.negocios.metricas', $business)` — Livewire (`pages::emprendedores.negocios.metricas`) alimentado por `App\Domain\Analytics\Actions\CalculateReadableMetrics`.

## Qué muestra

- Resumen semanal en lenguaje humano (`summary`, p. ej. "Esta semana N personas vieron tu negocio y M te escribieron").
- Tarjetas de total de visitas y clics a WhatsApp de los últimos 7 días.
- Comparación con el período de 7 días anterior (`previous_total_views`, `previous_total_whatsapp_clicks`) cuando hay datos suficientes.
- Gráfico simple de visitas y clics a WhatsApp por día (`views_by_day`, `whatsapp_clicks_by_day`).

## De dónde salen los números

- Cada visita a vitrina/producto y cada clic a WhatsApp se registra como `AnalyticsEvent` (`RegisterStoreView`, `RegisterWhatsAppClick`).
- Las cifras se agregan por día en el momento de la consulta en vez de precalcularse en una tabla `daily_business_metrics`: al volumen del piloto (dos municipios) es igual de rápido y siempre queda conciliable con los eventos crudos — simplificación documentada en `docs/architecture/decisiones.md`.

## Copiloto de WhatsApp (`route('emprendedores.negocios.copiloto', $business)`)

Vive en la misma zona de navegación ("Promocionar"): plantillas de promoción/estado/respuesta, generación de texto a partir de producto/precio/tono, edición, copiar y abrir WhatsApp, con borradores e historial limitado. Nunca envía ni responde automáticamente.

## Pendiente

- Job en cola para precalcular métricas diarias solo se justifica si el volumen de eventos crece más allá del piloto.
- Moderar contenido generado por el Copiloto y mostrar advertencia de revisión explícita.
