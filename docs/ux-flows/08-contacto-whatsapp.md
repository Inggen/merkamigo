# Contacto por WhatsApp

Botón de WhatsApp presente en la vitrina, en cada producto y en el Copiloto (borradores generados). Todos pasan por `VitrinaController::whatsapp` / `whatsappProduct`, que:

1. Registra el evento (`App\Domain\Analytics\Actions\RegisterWhatsAppClick`) antes de redirigir — así el clic queda medido sin necesidad de leer el contenido de la conversación.
2. Redirige a `https://wa.me/{numero}?text={mensaje}` (`buildWhatsAppUrl`), con el mensaje contextual pre-armado (nombre del negocio o del producto).
3. Nunca envía nada automáticamente ni abre una ventana de chat interno: siempre abre WhatsApp real (app o web) con el texto ya escrito para que la persona lo revise y lo envíe.

Funciona igual en móvil (abre la app de WhatsApp) y en escritorio (abre WhatsApp Web) porque ambos usan el mismo esquema `wa.me`.

## Pendiente

- Sugerir respuestas editables a preguntas frecuentes (productos disponibles, horarios, domicilio) más allá de lo que ya cubre `GenerateWhatsAppPromotion` (1.7).
