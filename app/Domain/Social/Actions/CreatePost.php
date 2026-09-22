<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Events\PostPublished;
use App\Domain\Social\Models\Post;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Crea una publicación de negocio (2.2 del TODO social, Sprint 2). Reduce
 * el alcance del TODO a propósito para este sprint: se publica de
 * inmediato salvo que se pida explícitamente `status: borrador` — no hay
 * flujo de edición de borrador todavía, eso queda para cuando haya
 * demanda real de ese caso.
 */
class CreatePost
{
    use StoresPostMedia;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function handle(Business $business, array $data, array $photos, User $actor): Post
    {
        $validated = Validator::make($data, [
            'type' => ['required', 'in:texto,imagen,carrusel,promocion,video'],
            'body' => ['nullable', 'string', 'max:5000', new NoLinks],
            'status' => ['sometimes', 'in:borrador,publicado'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'distinct'],
        ])->validate();

        if (blank($validated['body'] ?? null) && $photos === []) {
            throw ValidationException::withMessages([
                'body' => 'Escribe algo o agrega al menos una foto para publicar.',
            ]);
        }

        if ($validated['type'] === 'video' && count($photos) !== 1) {
            throw ValidationException::withMessages([
                'body' => 'Un reel necesita exactamente un video.',
            ]);
        }

        $productIds = $business->products()
            ->whereIn('id', $validated['product_ids'] ?? [])
            ->pluck('id');

        $status = $validated['status'] ?? 'publicado';

        return DB::transaction(function () use ($business, $validated, $photos, $actor, $productIds, $status) {
            $post = $business->posts()->create([
                'user_id' => $actor->id,
                'type' => $validated['type'],
                'body' => $validated['body'] ?? null,
                'status' => $status,
                'published_at' => $status === 'publicado' ? now() : null,
            ]);

            $this->storePhotos($post, $photos);

            if ($productIds->isNotEmpty()) {
                $post->products()->sync($productIds);
            }

            app(RecordAuditLog::class)->handle($actor, 'post.created', $post, [
                'business_id' => $business->id,
            ]);

            if ($status === 'publicado') {
                event(new PostPublished($post->id));
            }

            return $post->load(['media', 'products']);
        });
    }
}
