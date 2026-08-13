<?php

namespace App\Domain\Analytics\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\ImmersivePlaza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Evento medible de una plaza inmersiva (IMM-043): entrada a la plaza,
 * búsqueda, filtro de categoría, vitrina abierta, producto visto, clic a
 * WhatsApp o muestra de rendimiento. Hermano de `AnalyticsEvent` (mismo
 * dominio, misma disciplina de privacidad): nunca guarda IP ni user-agent
 * en crudo, solo un hash no reversible para deduplicar.
 */
class ImmersiveEvent extends Model
{
    public const PLAZA_ENTRY = 'plaza_entry';

    public const SEARCH_PERFORMED = 'search_performed';

    public const CATEGORY_FILTERED = 'category_filtered';

    public const VITRINA_OPENED = 'vitrina_opened';

    public const PRODUCT_VIEWED = 'product_viewed';

    public const WHATSAPP_CLICK = 'whatsapp_click';

    public const PERFORMANCE_SAMPLE = 'performance_sample';

    protected $fillable = [
        'immersive_plaza_id',
        'business_id',
        'type',
        'subject_type',
        'subject_id',
        'visitor_hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ImmersivePlaza, $this>
     */
    public function plaza(): BelongsTo
    {
        return $this->belongsTo(ImmersivePlaza::class, 'immersive_plaza_id');
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
