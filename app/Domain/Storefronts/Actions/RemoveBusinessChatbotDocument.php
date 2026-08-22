<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\BusinessChatbotProfile;
use App\Support\Media\MediaUploader;

/**
 * Quita el PDF que el negocio le había dado de contexto al chatbot IA
 * (pedido del usuario). El tono y las notas sueltas se conservan — solo
 * se limpia lo relacionado al documento.
 */
class RemoveBusinessChatbotDocument
{
    public function handle(BusinessChatbotProfile $profile): void
    {
        if ($profile->document_path) {
            app(MediaUploader::class)->delete($profile->document_path, 'private');
        }

        $profile->update([
            'document_path' => null,
            'document_original_name' => null,
            'document_text' => null,
        ]);
    }
}
