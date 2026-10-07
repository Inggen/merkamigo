<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Política de acumulación versionada de un negocio (TODO_Merkapuntos.md,
 * reglas de producto: "Política de acumulación versionada, aprobada y
 * calculada en servidor"). Cada compra guarda una copia (`policy_snapshot`
 * en `LoyaltyPurchase`) de la versión vigente al momento de registrarse,
 * así que cambiar la política después nunca altera compras ya acreditadas.
 *
 * @property array{type: string, points_per_unit?: int, unit_cents?: int} $rule
 */
class LoyaltyPolicy extends Model
{
    public const BORRADOR = 'borrador';

    public const ACTIVA = 'activa';

    public const ARCHIVADA = 'archivada';

    protected $fillable = [
        'business_id',
        'version',
        'status',
        'rule',
        'eligible_base_notes',
        'effective_from',
        'effective_until',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'rule' => 'array',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }

    /**
     * Ejemplo no vinculante del propio TODO: `floor(valor_elegible / 1000)`
     * puntos. Solo soporta la regla `simple` (puntos fijos por cada tramo
     * de `unit_cents`); una regla distinta en `rule.type` no se calcula
     * aquí a propósito — evita inventar un cálculo para una regla que el
     * negocio todavía no aprobó.
     */
    public function pointsFor(int $eligibleAmountCents): int
    {
        if (($this->rule['type'] ?? null) !== 'simple') {
            return 0;
        }

        $pointsPerUnit = max(0, (int) ($this->rule['points_per_unit'] ?? 0));
        $unitCents = max(1, (int) ($this->rule['unit_cents'] ?? 1));

        return (int) floor($eligibleAmountCents / $unitCents) * $pointsPerUnit;
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
