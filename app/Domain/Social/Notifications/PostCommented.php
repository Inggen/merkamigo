<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\PostComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alguien comentó un post del negocio (Fase 11 del TODO social: tipo
 * "comentario"). Se envía al autor del post, nunca a sí mismo.
 */
class PostCommented extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Post $post, private readonly PostComment $comment) {}

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
            'type' => 'post_commented',
            'post_id' => $this->post->id,
            'comment_id' => $this->comment->id,
            'commenter_name' => $this->comment->user->name,
            'message' => __(':name comentó tu publicación: ":body"', [
                'name' => $this->comment->user->name,
                'body' => str($this->comment->body)->limit(60),
            ]),
            'url' => route('vitrinas.show', $this->post->business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Nuevo comentario'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
