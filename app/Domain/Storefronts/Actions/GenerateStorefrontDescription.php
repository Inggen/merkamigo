<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\Contracts\GeneratesAssistedText;

/**
 * Genera una descripción de venta para la vitrina a partir de los datos
 * reales que el emprendedor ya llenó en el editor (pedido del usuario: un
 * botón "Mejorar descripción" que lea todos los campos y proponga un
 * texto vendedor) — nunca inventa productos, atributos ni ubicación que
 * no estén en el contexto.
 */
class GenerateStorefrontDescription
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @param  array{headline: ?string, zone: ?string, description: ?string}  $draft  Valores todavía sin guardar del formulario — priman sobre lo que ya tiene el negocio en base de datos.
     */
    public function handle(Business $business, array $draft = []): ?string
    {
        $context = [
            'nombre' => $business->name,
            'frase_corta' => $draft['headline'] ?? $business->storefront?->headline,
            'categoria' => $business->category?->name,
            'municipio' => $business->municipality?->name,
            'zona_o_barrio' => $draft['zone'] ?? $business->zone,
            'atributos' => $business->activeAttributes()->pluck('name')->all(),
            'productos_o_servicios' => $business->products()->where('status', 'publicado')->pluck('name')->all(),
            'descripcion_actual' => $draft['description'] ?? $business->storefront?->description,
        ];

        $generated = $this->assistedText->generate($this->prompt(), $context);

        return $generated !== null ? trim($generated) : null;
    }

    private function prompt(): string
    {
        return
            'Escribes la descripción de la vitrina de un negocio local colombiano en Merkamigo, a partir '.
            'SOLO de los datos reales del contexto — nunca inventes productos, atributos, ubicación ni '.
            'nada que no esté ahí. Tono cercano y vendedor, como si el propio dueño la escribiera con '.
            'ayuda, sin sonar exagerado ni robotizado. Entre 2 y 4 frases, sin emojis, sin encabezados ni '.
            'comillas. Si ya hay "descripcion_actual", mejórala o reescríbela en vez de partir de cero. '.
            'Responde ÚNICAMENTE con el texto final de la descripción, sin nada más alrededor.';
    }
}
