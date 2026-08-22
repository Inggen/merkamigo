<?php

namespace App\Domain\Storefronts\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversación de un visitante con el chatbot IA de la vitrina (pedido
 * del usuario: "dejar un seguimiento para que el emprendedor pueda saber
 * quién le escribió y qué dijo"). Agrupa los mensajes de una misma
 * persona (`visitor_hash`) mientras la conversación siga activa — ver
 * `RecordBusinessChatMessage` para la ventana de agrupación.
 */
class BusinessChatConversation extends Model
{
    protected $fillable = [
        'business_id',
        'visitor_hash',
        'visitor_user_id',
        'visitor_label',
        'summary',
        'last_message_at',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_user_id');
    }

    /**
     * @return HasMany<BusinessChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BusinessChatMessage::class)->orderBy('id');
    }

    public function displayLabel(): string
    {
        return $this->visitor_label ?: __('Visitante anónimo');
    }
}
