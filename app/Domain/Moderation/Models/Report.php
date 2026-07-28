<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Reporte de contenido (1.3/1.4/1.9 del TODO): un visitante o cliente marca
 * un negocio o producto como inapropiado; un moderador lo revisa desde
 * Filament.
 */
class Report extends Model
{
    public const PENDIENTE = 'pendiente';

    public const RESUELTO = 'resuelto';

    public const DESCARTADO = 'descartado';

    protected $fillable = [
        'reportable_type', 'reportable_id', 'reporter_email', 'reason',
        'details', 'status', 'resolution_note', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Descripción legible de qué se reportó, para la tabla de Filament.
     */
    public function reportableLabel(): string
    {
        return match (true) {
            $this->reportable instanceof Business => 'Negocio: '.$this->reportable->name,
            $this->reportable instanceof Product => 'Producto: '.$this->reportable->name,
            default => __('(eliminado)'),
        };
    }
}
