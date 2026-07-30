<?php

namespace App\Domain\Analytics\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Evento medible del negocio (1.8 del TODO): visita a vitrina/producto, clic
 * a WhatsApp, vista del QR o clic en compartir. No guarda IP ni user-agent
 * en crudo, solo un hash no reversible para deduplicar (0.6 del TODO: los
 * eventos no guardan más datos personales de los necesarios).
 */
class AnalyticsEvent extends Model
{
    public const VITRINA_VIEW = 'vitrina_view';

    public const PRODUCTO_VIEW = 'producto_view';

    public const WHATSAPP_CLICK = 'whatsapp_click';

    public const QR_VIEW = 'qr_view';

    public const COMPARTIR_CLICK = 'compartir_click';

    public const OFERTA_VIEW = 'oferta_view';

    protected $fillable = ['business_id', 'type', 'subject_type', 'subject_id', 'visitor_hash'];

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
