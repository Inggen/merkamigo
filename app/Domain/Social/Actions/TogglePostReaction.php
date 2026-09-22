<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\PostReaction;
use App\Domain\Social\Notifications\PostReacted;
use App\Models\User;

/**
 * "Me gusta" en un post (2.2 del TODO social). Mismo patrón que
 * `ToggleFavorite` — un solo tipo de reacción por ahora.
 */
class TogglePostReaction
{
    /**
     * @return bool true si quedó reaccionado, false si se quitó.
     */
    public function handle(User $user, Post $post): bool
    {
        $existing = PostReaction::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        PostReaction::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        // Fase 11 del TODO social ("reacción"): nunca notificarse a sí
        // mismo si el dueño reacciona a su propio post.
        if ($post->user_id !== $user->id) {
            $post->user->notify(new PostReacted($post, $user));
        }

        return true;
    }
}
