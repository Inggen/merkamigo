<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Models\EventSpace;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * CRUD de espacios/salas reservables (Fase 2 · pestaña Agenda). El MVP
 * suele exponer uno solo en la UI, pero el esquema admite varios desde el
 * inicio sin rediseño (reglas de producto).
 */
class ManageEventSpace
{
    public function __construct(private readonly MediaUploader $mediaUploader) {}

    /**
     * @param  array{name: string, description?: ?string, capacity?: ?int}  $data
     */
    public function create(Business $business, array $data, ?UploadedFile $image = null): EventSpace
    {
        if ($image) {
            $data['image_path'] = $this->storeImage($business, $image);
        }

        // `Model::create()` no vuelve a leer la fila: si no se fija
        // `is_active` aquí, el objeto en memoria queda con el atributo sin
        // definir (no el default `true` de la migración) y cualquier
        // chequeo de disponibilidad justo después de crear fallaría.
        return $business->eventSpaces()->create([...$data, 'is_active' => true]);
    }

    /**
     * @param  array{name?: string, description?: ?string, capacity?: ?int, is_active?: bool}  $data
     */
    public function update(EventSpace $space, array $data, ?UploadedFile $image = null): EventSpace
    {
        $previousImage = $space->image_path;

        if ($image) {
            $data['image_path'] = $this->storeImage($space->business, $image);
        }

        $space->update($data);

        if ($image) {
            $this->mediaUploader->delete($previousImage);
        }

        return $space;
    }

    public function delete(EventSpace $space): void
    {
        $this->mediaUploader->delete($space->image_path);
        $space->delete();
    }

    private function storeImage(Business $business, UploadedFile $image): string
    {
        return $this->mediaUploader->store($image, 'event_space', "event-spaces/{$business->id}");
    }
}
