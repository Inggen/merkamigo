<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un usuario empezó a seguir el negocio (Fase 11 del TODO social: tipo
 * "nuevo seguidor"). Se envía a todos los miembros activos del negocio,
 * no solo al dueño — cualquier colaborador puede querer saberlo.
 */
class NewFollower extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $follower, private readonly int $businessId, private readonly string $businessName) {}

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
            'type' => 'new_follower',
            'business_id' => $this->businessId,
            'follower_name' => $this->follower->name,
            'message' => __(':name ahora sigue a :business.', ['name' => $this->follower->name, 'business' => $this->businessName]),
            'url' => route('emprendedores.home'),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Nuevo seguidor'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
