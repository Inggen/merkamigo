<?php

namespace App\Domain\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveDestination extends Model
{
    protected $fillable = [
        'live_stream_id',
        'provider',
        'name',
        'rtmp_url',
        'stream_key',
        'is_enabled',
        'status',
        'last_error',
        'last_connected_at',
    ];

    protected function casts(): array
    {
        return [
            'rtmp_url' => 'encrypted',
            'stream_key' => 'encrypted',
            'is_enabled' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'connecting' => __('Conectando'),
            'transmitting' => __('Transmitiendo'),
            'error' => __('Error de conexión'),
            default => __('Desconectado'),
        };
    }
}
