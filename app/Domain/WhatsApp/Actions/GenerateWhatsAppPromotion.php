<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use InvalidArgumentException;

/**
 * Genera el texto de una plantilla del Copiloto de WhatsApp (1.7 del TODO)
 * a partir de datos reales del negocio/producto — nunca inventa precios ni
 * condiciones, y nunca envía nada: solo produce texto editable para que el
 * emprendedor lo revise, copie y comparta él mismo.
 */
class GenerateWhatsAppPromotion
{
    /**
     * @var array<int, string>
     */
    private const TYPES = [
        WhatsAppContent::PROMOCION,
        WhatsAppContent::ESTADO,
        WhatsAppContent::RESPUESTA,
        WhatsAppContent::PRESENTACION,
    ];

    public function handle(Business $business, string $type, ?Product $product, string $tone): string
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Tipo de contenido inválido: {$type}");
        }

        $emoji = $tone === 'cercano';

        return match ($type) {
            WhatsAppContent::PROMOCION => $this->promocion($business, $product, $emoji),
            WhatsAppContent::ESTADO => $this->estado($business, $emoji),
            WhatsAppContent::RESPUESTA => $this->respuesta($business, $emoji),
            WhatsAppContent::PRESENTACION => $this->presentacion($business, $emoji),
        };
    }

    private function promocion(Business $business, ?Product $product, bool $emoji): string
    {
        if (! $product) {
            return $this->join([
                $emoji ? "🎉 ¡Novedades en {$business->name}!" : "Novedades en {$business->name}.",
                'Escríbenos por WhatsApp para conocer lo que tenemos disponible.',
                $this->link($business),
            ]);
        }

        $precio = $this->precioTexto($product);

        return $this->join([
            $emoji ? "🎉 ¡{$product->name} disponible en {$business->name}!" : "{$product->name} disponible en {$business->name}.",
            $precio,
            $emoji ? '📲 Escríbenos por WhatsApp para separar el tuyo.' : 'Escríbenos por WhatsApp para más información.',
            $this->link($business, $product),
        ]);
    }

    private function estado(Business $business, bool $emoji): string
    {
        $horario = $business->hoursNote();

        return $this->join([
            $emoji ? "📍 Hoy seguimos atendiendo en {$business->name}." : "{$business->name} sigue en atención.",
            $horario ? "Horario: {$horario}." : null,
            $this->link($business),
        ]);
    }

    private function respuesta(Business $business, bool $emoji): string
    {
        $disponibles = $business->products()->where('status', 'publicado')->where('is_available', true)->pluck('name');

        $listado = $disponibles->isNotEmpty()
            ? 'Por ahora tenemos disponible: '.$disponibles->take(5)->implode(', ').'.'
            : 'Escríbeme y te cuento qué tenemos disponible en este momento.';

        $horario = $business->hoursNote() ?? 'te confirmo el horario en un momento';

        return $this->join([
            $emoji ? '¡Hola! Gracias por escribirnos 👋' : 'Hola, gracias por tu mensaje.',
            $listado,
            "Nuestro horario es: {$horario}.",
            $this->link($business),
        ]);
    }

    private function presentacion(Business $business, bool $emoji): string
    {
        $descripcion = $business->storefront?->description;

        return $this->join([
            $emoji ? "👋 ¡Hola! Somos {$business->name}." : "Hola, somos {$business->name}.",
            $descripcion,
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
}
