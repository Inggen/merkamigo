<?php

namespace App\Domain\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamReaction extends Model
{
    protected $fillable = ['live_stream_id', 'user_id', 'visitor_hash', 'type'];

    /** @return BelongsTo<LiveStream, $this> */
    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
