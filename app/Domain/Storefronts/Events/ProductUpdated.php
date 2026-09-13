<?php

namespace App\Domain\Storefronts\Events;

/**
 * Cubre cualquier cambio persistido del producto: edición del
 * emprendedor, publicar/archivar (ambos son `UpdateProduct` con distinto
 * `status`), y también las suspensiones/restauraciones de moderación
 * (`SuspendProduct`/`RestoreProduct`) — todas terminan en "el producto
 * cambió, hay que re-evaluar su elegibilidad ante Google".
 */
class ProductUpdated
{
    public function __construct(public readonly int $productId) {}
}
