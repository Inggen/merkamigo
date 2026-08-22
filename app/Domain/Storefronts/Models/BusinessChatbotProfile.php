<?php

namespace App\Domain\Storefronts\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Contexto propio que un negocio con chatbot IA (plan Emprendedor o el
 * add-on, ver `Business::canUseAiChatbot()`) le da al asistente de su
 * vitrina: un PDF con información del negocio (se guarda el texto ya
 * extraído para no reprocesar el archivo en cada pregunta), notas sueltas
 * escritas a mano, y el tono/jerga con la que habla el negocio.
 */
class BusinessChatbotProfile extends Model
{
    protected $fillable = [
        'business_id',
        'tone',
        'extra_notes',
        'document_path',
        'document_original_name',
        'document_text',
    ];

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function documentUrl(): ?string
    {
        return $this->document_path
            ? Storage::disk('private')->temporaryUrl($this->document_path, now()->addMinutes(10))
            : null;
    }
}
