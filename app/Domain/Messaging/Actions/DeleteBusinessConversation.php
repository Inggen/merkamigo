<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Borrado suave de una conversación (pedido del usuario). Borra para
 * ambas partes a la vez — no es "ocultar solo para mí": si el cliente o
 * el negocio ya no la quieren ver, cualquiera de los dos puede borrarla,
 * y vuelve a aparecer sola si alguien le escribe de nuevo
 * (`StartBusinessConversation` la restaura en ese caso).
 */
class DeleteBusinessConversation
{
    public function handle(BusinessConversation $conversation, User $actor): void
    {
        abort_unless($conversation->canBeViewedBy($actor), 403);

        $conversation->delete();

        app(RecordAuditLog::class)->handle($actor, 'business_conversation.deleted', $conversation);
    }
}
