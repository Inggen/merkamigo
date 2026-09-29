<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Sube y adjunta medios a un producto, compartido por CreateProduct y
 * UpdateProduct (0.4 del TODO: no duplicar reglas).
 */
trait StoresProductMedia
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storeMedia(Product $product, array $photos, ?UploadedFile $video = null): void
    {
        if ($video) {
            if (! $product->media()->where('type', 'video')->exists()) {
                $product->media()->where('type', 'image')->increment('position');
            }

            $path = app(MediaUploader::class)->store(
                $video,
                'product_video',
                "products/{$product->id}",
            );

            $product->media()->create([
                'path' => $path,
                'type' => 'video',
                'position' => 0,
            ]);
        }

        $nextPosition = (int) $product->media()->max('position') + 1;

        foreach ($photos as $photo) {
            $path = app(MediaUploader::class)->store(
                $photo,
                'product_photo',
                "products/{$product->id}",
            );

            $product->media()->create([
                'path' => $path,
                'type' => 'image',
                'position' => $nextPosition++,
            ]);
        }
    }
}
