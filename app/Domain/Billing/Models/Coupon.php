<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cupón de descuento (4.1 del TODO), aplicado en el checkout (4.2). El
 * TODO marca los cupones como "solo si se validan" — la validez y el
 * descuento se verifican aquí en cada uso, nunca se asume vigente.
 *
 * @property Carbon|null $expires_at
 */
class Coupon extends Model
{
    public const PORCENTAJE = 'porcentaje';

    public const FIJO = 'fijo';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'expires_at',
        'max_redemptions',
        'redeemed_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    public function discountedAmountCents(int $amountCents): int
    {
        $discount = $this->discount_type === self::PORCENTAJE
            ? (int) round($amountCents * ($this->discount_value / 100))
            : $this->discount_value * 100;

        return max(0, $amountCents - $discount);
    }
}
