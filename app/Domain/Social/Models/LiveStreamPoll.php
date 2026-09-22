<?php

namespace App\Domain\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveStreamPoll extends Model
{
    protected $fillable = ['live_stream_id', 'user_id', 'question', 'options', 'closed_at'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'closed_at' => 'datetime',
        ];
    }

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

    /** @return HasMany<LiveStreamPollVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(LiveStreamPollVote::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /** @return array<int, array{index: int, label: string, votes: int, percentage: float}> */
    public function results(): array
    {
        $counts = $this->relationLoaded('votes')
            ? $this->votes->countBy('option_index')
            : $this->votes()->selectRaw('option_index, count(*) as aggregate')->groupBy('option_index')->pluck('aggregate', 'option_index');
        $total = max(1, (int) $counts->sum());

        return collect($this->options)->values()->map(fn (string $label, int $index): array => [
            'index' => $index,
            'label' => $label,
            'votes' => (int) ($counts[$index] ?? 0),
            'percentage' => round(((int) ($counts[$index] ?? 0) / $total) * 100, 1),
        ])->all();
    }
}
