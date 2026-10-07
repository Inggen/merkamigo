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

    public const POST_VIEW = 'post_view';

    public const STORY_VIEW = 'story_view';

    public const REEL_VIEW = 'reel_view';

    public const LIVE_VIEW = 'live_view';

    public const LIVE_PRODUCT_CLICK = 'live_product_click';

    public const LIVE_CART_ADD = 'live_cart_add';

    public const LIVE_CHECKOUT_STARTED = 'live_checkout_started';

    public const LIVE_PURCHASE = 'live_purchase';

    public const PROMOTION_IMPRESSION = 'promotion_impression';

    public const PROMOTION_CLICK = 'promotion_click';

    public const PROMOTION_CONVERSION = 'promotion_conversion';

    // TODO_Merkapuntos.md, F5.4: "impresión de premio, detalle, ...
    // compra confirmada, canje reservado/entregado, cancelado/expirado,
    // recompra" — mismos eventos anónimos/agregados que el resto de la
    // plataforma, vía `RegisterAnalyticsEvent` (deduplicados, sin IP ni
    // user-agent en crudo).
    public const LOYALTY_REWARD_IMPRESSION = 'loyalty_reward_impression';

    public const LOYALTY_REWARD_DETAIL_VIEW = 'loyalty_reward_detail_view';

    public const LOYALTY_PURCHASE_CONFIRMED = 'loyalty_purchase_confirmed';

    public const LOYALTY_REDEMPTION_RESERVED = 'loyalty_redemption_reserved';

    public const LOYALTY_REDEMPTION_DELIVERED = 'loyalty_redemption_delivered';

    public const LOYALTY_REDEMPTION_RELEASED = 'loyalty_redemption_released';

    public const LOYALTY_REPURCHASE = 'loyalty_repurchase';

    // TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 6: "Medir
    // impresiones, aperturas, clics y reservas atribuibles por vitrina y
    // plan" — mismo patrón que los de Merkapuntos arriba.
    public const EVENT_IMPRESSION = 'event_impression';

    public const EVENT_DETAIL_VIEW = 'event_detail_view';

    public const EVENT_RESERVATION_STARTED = 'event_reservation_started';

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
