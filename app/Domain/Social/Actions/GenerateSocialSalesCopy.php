<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Validation\Rule;

class GenerateSocialSalesCopy
{
    public function __construct(private readonly GeneratesAssistedText $assistedText) {}

    /**
     * @param  array<string, mixed>  $draft
     */
    public function handle(Business $business, string $channel, array $draft = []): ?string
    {
        validator(['channel' => $channel], [
            'channel' => ['required', Rule::in(['publicacion', 'estado', 'reel', 'live'])],
        ])->validate();

        $productIds = collect($draft['product_ids'] ?? [])->filter()->map(fn ($id) => (int) $id);
        $products = $business->products()
            ->whereIn('id', $productIds)
            ->get(['name', 'description', 'price', 'price_type', 'unit'])
            ->map(fn ($product) => [
                'nombre' => $product->name,
                'descripcion' => $product->description,
                'precio' => in_array($product->price_type, ['exacto', 'desde'], true) ? $product->price : null,
                'tipo_de_precio' => $product->price_type,
                'unidad' => $product->unit,
            ])
            ->all();

        $context = [
            'canal' => $channel,
            'negocio' => $business->name,
            'categoria' => $business->category?->name,
            'municipio' => $business->municipality?->name,
            'texto_actual' => $draft['text'] ?? null,
            'tipo_de_contenido' => $draft['type'] ?? null,
            'productos_seleccionados' => $products,
        ];

        $generated = $this->assistedText->generate($this->prompt($channel), $context);

        return filled($generated) ? trim($generated) : null;
    }

    private function prompt(string $channel): string
    {
        $format = match ($channel) {
            'estado' => 'Máximo 180 caracteres, directo y oportuno.',
            'reel' => 'Máximo 280 caracteres, una apertura atractiva y un llamado a actuar.',
            'live' => 'Escribe una descripción de 2 frases que explique qué verá la audiencia y la invite a entrar.',
            default => 'Entre 2 y 4 frases, fáciles de leer en un feed.',
        };

        return
            'Eres el asistente de ventas de un negocio local colombiano en Merkamigo. Escribe un borrador para el canal indicado usando SOLO los datos del contexto. '.
            'No inventes descuentos, disponibilidad, propiedades, precios ni condiciones. Si existe texto_actual, mejóralo. '.
            'Tono cercano, claro y profesional; máximo un emoji y sin hashtags genéricos. '.$format.' '.
            'No incluyas encabezados, comillas ni explicaciones. Devuelve únicamente el texto editable.';
    }
}
