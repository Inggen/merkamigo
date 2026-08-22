<?php

namespace App\Domain\Businesses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Catálogo administrable de formas de pago (Nequi, Visa, Pago
 * contraentrega...) que un negocio puede marcar como aceptadas — pedido
 * del usuario, se muestran con su logo en la pestaña "Información de
 * pago" de la vitrina. Mismo criterio de administración que
 * `BusinessAttribute`, pero con imagen.
 */
class PaymentMethod extends Model
{
    protected $fillable = ['name', 'slug', 'logo_path', 'is_active', 'position'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
