<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\ProductMedia;
use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateProduct
{
    use StoresProductMedia, ValidatesProductData;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $newPhotos
     * @param  array<int, int>  $removeMediaIds
     */
    public function handle(
        Product $product,
        array $data,
        array $newPhotos,
        array $removeMediaIds,
        User $actor,
    ): Product {
        $validated = Validator::make($data, $this->rules(partial: true))->validate();

        $remaining = $product->media()->count() - count($removeMediaIds);
        $this->validatePhotoCount($remaining, count($newPhotos));

        return DB::transaction(function () use ($product, $validated, $newPhotos, $removeMediaIds, $actor) {
            $product->update($validated);

            if ($removeMediaIds !== []) {
                $product->media()->whereIn('id', $removeMediaIds)->get()->each(function (ProductMedia $media) {
                    app(MediaUploader::class)->delete($media->path);
                    $media->delete();
                });
            }

            $this->storePhotos($product, $newPhotos);

            app(RecordAuditLog::class)->handle($actor, 'product.updated', $product);

            $product->refresh()->load('media');

            return $product;
        });
    }
}
