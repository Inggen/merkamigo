<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\BusinessChatbotProfile;

/**
 * Guarda el tono/jerga y las notas sueltas del chatbot IA de un negocio
 * (pedido del usuario, ver `AnswerBusinessChatQuestion`). Independiente
 * del PDF (`SaveBusinessChatbotDocument`) — un negocio puede configurar
 * solo el tono, solo notas, o ambos, sin necesitar el documento.
 */
class SaveBusinessChatbotProfile
{
    public function handle(Business $business, ?string $tone, ?string $extraNotes): BusinessChatbotProfile
    {
        $profile = BusinessChatbotProfile::firstOrNew(['business_id' => $business->id]);

        $profile->business_id = $business->id;
        $profile->tone = filled($tone) ? $tone : null;
        $profile->extra_notes = filled($extraNotes) ? $extraNotes : null;
        $profile->save();

        return $profile;
    }
}
