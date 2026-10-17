<?php

namespace App\Domain\Platform\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformTestMail extends Notification
{
    public function __construct(private readonly string $sentBy) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Correo de prueba de Merkamigo'))
            ->view([
                'html' => 'mail.platform.test',
                'text' => 'mail.platform.test-text',
            ], [
                'sentBy' => $this->sentBy,
                'sentAt' => now(),
            ]);
    }
}
