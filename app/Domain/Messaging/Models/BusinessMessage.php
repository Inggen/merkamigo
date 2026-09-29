<?php

namespace App\Domain\Messaging\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessMessage extends Model
{
    protected $fillable = ['sender_user_id', 'body', 'attachment_path', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    /**
     * @return BelongsTo<BusinessConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BusinessConversation::class, 'business_conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
