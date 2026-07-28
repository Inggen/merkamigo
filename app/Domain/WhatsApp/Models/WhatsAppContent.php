<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Borrador generado por el Copiloto de WhatsApp (1.7 del TODO): plantilla
 * de texto lista para copiar y compartir, nunca enviada automáticamente.
 */
class WhatsAppContent extends Model
{
    public const PROMOCION = 'promocion';

    public const ESTADO = 'estado';

    public const RESPUESTA = 'respuesta';

    public const PRESENTACION = 'presentacion';

    protected $table = 'whatsapp_contents';

    protected $fillable = ['business_id', 'product_id', 'type', 'tone', 'content'];

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
