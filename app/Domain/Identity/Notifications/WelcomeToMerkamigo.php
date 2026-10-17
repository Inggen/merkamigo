<?php

namespace App\Domain\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToMerkamigo extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isEntrepreneur = $notifiable->experience === 'emprendedor';

        $data = [
            'name' => $notifiable->name,
            'isEntrepreneur' => $isEntrepreneur,
            'primaryUrl' => $isEntrepreneur
                ? route('emprendedores.crear-vitrina')
                : route('explorar'),
            'secondaryUrl' => $isEntrepreneur
                ? route('emprendedores.home')
                : route('profile.edit'),
        ];

        return (new MailMessage)
            ->subject(__('¡Bienvenido a Merkamigo!'))
            ->view([
                'html' => 'mail.identity.welcome',
                'text' => 'mail.identity.welcome-text',
            ], $data);
    }
}
