<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\ContentPromotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateContentPromotion
{
    public function handle(Business $business, Model $content, array $segmentation, User $actor): ContentPromotion
    {
        if ((int) $content->getAttribute('business_id') !== $business->id) {
            throw ValidationException::withMessages(['content' => __('El contenido debe pertenecer al negocio actual.')]);
        }

        $validated = validator($segmentation, [
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'radius_km' => ['nullable', 'integer', 'in:1,3,5,10,20'],
        ])->validate();

        return ContentPromotion::create([
            'business_id' => $business->id,
            'created_by_user_id' => $actor->id,
            'promotable_type' => $content->getMorphClass(),
            'promotable_id' => $content->getKey(),
            'municipality_id' => $validated['municipality_id'] ?? $business->municipality_id,
            'category_id' => $validated['category_id'] ?? $business->category_id,
            'radius_km' => $validated['radius_km'] ?? null,
            'status' => ContentPromotion::BORRADOR,
        ]);
    }
}
