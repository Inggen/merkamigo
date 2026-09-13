<?php

namespace App\Domain\Storefronts\Actions;

use App\Support\Validation\Rules\NoLinks;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de validación de producto compartidas por CreateProduct y
 * UpdateProduct, para no duplicarlas (0.4 del TODO).
 */
trait ValidatesProductData
{
    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255', new NoLinks],
            'type' => [$required, 'in:producto,servicio'],
            'description' => ['sometimes', 'nullable', 'string', new NoLinks],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_type' => ['sometimes', 'in:exacto,desde,consultar,sin_precio'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:100'],
            'promo_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'promo_label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'promo_starts_at' => ['sometimes', 'nullable', 'date'],
            'promo_ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:promo_starts_at'],
            'is_available' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:borrador,publicado,agotado,archivado'],
            // Identificadores opcionales para Google Shopping (0.5 del
            // TODO de la integración con Google Merchant): nunca
            // obligatorios, un producto artesanal puede no tenerlos.
            'gtin' => ['sometimes', 'nullable', 'string', 'max:14'],
            'mpn' => ['sometimes', 'nullable', 'string', 'max:70'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'condition' => ['sometimes', 'in:nuevo,usado,reacondicionado'],
            'variants' => ['sometimes', 'array'],
            'variants.*.label' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    private function validatePhotoCount(int $existing, int $incoming): void
    {
        $max = config('media.product_photo.max_files');

        if ($existing + $incoming > $max) {
            throw ValidationException::withMessages([
                'photos' => ["No puedes tener más de {$max} fotos por producto."],
            ]);
        }
    }
}
