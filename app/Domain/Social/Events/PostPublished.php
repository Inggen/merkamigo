<?php

namespace App\Domain\Social\Events;

/**
 * Un post quedó visible públicamente (2.3 del TODO social: "notificar
 * contenido nuevo según preferencias del usuario") — dispara la
 * notificación a seguidores del negocio, ver
 * `App\Domain\Social\Jobs\NotifyFollowersOfNewPost`.
 */
class PostPublished
{
    public function __construct(public readonly int $postId) {}
}
