<?php

namespace App\Domain\Storefronts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Archivo de un producto digital (Fase 9 del TODO social) — disco
 * `private`, nunca una URL pública permanente; solo se sirve a través de
 * `App\Http\Controllers\ProductDownloadController`, que exige un
 * `Entitlement` vigente.
 */
class ProductFile extends Model
{
    protected $fillable = ['product_id', 'path', 'original_name', 'size_bytes', 'position'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
