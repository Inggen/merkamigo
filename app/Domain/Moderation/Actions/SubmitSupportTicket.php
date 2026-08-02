<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\SupportTicket;
use App\Models\User;

/**
 * Registra una solicitud de soporte (1.9 del TODO). Disponible tanto para
 * visitantes como para usuarios registrados — no exige cuenta.
 */
class SubmitSupportTicket
{
    public function handle(string $subject, string $message, ?User $user, ?string $contactEmail): SupportTicket
    {
        return SupportTicket::create([
            'user_id' => $user?->id,
            'contact_email' => $contactEmail,
            'subject' => $subject,
            'message' => $message,
            'status' => SupportTicket::PENDIENTE,
        ]);
    }
}
