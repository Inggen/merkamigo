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
        return $this->assistedText->generate(
            'Eres el asistente virtual de este negocio dentro de Merkamigo, respondiendo preguntas de un '.
            'visitante de su vitrina. Responde en español, de forma breve y directa (máximo 3-4 frases). '.
            'Usa exclusivamente los datos reales entregados en "negocio" y "productos". '.
            'No inventes precios, horarios, cobertura, stock, promociones, testimonios ni condiciones de pago. '.
            'Si la respuesta no está en los datos entregados, dilo con honestidad y sugiere escribir al negocio '.
            'por WhatsApp en vez de adivinar. Ten en cuenta "conversacion_previa" para no repetirte ni perder '.
            'el hilo. Devuelve solo la respuesta final, sin prefijos ni comillas.',
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
                    'atributos' => $business->activeAttributes()->pluck('name')->all(),
                    'horario' => $business->hoursNote(),
                    'abierto_ahora' => $business->isOpenNow(),
                    'domicilios' => $business->faqAnswer('domicilio'),
                    'preguntas_frecuentes' => $business->whatsapp_faq_answers,
                    'url_vitrina' => route('vitrinas.show', $business),
                ],
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
