<?php

namespace App\Domain\Needs\Actions;

use App\Support\Validation\Rules\NoLinks;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de validación de necesidad compartidas por `SaveNeedDraft` y
 * `PublishNeed`, para no duplicarlas (0.4 del TODO). Mismo patrón que
 * `ValidatesProductData`.
 */
trait ValidatesNeedData
{
    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $partial = true): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255', new NoLinks],
            'description' => [$required, 'string', new NoLinks],
            // Nullable incluso cuando no es parcial: el requisito real de
            // "municipio obligatorio para publicar" lo aplica
            // `PublishNeed::missingFields()`, no este validador de borrador
            // — un borrador sin municipio todavía debe poder guardarse.
            'municipality_id' => [$required, 'nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'zone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    private function validatePhotoCount(int $existing, int $incoming): void
    {
        $max = config('media.need_photo.max_files');

        if ($existing + $incoming > $max) {
            throw ValidationException::withMessages([
                'photos' => ["No puedes tener más de {$max} fotos por solicitud."],
            ]);
        }
    }
}
