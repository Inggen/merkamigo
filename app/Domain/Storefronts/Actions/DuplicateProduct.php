<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Duplica un producto (1.4 del TODO) para acelerar la carga de artículos
 * parecidos: copia campos, variantes y fotos; el duplicado nace en
 * `borrador` para que el emprendedor lo revise antes de publicarlo.
 */
class DuplicateProduct
{
    public function handle(Product $product, User $actor): Product
    {
        $product->loadMissing(['media', 'variants']);

        return DB::transaction(function () use ($product, $actor) {
            $business = $product->business;

            $duplicate = $business->products()->create([
                'name' => $product->name,
                'slug' => $this->uniqueSlug($business, $product->name),
                'type' => $product->type,
                'description' => $product->description,
                'price' => $product->price,
                'price_type' => $product->price_type,
                'unit' => $product->unit,
                'promo_price' => $product->promo_price,
                'promo_label' => $product->promo_label,
                'promo_starts_at' => $product->promo_starts_at,
                'promo_ends_at' => $product->promo_ends_at,
                'is_available' => $product->is_available,
                'status' => 'borrador',
                'position' => $business->products()->max('position') + 1,
            ]);

            foreach ($product->variants as $variant) {
                $duplicate->variants()->create([
                    'label' => $variant->label,
                    'price' => $variant->price,
                    'position' => $variant->position,
                ]);
            }

            foreach ($product->media as $media) {
                $newPath = "products/{$duplicate->id}/".basename($media->path);
                Storage::disk('public')->copy($media->path, $newPath);

                $duplicate->media()->create([
                    'path' => $newPath,
                    'position' => $media->position,
                ]);
            }

            app(RecordAuditLog::class)->handle($actor, 'product.duplicated', $duplicate);

            return $duplicate->load(['media', 'variants']);
        });
    }

    private function uniqueSlug(Business $business, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $attempt = 1;

        while ($business->products()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".(++$attempt);
        }

        return $slug;
    }
}
