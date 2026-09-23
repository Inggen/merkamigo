<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un negocio publicó contenido nuevo para la comunidad de Merkamigo.
 * Alimenta el mismo Centro de actividad ya existente (`clientes.actividad`)
 * — esa vista ya renderiza cualquier notificación de forma genérica
 * (`data.message`/`data.url`), no hace falta tocarla.
 */
class NewPostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

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
        $business = $this->post->business;

        $isReel = $this->post->type === 'video';

        return [
            'type' => $isReel ? 'new_reel' : 'new_post',
            'post_id' => $this->post->id,
            'business_id' => $business->id,
            'business_name' => $business->name,
            'message' => $isReel
                ? __(':business publicó un nuevo reel.', ['business' => $business->name])
                : __(':business hizo una nueva publicación.', ['business' => $business->name]),
            'url' => $isReel ? route('reels') : route('home'),
            'action_label' => $isReel ? __('Ver reel') : __('Ver publicación'),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => $this->post->type === 'video' ? __('Nuevo reel') : __('Nueva publicación'),
            'body' => $data['message'],
            'url' => $data['url'],
        ];
    }
}
