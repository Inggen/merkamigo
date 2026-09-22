<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alguien reaccionó a un post del negocio (Fase 11 del TODO social: tipo
 * "reacción"). Se envía al autor del post, nunca a sí mismo.
 */
class PostReacted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Post $post, private readonly User $reactor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_reacted',
            'post_id' => $this->post->id,
            'reactor_name' => $this->reactor->name,
            'message' => __(':name reaccionó a tu publicación.', ['name' => $this->reactor->name]),
            'url' => route('vitrinas.show', $this->post->business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Nueva reacción'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
