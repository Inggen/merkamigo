<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Social\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Compatibilidad para notificaciones encoladas antes del envío global.
 */
class NewPostFromFollowedBusiness extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

    public function via(object $notifiable): array
    {
        return (new NewPostPublished($this->post))->via($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return (new NewPostPublished($this->post))->toArray($notifiable);
    }

    public function toPush(object $notifiable): array
    {
        return (new NewPostPublished($this->post))->toPush($notifiable);
    }
}
