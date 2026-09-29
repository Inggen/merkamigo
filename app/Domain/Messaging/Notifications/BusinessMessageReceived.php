<?php

namespace App\Domain\Messaging\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Messaging\Models\BusinessMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BusinessMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly BusinessConversation $conversation,
        private readonly BusinessMessage $message,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array{
     *     type: string,
     *     conversation_id: int,
     *     business_id: int,
     *     sender_name: string,
     *     message: string,
     *     url: string,
     *     action_label: string
     * }
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'business_message',
            'conversation_id' => $this->conversation->id,
            'business_id' => $this->conversation->business_id,
            'sender_name' => $this->message->sender->name,
            'message' => __('Nuevo mensaje de :name: :body', [
                'name' => $this->message->sender->name,
                'body' => filled($this->message->body) ? str($this->message->body)->limit(80) : __('(foto adjunta)'),
            ]),
            'url' => route('messages.show', $this->conversation),
            'action_label' => __('Responder'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => __('Nuevo mensaje'),
            'body' => $data['message'],
            'url' => $data['url'],
        ];
    }
}
