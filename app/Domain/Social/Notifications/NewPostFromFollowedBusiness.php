<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un negocio que el usuario sigue publicó contenido nuevo (2.3 del TODO
 * social: "notificar contenido nuevo según preferencias del usuario").
 * Alimenta el mismo Centro de actividad ya existente (`clientes.actividad`)
 * — esa vista ya renderiza cualquier notificación de forma genérica
 * (`data.message`/`data.url`), no hace falta tocarla.
 */
class NewPostFromFollowedBusiness extends Notification implements ShouldQueue
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

        return [
            'type' => 'new_post',
            'post_id' => $this->post->id,
            'business_id' => $business->id,
            'business_name' => $business->name,
            'message' => __(':business publicó algo nuevo.', ['business' => $business->name]),
            'url' => route('vitrinas.show', $business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Nueva publicación'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
