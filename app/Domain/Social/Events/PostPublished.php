<?php

namespace App\Domain\Social\Events;

/**
 * Un post quedó visible públicamente (2.3 del TODO social: "notificar
 * contenido nuevo") — dispara la notificación interna y push a todas
 * las personas registradas, ver `NotifyUsersOfNewPost`.
 */
class PostPublished
{
    public function __construct(public readonly int $postId) {}
}
