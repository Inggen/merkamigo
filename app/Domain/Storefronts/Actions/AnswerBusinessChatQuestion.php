<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\Contracts\GeneratesAssistedText;

/**
 * Responde una pregunta de un visitante en el chat con IA de la vitrina
 * (solo disponible para negocios con `Business::canUseAiChatbot()`),
 * usando exclusivamente datos reales del negocio y su catálogo — nunca
 * inventa precios, horarios, cobertura, stock ni promociones. Sigue el
 * mismo contrato de un solo turno que `GenerateWhatsAppPromotion`; el
 * historial de la conversación se manda como parte del contexto para que
 * el modelo pueda dar continuidad sin necesitar estado en el servidor.
 */
class AnswerBusinessChatQuestion
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history  Últimos turnos de la conversación, más antiguo primero.
     */
    public function handle(Business $business, string $question, array $history = []): ?string
    {
        $profile = $business->chatbotProfile;

        $toneInstruction = filled($profile?->tone)
            ? 'El negocio pidió expresamente que hables con este tono y estas expresiones propias de la región/del '.
                'negocio (úsalas de forma natural, sin forzarlas en cada frase): "'.$profile->tone.'". '
            : '';

        return $this->assistedText->generate(
            'Eres una persona real que atiende el chat de este negocio dentro de Merkamigo — no digas que eres un '.
            'asistente virtual ni un bot, simplemente responde como lo haría alguien del negocio que conoce bien '.
            'su catálogo. Responde en español, con un tono cercano y natural (como un mensaje de WhatsApp bien '.
            'escrito), sin sonar robótico ni repetir la misma estructura en cada respuesta. '.$toneInstruction.
            'Responde puntualmente SOLO lo que te preguntan, como lo haría una persona real chateando — nunca '.
            'sueltes de una vez toda la información que tengas del negocio o del catálogo si no te la pidieron. Si '.
            'te saludan o escriben algo breve ("hola", "buenas", "quiubo"), responde solo con un saludo corto y '.
            'natural (puedes preguntar en qué le ayudas), sin adelantarte a dar precios, horarios ni listas de '.
            'productos que nadie pidió todavía. Lleva la conversación como una persona: si la pregunta es vaga o '.
            'te falta un dato para responder bien (qué producto le interesa, para cuándo, para cuántas personas), '.
            'pregúntalo primero en vez de asumir y contestar con un resumen general. Cuando sí tengas claro qué te '.
            'están pidiendo, sé específico y usa los datos reales entregados en "negocio", "productos", '.
            '"notas_del_negocio" y "documento_del_negocio" — precios, nombres de productos, horarios, condiciones '.
            'reales cuando existan — pero limitado a esa pregunta puntual, no como excusa para repasar todo lo que '.
            'sabes. No hay un límite fijo de frases, pero por defecto una respuesta corta y al grano es mejor que '.
            'una larga; solo extiéndete cuando la pregunta realmente lo pida. '.
            'No inventes ni asumas ningún dato que no esté en el contexto (precios, horarios, cobertura, stock, '.
            'promociones, testimonios, condiciones de pago). Si "productos" o "negocio" tienen un dato estructurado '.
            '(por ejemplo un precio), ese manda por encima de lo que diga "notas_del_negocio" o '.
            '"documento_del_negocio" si llegaran a contradecirse — usa esos dos últimos para completar información '.
            'que no esté en los campos estructurados (por ejemplo historia del negocio, políticas, marcas que '.
            'manejan, preguntas frecuentes propias). Antes de rendirte, revisa con calma TODA la '.
            'información disponible (incluyendo "preguntas_frecuentes", "direccion", "notas_del_negocio" y '.
            '"documento_del_negocio") porque suele responder más '.
            'preguntas de las que parece a primera vista. Recurrir a "escríbele al negocio por WhatsApp" es el '.
            'último recurso, solo para lo puntual que de verdad no está en ningún dato entregado (ej. negociar un '.
            'precio, confirmar stock exacto de algo no listado, coordinar una entrega específica) — nunca lo uses '.
            'como respuesta por defecto ni lo repitas en cada mensaje. Ten en cuenta "conversacion_previa" para no '.
            'repetirte ni perder el hilo. Devuelve solo la respuesta final, sin prefijos ni comillas.',
            [
                'pregunta_actual' => $question,
                'conversacion_previa' => $history,
                'negocio' => [
                    'nombre' => $business->name,
                    'titulo' => $business->storefront?->headline,
                    'descripcion' => $business->storefront?->description,
                    'categoria' => $business->category?->name,
                    'municipio' => $business->municipality?->name,
                    'zona' => $business->zone,
                    'direccion' => $business->address,
                    'atributos' => $business->activeAttributes()->pluck('name')->all(),
                    'horario' => $business->hoursNote(),
                    'abierto_ahora' => $business->isOpenNow(),
                    'metodos_de_pago' => $business->payment_info,
                    'preguntas_frecuentes' => $business->whatsapp_faq_answers,
                    'url_vitrina' => route('vitrinas.show', $business),
                ],
                'notas_del_negocio' => $profile?->extra_notes,
                'documento_del_negocio' => $profile?->document_text,
                'productos' => $business->products()
                    ->where('status', 'publicado')
                    ->orderBy('position')
                    ->limit(25)
                    ->get()
                    ->map(fn ($product) => [
                        'nombre' => $product->name,
                        'descripcion' => $product->description,
                        'precio_tipo' => $product->price_type,
                        'precio' => $product->price,
                        'promocion_activa' => $product->hasActivePromo(),
                        'precio_promocion' => $product->hasActivePromo() ? $product->promo_price : null,
                        'disponible' => $product->is_available && ! $product->isSoldOut(),
                    ])
                    ->all(),
            ],
        );
    }
}
