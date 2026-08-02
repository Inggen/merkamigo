<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use InvalidArgumentException;

/**
 * Genera el texto de una plantilla del Copiloto de WhatsApp (1.7 del TODO)
 * a partir de datos reales del negocio/producto — nunca inventa precios ni
 * condiciones, y nunca envía nada: solo produce texto editable para que el
 * emprendedor lo revise, copie y comparta él mismo.
 */
class GenerateWhatsAppPromotion
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @var array<int, string>
     */
    private const TYPES = [
        WhatsAppContent::PROMOCION,
        WhatsAppContent::ESTADO,
        WhatsAppContent::RESPUESTA,
        WhatsAppContent::PRESENTACION,
    ];

    /**
     * @var array<int, string>
     */
    private const LENGTHS = ['corto', 'medio', 'largo'];

    public function handle(Business $business, string $type, ?Product $product, string $tone, string $length = 'medio'): string
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Tipo de contenido inválido: {$type}");
        }

        if (! in_array($length, self::LENGTHS, true)) {
            throw new InvalidArgumentException("Longitud inválida: {$length}");
        }

        $emoji = $tone === 'cercano';

        $draft = match ($type) {
            WhatsAppContent::PROMOCION => $this->promocion($business, $product, $emoji, $length),
            WhatsAppContent::ESTADO => $this->estado($business, $emoji, $length),
            WhatsAppContent::RESPUESTA => $this->respuesta($business, $emoji, $length),
            WhatsAppContent::PRESENTACION => $this->presentacion($business, $emoji, $length),
        };

        return $this->assist($draft, $business, $type, $product, $tone, $length) ?: $draft;
    }

    private function promocion(Business $business, ?Product $product, bool $emoji, string $length): string
    {
        if (! $product) {
            return $this->join([
                $emoji ? "🎉 ¡Novedades en {$business->name}!" : "Novedades en {$business->name}.",
                $length !== 'corto' ? 'Escríbenos por WhatsApp para conocer lo que tenemos disponible.' : null,
                $this->link($business),
            ]);
        }

        $precio = $this->precioTexto($product);

        return $this->join([
            $emoji ? "🎉 ¡{$product->name} disponible en {$business->name}!" : "{$product->name} disponible en {$business->name}.",
            $precio,
            $length !== 'corto' ? ($emoji ? '📲 Escríbenos por WhatsApp para separar el tuyo.' : 'Escríbenos por WhatsApp para más información.') : null,
            $length === 'largo' && $product->description ? $product->description : null,
            $this->link($business, $product),
        ]);
    }

    private function estado(Business $business, bool $emoji, string $length): string
    {
        $horario = $business->hoursNote();

        return $this->join([
            $emoji ? "📍 Hoy seguimos atendiendo en {$business->name}." : "{$business->name} sigue en atención.",
            $length !== 'corto' && $horario ? "Horario: {$horario}." : null,
            $this->link($business),
        ]);
    }

    private function respuesta(Business $business, bool $emoji, string $length): string
    {
        $listado = $business->faqAnswer('disponibilidad');

        if (! $listado) {
            $disponibles = $business->products()->where('status', 'publicado')->where('is_available', true)->pluck('name');

            $listado = $disponibles->isNotEmpty()
                ? 'Por ahora tenemos disponible: '.$disponibles->take(5)->implode(', ').'.'
                : 'Escríbeme y te cuento qué tenemos disponible en este momento.';
        }

        $horario = $business->faqAnswer('horario') ?? $business->hoursNote() ?? 'te confirmo el horario en un momento';
        $domicilio = $business->faqAnswer('domicilio') ?? ($business->activeAttributes()->contains('slug', 'domicilios-disponibles')
            ? 'Sí, hacemos domicilios.'
            : null);

        return $this->join([
            $emoji ? '¡Hola! Gracias por escribirnos 👋' : 'Hola, gracias por tu mensaje.',
            $listado,
            $length !== 'corto' ? "Nuestro horario es: {$horario}." : null,
            $length !== 'corto' ? $domicilio : null,
            $this->link($business),
        ]);
    }

    private function presentacion(Business $business, bool $emoji, string $length): string
    {
        $descripcion = $business->storefront?->description;

        return $this->join([
            $emoji ? "👋 ¡Hola! Somos {$business->name}." : "Hola, somos {$business->name}.",
            $length !== 'corto' ? $descripcion : null,
            $this->link($business),
        ]);
    }

    private function precioTexto(Product $product): ?string
    {
        return match ($product->price_type) {
            'exacto' => $product->price ? '$'.number_format((float) $product->price, 0, ',', '.') : null,
            'desde' => $product->price ? 'Desde $'.number_format((float) $product->price, 0, ',', '.') : null,
            'consultar' => 'Escríbenos para conocer el precio.',
            default => null,
        };
    }

    private function link(Business $business, ?Product $product = null): string
    {
        return $product
            ? route('vitrinas.product', [$business, $product])
            : route('vitrinas.show', $business);
    }

    /**
     * @param  array<int, string|null>  $lines
     */
    private function join(array $lines): string
    {
        return implode("\n\n", array_values(array_filter($lines, fn (?string $line) => filled($line))));
    }

    private function assist(string $draft, Business $business, string $type, ?Product $product, string $tone, string $length): ?string
    {
        return $this->assistedText->generate(
            'Reescribe este texto para WhatsApp en español usando exclusivamente el contexto real entregado. '.
            'No inventes precios, horarios, cobertura, stock, promociones, testimonios ni condiciones de pago. '.
            'Si falta un dato, no lo agregues. Mantén el objetivo del tipo de mensaje, el tono y la longitud. '.
            'Devuelve solo el texto final listo para copiar.',
            [
                'draft' => $draft,
                'message_type' => $type,
                'tone' => $tone,
                'length' => $length,
                'business' => [
                    'name' => $business->name,
                    'headline' => $business->storefront?->headline,
                    'description' => $business->storefront?->description,
                    'municipality' => $business->municipality?->name,
                    'category' => $business->category?->name,
                    'hours_note' => $business->hoursNote(),
                    'faq_answers' => $business->whatsapp_faq_answers,
                    'public_url' => route('vitrinas.show', $business),
                ],
                'product' => $product ? [
                    'name' => $product->name,
                    'description' => $product->description,
                    'price_type' => $product->price_type,
                    'price' => $product->price,
                    'public_url' => route('vitrinas.product', [$business, $product]),
                ] : null,
            ],
        );
    }
}
