<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\BusinessChatConversation;
use App\Domain\Storefronts\Models\BusinessChatMessage;
use App\Support\Ai\Contracts\GeneratesAssistedText;

/**
 * Resume una conversación del chatbot para el correo/notificación al
 * emprendedor (pedido del usuario: "un resumen de lo que se dijo"). Si la
 * IA está apagada o falla, `handle()` devuelve null y quien la use debe
 * caer en un mensaje genérico — nunca se bloquea la notificación por
 * esto.
 */
class SummarizeChatConversation
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    public function handle(BusinessChatConversation $conversation): ?string
    {
        $transcript = $conversation->messages
            ->map(fn (BusinessChatMessage $message) => ($message->role === BusinessChatMessage::VISITOR ? 'Visitante' : 'Chatbot').': '.$message->content)
            ->implode("\n");

        if (blank($transcript)) {
            return null;
        }

        return $this->assistedText->generate(
            'Resume en 2-3 frases, en español y en tercera persona, de qué habló un visitante con el chatbot de '.
            'este negocio: qué preguntó, qué se le respondió, y si parece interesado en comprar/contratar algo o '.
            'si quedó alguna duda sin resolver que el negocio debería seguir por su cuenta. Sé concreto y directo, '.
            'sin relleno ni frases genéricas. Devuelve solo el resumen, sin prefijos ni comillas.',
            ['transcripcion' => $transcript],
        );
    }
}
