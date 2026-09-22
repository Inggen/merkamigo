<?php

namespace App\Domain\Social\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\PostComment;
use App\Domain\Social\Notifications\PostCommented;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Support\Facades\Validator;

/**
 * Comenta un post (2.2 del TODO social). Reutiliza `NoLinks` igual que el
 * resto de texto libre del proyecto (0.4 del TODO general: no duplicar
 * reglas de moderación anti-spam).
 */
class CreatePostComment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Post $post, array $data, User $actor): PostComment
    {
        $validated = Validator::make($data, [
            'body' => ['required', 'string', 'max:1000', new NoLinks],
        ])->validate();

        $comment = $post->comments()->create([
            'user_id' => $actor->id,
            'body' => $validated['body'],
            'status' => 'publicado',
        ]);

        app(RecordAuditLog::class)->handle($actor, 'post.commented', $post, [
            'comment_id' => $comment->id,
        ]);

        // Fase 11 del TODO social ("comentario"): nunca notificarse a sí
        // mismo si el dueño comenta su propio post.
        if ($post->user_id !== $actor->id) {
            $post->user->notify(new PostCommented($post, $comment));
        }

        return $comment;
    }
}
