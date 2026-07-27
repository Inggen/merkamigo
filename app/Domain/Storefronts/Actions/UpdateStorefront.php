<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Actualiza los datos editables de negocio + vitrina. La usan los pasos
 * 1-3 de "Mi Merkamigo en cinco minutos" (autosave) y el editor de vitrina
 * del panel (`/emprendedores/negocios/{business}/vitrina`), sin duplicar
 * reglas (0.4 del TODO).
 */
class UpdateStorefront
{
    private const BUSINESS_FIELDS = [
        'name', 'zone', 'address', 'municipality_id', 'category_id',
        'whatsapp_number', 'hours', 'social_links', 'payment_info', 'attributes',
    ];

    private const STOREFRONT_FIELDS = ['headline', 'description'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, array $data, User $actor): Storefront
    {
        $validated = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'zone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'municipality_id' => ['sometimes', 'nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'whatsapp_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'hours' => ['sometimes', 'nullable', 'array'],
            'social_links' => ['sometimes', 'nullable', 'array'],
            'payment_info' => ['sometimes', 'nullable', 'string'],
            'attributes' => ['sometimes', 'nullable', 'array'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'nullable'],
            'cover' => ['sometimes', 'nullable'],
        ])->validate();

        return DB::transaction(function () use ($business, $validated, $actor) {
            $business->fill(Arr::only($validated, self::BUSINESS_FIELDS));

            if (! empty($validated['logo']) && $validated['logo'] instanceof UploadedFile) {
                app(MediaUploader::class)->delete($business->logo_path);
                $business->logo_path = app(MediaUploader::class)->store(
                    $validated['logo'],
                    'business_logo',
                    "businesses/{$business->id}",
                );
            }

            $business->save();

            $storefront = $business->storefront;
            $storefront->fill(Arr::only($validated, self::STOREFRONT_FIELDS));

            if (! empty($validated['cover']) && $validated['cover'] instanceof UploadedFile) {
                app(MediaUploader::class)->delete($storefront->cover_path);
                $storefront->cover_path = app(MediaUploader::class)->store(
                    $validated['cover'],
                    'storefront_cover',
                    "storefronts/{$storefront->id}",
                );
            }

            $storefront->save();

            app(RecordAuditLog::class)->handle($actor, 'business.updated', $business);

            return $storefront->setRelation('business', $business);
        });
    }
}
