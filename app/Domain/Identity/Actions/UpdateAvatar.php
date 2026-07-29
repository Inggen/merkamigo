<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Actualiza o quita la foto de perfil del usuario (header, 0.6 del TODO:
 * "validar y limitar archivos por tipo, tamaño y cantidad" ya cubierto por
 * `config('media.avatar')` vía MediaUploader).
 */
class UpdateAvatar
{
    public function handle(User $user, UploadedFile $file): User
    {
        app(MediaUploader::class)->delete($user->avatar_path);

        $user->avatar_path = app(MediaUploader::class)->store($file, 'avatar', "users/{$user->id}");
        $user->save();

        return $user;
    }

    public function remove(User $user): User
    {
        app(MediaUploader::class)->delete($user->avatar_path);

        $user->avatar_path = null;
        $user->save();

        return $user;
    }
}
