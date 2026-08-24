<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración singleton (mismo criterio que `OpenAiSetting`): el PDF de
 * contexto general que el asistente de la plataforma usa en cada
 * respuesta, junto con categorías/municipios/preguntas frecuentes (ver
 * `AnswerPlatformChatQuestion`).
 */
class PlatformKnowledgeDocument extends Model
{
    protected $table = 'platform_knowledge_documents';

    protected $fillable = [
        'document_path',
        'document_original_name',
        'document_text',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create();
    }

    public function hasDocument(): bool
    {
        return filled($this->document_text);
    }
}
