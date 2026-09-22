<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\ProductFile;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Guarda (o reemplaza) el archivo entregable de un producto digital
 * (Fase 9 del TODO social) — un archivo por producto en este alcance;
 * subir uno nuevo borra el anterior en vez de acumular versiones.
 */
class UpdateProductFile
{
    public function handle(Product $product, UploadedFile $file): ProductFile
    {
        $existing = $product->files()->first();

        $path = app(MediaUploader::class)->store($file, 'product_digital_file', "products/{$product->id}/files");

        if ($existing) {
            app(MediaUploader::class)->delete($existing->path, 'private');
            $existing->update([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
            ]);

            return $existing;
        }

        return $product->files()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ]);
    }
}
