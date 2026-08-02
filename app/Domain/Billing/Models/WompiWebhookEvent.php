<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de eventos de webhook ya procesados (4.2 del TODO): Wompi
 * reintenta hasta 3 veces si no respondemos 2xx, así que el `checksum` es
 * único para no procesar el mismo evento más de una vez.
 */
class WompiWebhookEvent extends Model
{
    protected $fillable = [
        'wompi_transaction_id',
        'status',
        'checksum',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
