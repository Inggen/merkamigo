<?php

namespace App\Domain\Storefronts\Actions;

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
            'name' => [$required, 'string', 'max:255'],
            'type' => [$required, 'in:producto,servicio'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_type' => ['sometimes', 'in:exacto,desde,consultar,sin_precio'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:100'],
            'promo_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'promo_label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_available' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:borrador,publicado,agotado,archivado'],
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
