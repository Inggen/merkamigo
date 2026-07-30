<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Crea o actualiza el borrador de una necesidad (2.1 del TODO: "guardar
 * borrador"). No cambia el estado — una necesidad ya publicada puede
 * seguir editándose con esta misma acción sin que eso la regrese a
 * borrador (2.1: "edición, cancelación y cierre").
 */
class SaveNeedDraft
{
    use StoresNeedMedia, ValidatesNeedData;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function handle(User $user, ?Need $need, array $data, array $photos = []): Need
    {
        $validated = Validator::make($data, $this->rules(partial: true))->validate();

        $existingPhotos = $need?->media()->count() ?? 0;
        $this->validatePhotoCount($existingPhotos, count($photos));

        return DB::transaction(function () use ($user, $need, $validated, $photos) {
            if ($need) {
                $need->update($validated);
            } else {
                $need = $user->needs()->create([
                    ...$validated,
                    'status' => Need::BORRADOR,
                ]);
            }

            $this->storeNeedPhotos($need, $photos);

            return $need->fresh(['media']);
        });
    }
}
