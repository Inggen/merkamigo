<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Guarda archivos subidos por el usuario validando contra los límites de
 * `config/media.php` (0.6 del TODO: "validar y limitar archivos por tipo,
 * tamaño y cantidad"). Usa el disco `public` en desarrollo; en
 * producción este disco debe apuntar a almacenamiento S3-compatible (ver
 * docs/architecture/decisiones.md) — no se redimensiona ni se generan
 * variantes todavía (no hay librería de imágenes instalada).
 */
class MediaUploader
{
    public function store(UploadedFile $file, string $context, string $directory): string
    {
        $rules = config("media.{$context}");

        Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', $rules['mimes']),
                'max:'.$rules['max_kb'],
            ],
        ])->validate();

        $path = $file->store($directory, 'public');

        if ($path === false) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
