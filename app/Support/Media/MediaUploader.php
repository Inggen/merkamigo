<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Guarda archivos subidos por el usuario validando contra los límites de
 * `config/media.php` (0.6 del TODO: "validar y limitar archivos por tipo,
 * tamaño y cantidad"). Usa el disco `public` en desarrollo; en
 * producción este disco debe apuntar a almacenamiento S3-compatible (ver
 * docs/architecture/decisiones.md).
 *
 * Cuando el contexto define `max_width` (1.2 del TODO: "optimizar,
 * comprimir y generar variantes de imágenes"), la imagen se reduce con
 * Intervention Image antes de guardarse — nunca se agranda, y se
 * conserva el formato original (importante para no romper la
 * transparencia de logos en PNG).
 *
 * El mismo paso de `storeResized()` cubre además "analizar archivos y
 * remover metadatos sensibles" (0.6 del TODO): el driver GD de
 * Intervention Image no lee ni conserva EXIF al recodificar, así que
 * cualquier ubicación GPS o dato del dispositivo embebido en la foto
 * original se descarta. Esto aplica a avatar, logo, portadas y fotos de
 * producto/necesidad/municipio; `verification_document` (PDF, Fase 3) no
 * pasa por aquí todavía.
 */
class MediaUploader
{
    public function store(UploadedFile $file, string $context, string $directory): string
    {
        $rules = config("media.{$context}");
        $disk = $rules['disk'] ?? 'public';

        Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', $rules['mimes']),
                'max:'.$rules['max_kb'],
            ],
        ])->validate();

        if (isset($rules['max_width'])) {
            return $this->storeResized($file, $directory, $rules['max_width'], $disk);
        }

        $path = $file->store($directory, $disk);

        if ($path === false) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return $path;
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function url(?string $path, string $disk = 'public'): ?string
    {
        return $path ? Storage::disk($disk)->url($path) : null;
    }

    private function storeResized(UploadedFile $file, string $directory, int $maxWidth, string $disk): string
    {
        $image = (new ImageManager(Driver::class))->decode($file);
        $image->scaleDown(width: $maxWidth);

        $path = $file->hashName($directory);

        Storage::disk($disk)->put($path, (string) $image->encode());

        return $path;
    }
}
