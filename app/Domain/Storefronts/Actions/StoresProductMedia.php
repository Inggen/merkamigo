<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Sube y adjunta fotos a un producto, compartido por CreateProduct y
 * UpdateProduct (0.4 del TODO: no duplicar reglas).
 */
trait StoresProductMedia
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storePhotos(Product $product, array $photos): void
    {
        $nextPosition = (int) $product->media()->max('position') + 1;

        foreach ($photos as $photo) {
            $path = app(MediaUploader::class)->store(
                $photo,
                'product_photo',
                "products/{$product->id}",
            );

            $product->media()->create([
                'path' => $path,
                'position' => $nextPosition++,
            ]);
        }
    }
}
