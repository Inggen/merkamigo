<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Post;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Mismo patrón que `StoresProductMedia` (0.4 del TODO: no duplicar reglas).
 */
trait StoresPostMedia
{
    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storePhotos(Post $post, array $photos): void
    {
        $nextPosition = (int) $post->media()->max('position') + 1;

        foreach ($photos as $photo) {
            $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: '');
            $context = in_array($extension, ['mp4', 'webm', 'mov'], true) ? 'post_video' : 'post_photo';

            $path = app(MediaUploader::class)->store(
                $photo,
                $context,
                "posts/{$post->id}",
            );

            $post->media()->create([
                'path' => $path,
                'position' => $nextPosition++,
            ]);
        }
    }
}
