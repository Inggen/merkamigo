<?php

namespace App\Domain\Needs\Actions;

use App\Support\Validation\Rules\NoLinks;

/**
 * Reglas de validación de propuesta compartidas (0.4 del TODO).
 */
trait ValidatesOfferData
{
    /**
     * @return array<string, array<int, mixed>>
     */
    private function offerRules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000', new NoLinks],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'availability' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
