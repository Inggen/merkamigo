<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\Story;
use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Crea un estado (Fase 3 del TODO social, Sprint 3). Expira a las 24h por
 * defecto — no configurable todavía, coincide con "duración por defecto:
 * 24 horas" del TODO; permitir elegir duración queda para cuando haya
 * demanda real.
 */
class CreateStory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, array $data, UploadedFile $photo, User $actor): Story
    {
        $validated = Validator::make($data, [
            'type' => ['required', 'in:imagen,promocion,producto,servicio'],
            'caption' => ['nullable', 'string', 'max:280'],
            'product_id' => ['sometimes', 'nullable', 'integer'],
        ])->validate();

        $productId = null;

        if (! blank($validated['product_id'] ?? null)) {
            $productId = $business->products()->whereKey($validated['product_id'])->value('id');
        }

        return DB::transaction(function () use ($business, $validated, $photo, $actor, $productId) {
            $path = app(MediaUploader::class)->store($photo, 'story_photo', "stories/{$business->id}");

            $story = $business->stories()->create([
                'user_id' => $actor->id,
                'product_id' => $productId,
                'type' => $validated['type'],
                'image_path' => $path,
                'caption' => $validated['caption'] ?? null,
                'expires_at' => now()->addHours(24),
            ]);

            app(RecordAuditLog::class)->handle($actor, 'story.created', $story, [
                'business_id' => $business->id,
            ]);

            return $story;
        });
    }
}
