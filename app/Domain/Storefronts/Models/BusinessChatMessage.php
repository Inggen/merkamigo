<?php

namespace App\Domain\Storefronts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessChatMessage extends Model
{
    public const VISITOR = 'user';

    public const CHATBOT = 'assistant';

    protected $fillable = [
        'business_chat_conversation_id',
        'role',
        'content',
    ];

    /**
     * @return BelongsTo<BusinessChatConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BusinessChatConversation::class, 'business_chat_conversation_id');
    }
}
