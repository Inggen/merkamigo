<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Need;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Sube y adjunta fotos a una necesidad (2.1 del TODO: "fotos opcionales").
 * Mismo patrón que `StoresProductMedia`.
 */
trait StoresNeedMedia
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storeNeedPhotos(Need $need, array $photos): void
    {
        $nextPosition = (int) $need->media()->max('position') + 1;

        foreach ($photos as $photo) {
            $path = app(MediaUploader::class)->store(
                $photo,
                'need_photo',
                "needs/{$need->id}",
            );

            $need->media()->create([
                'path' => $path,
                'position' => $nextPosition++,
            ]);
        }
    }
}
