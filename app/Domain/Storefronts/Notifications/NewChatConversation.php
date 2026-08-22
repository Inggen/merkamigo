<?php

namespace App\Domain\Storefronts\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Storefronts\Models\BusinessChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un visitante conversó con el chatbot IA de la vitrina (pedido del
 * usuario: avisarle al negocio quién escribió y qué se dijo). Se envía
 * una sola vez por conversación, con un resumen generado por IA — ver
 * `NotifyBusinessOfChatConversation` para el criterio de "cuándo".
 */
class NewChatConversation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BusinessChatConversation $conversation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', PushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visitor = $this->conversation->displayLabel();

        return (new MailMessage)
            ->subject(__(':visitor escribió a tu chatbot', ['visitor' => $visitor]))
            ->greeting(__('¡Hola!'))
            ->line(__(':visitor conversó con el chatbot de tu vitrina.', ['visitor' => $visitor]))
            ->line($this->conversation->summary ?? __('Revisa la conversación completa en tu panel.'))
            ->action(__('Ver conversación'), route('emprendedores.negocios.chatbot', $this->conversation->business_id))
            ->line(__('Este resumen lo genera IA a partir de la conversación — puede no ser exacto al 100%.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_chat_conversation',
            'conversation_id' => $this->conversation->id,
            'business_id' => $this->conversation->business_id,
            'visitor' => $this->conversation->displayLabel(),
            'summary' => $this->conversation->summary,
            'url' => route('emprendedores.negocios.chatbot', $this->conversation->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => __(':visitor escribió a tu chatbot', ['visitor' => $data['visitor']]),
            'body' => $data['summary'] ?? __('Revisa la conversación completa en tu panel.'),
            'url' => $data['url'],
        ];
    }
}
