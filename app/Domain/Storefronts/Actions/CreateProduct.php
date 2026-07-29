<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateProduct
{
    use StoresProductMedia, SyncsProductVariants, ValidatesProductData;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function handle(Business $business, array $data, array $photos, User $actor): Product
    {
        $validated = Validator::make($data, $this->rules())->validate();

        $this->validatePhotoCount(0, count($photos));

        $variants = $validated['variants'] ?? null;
        unset($validated['variants']);

        return DB::transaction(function () use ($business, $validated, $variants, $photos, $actor) {
            $product = $business->products()->create([
                ...$validated,
                'slug' => $this->uniqueSlug($business, $validated['name']),
                'position' => $business->products()->max('position') + 1,
            ]);

            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }

            $this->storePhotos($product, $photos);

            app(RecordAuditLog::class)->handle($actor, 'product.created', $product);

            return $product->load(['media', 'variants']);
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
