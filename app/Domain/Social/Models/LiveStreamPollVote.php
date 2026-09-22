<?php

namespace App\Domain\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamPollVote extends Model
{
    protected $fillable = ['live_stream_poll_id', 'user_id', 'visitor_hash', 'option_index'];

    /** @return BelongsTo<LiveStreamPoll, $this> */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(LiveStreamPoll::class, 'live_stream_poll_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
