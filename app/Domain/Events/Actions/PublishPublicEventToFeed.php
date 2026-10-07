<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Events\PostPublished;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * "Publicar en el feed" desde un evento ya publicado
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5): crea un post
 * vinculado con enlace a la ficha del evento; `posts.public_event_id` es
 * único en BD, así que un segundo intento (doble clic, carrera) nunca
 * produce un segundo post — se traduce aquí en un mensaje legible en vez
 * de dejar pasar la excepción SQL.
 */
class PublishPublicEventToFeed
{
    public function handle(PublicEvent $event, User $actor): Post
    {
        if ($event->status !== PublicEvent::PUBLICADO) {
            throw new EventActionException('Solo puedes publicar en el feed un evento ya publicado.');
        }

        if ($event->post()->exists()) {
            throw new EventActionException('Este evento ya tiene una publicación en el feed.');
        }

        $body = $event->title;

        if ($event->description) {
            $body .= "\n\n".$event->description;
        }

        $body .= "\n\n".__(':date', ['date' => $event->starts_at->translatedFormat('l j \d\e F, g:i a')]);

        try {
            return DB::transaction(function () use ($event, $actor, $body) {
                $post = $event->business->posts()->create([
                    'user_id' => $actor->id,
                    'public_event_id' => $event->id,
                    'type' => 'texto',
                    'body' => $body,
                    'status' => 'publicado',
                    'published_at' => now(),
                ]);

                app(RecordAuditLog::class)->handle($actor, 'public_event.published_to_feed', $event, [
                    'post_id' => $post->id,
                ]);

                event(new PostPublished($post->id));

                return $post;
            });
        } catch (QueryException $e) {
            throw new EventActionException('Este evento ya tiene una publicación en el feed.', previous: $e);
        }
    }
}
