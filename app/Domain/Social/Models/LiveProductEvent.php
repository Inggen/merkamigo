<?php

namespace App\Domain\Social\Models;

use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveProductEvent extends Model
{
    protected $fillable = [
        'live_stream_id',
        'product_id',
        'action',
        'elapsed_seconds',
        'created_by',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
