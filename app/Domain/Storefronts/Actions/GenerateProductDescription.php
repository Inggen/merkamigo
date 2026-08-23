<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\Contracts\GeneratesAssistedText;

/**
 * Genera una descripción de venta corta para un producto o servicio a
 * partir de los datos reales que el emprendedor ya llenó en el
 * formulario (pedido del usuario, mismo criterio que
 * `GenerateStorefrontDescription`) — nunca inventa precios, materiales
 * ni condiciones que no estén en el contexto.
 */
class GenerateProductDescription
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @param  array{name: string, type: string, price: ?float, price_type: string, unit: ?string, description: ?string}  $draft
     */
    public function handle(Business $business, array $draft): ?string
    {
        $context = [
            'nombre_del_producto_o_servicio' => $draft['name'],
            'es_servicio' => $draft['type'] === 'servicio',
            'negocio' => $business->name,
            'categoria_del_negocio' => $business->category?->name,
            'precio' => $draft['price_type'] === 'exacto' || $draft['price_type'] === 'desde' ? $draft['price'] : null,
            'tipo_de_precio' => $draft['price_type'],
            'unidad' => $draft['unit'],
            'descripcion_actual' => $draft['description'],
        ];

        $generated = $this->assistedText->generate($this->prompt(), $context);

        return $generated !== null ? trim($generated) : null;
    }

    private function prompt(): string
    {
        return
            'Escribes la descripción breve de un producto o servicio en el catálogo de un negocio local '.
            'colombiano en Merkamigo, a partir SOLO de los datos reales del contexto — nunca inventes '.
            'materiales, ingredientes, precios ni condiciones que no estén ahí. Tono cercano y vendedor, '.
            'sin sonar exagerado ni robotizado. 1 a 3 frases, sin emojis, sin encabezados ni comillas. Si '.
            'ya hay "descripcion_actual", mejórala o reescríbela en vez de partir de cero. Responde '.
            'ÚNICAMENTE con el texto final de la descripción, sin nada más alrededor.';
    }
}
