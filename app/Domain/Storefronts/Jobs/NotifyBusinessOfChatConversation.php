<?php

namespace App\Domain\Storefronts\Jobs;

use App\Domain\Storefronts\Actions\SummarizeChatConversation;
use App\Domain\Storefronts\Models\BusinessChatConversation;
use App\Domain\Storefronts\Notifications\NewChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica al negocio que alguien conversó con su chatbot, con un resumen
 * (pedido del usuario). Se programa con 10 minutos de retraso desde
 * `RecordBusinessChatMessage` en cada mensaje nuevo — para no mandar un
 * correo por cada pregunta de una misma conversación, este job se
 * "autocancela" (`$messageCountAtDispatch` ya no coincide) si llegaron
 * más mensajes después de programarlo: el job más reciente, disparado por
 * el último mensaje, es el único que termina notificando.
 */
class NotifyBusinessOfChatConversation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $messageCountAtDispatch,
    ) {}

    public function handle(): void
    {
        $conversation = BusinessChatConversation::with('business.members')->find($this->conversationId);

        if (! $conversation || $conversation->notified_at !== null) {
            return;
        }

        if ($conversation->messages()->count() !== $this->messageCountAtDispatch) {
            // Llegaron mensajes nuevos desde que se programó este job — el
            // job disparado por el mensaje más reciente se encargará.
            return;
        }

        $conversation->load('messages');
        $summary = app(SummarizeChatConversation::class)->handle($conversation);

        $conversation->update(['summary' => $summary, 'notified_at' => now()]);

        $conversation->business->members->each(
            fn ($member) => $member->notify(new NewChatConversation($conversation))
        );
    }
}
