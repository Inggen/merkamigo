<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Jobs\NotifyBusinessOfChatConversation;
use App\Domain\Storefronts\Models\BusinessChatConversation;
use App\Domain\Storefronts\Models\BusinessChatMessage;
use Illuminate\Http\Request;

/**
 * Guarda cada intercambio del chat con IA de la vitrina (pedido del
 * usuario: seguimiento de quién escribió y qué se dijo). Los visitantes
 * no inician sesión, así que se agrupan por `visitor_hash` (mismo hash
 * que ya usa `RegisterAnalyticsEvent`, sin guardar la IP en claro)
 * mientras la conversación siga "caliente"; pasada la ventana sin
 * mensajes nuevos, el siguiente mensaje arranca una conversación nueva en
 * vez de reabrir una ya vieja.
 */
class RecordBusinessChatMessage
{
    private const SESSION_WINDOW_HOURS = 6;

    private const NOTIFY_DELAY_MINUTES = 10;

    public function handle(Business $business, Request $request, string $question, string $answer): BusinessChatConversation
    {
        $visitorHash = hash('sha256', $request->ip().'|'.$request->userAgent());
        $user = $request->user();

        $conversation = BusinessChatConversation::query()
            ->where('business_id', $business->id)
            ->where('visitor_hash', $visitorHash)
            ->where('last_message_at', '>=', now()->subHours(self::SESSION_WINDOW_HOURS))
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            $conversation = BusinessChatConversation::create([
                'business_id' => $business->id,
                'visitor_hash' => $visitorHash,
                'visitor_user_id' => $user?->id,
                'visitor_label' => $user?->name,
                'last_message_at' => now(),
            ]);
        }

        $conversation->messages()->create(['role' => BusinessChatMessage::VISITOR, 'content' => $question]);
        $conversation->messages()->create(['role' => BusinessChatMessage::CHATBOT, 'content' => $answer]);
        $conversation->update(['last_message_at' => now()]);

        NotifyBusinessOfChatConversation::dispatch($conversation->id, $conversation->messages()->count())
            ->delay(now()->addMinutes(self::NOTIFY_DELAY_MINUTES));

        return $conversation;
    }
}
