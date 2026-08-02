<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de productos de ingreso complementario (4.3 del TODO):
 * destacados temporales, vitrina asistida, kit "Arranca Bonito". Precios
 * editables desde Filament, sin valores codificados.
 *
 * @property array<string, mixed>|null $payload
 */
class BillingProduct extends Model
{
    public const DESTACADO = 'destacado';

    public const VITRINA_ASISTIDA = 'vitrina_asistida';

    public const KIT_ARRANCA_BONITO = 'kit_arranca_bonito';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'kind',
        'payload',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
