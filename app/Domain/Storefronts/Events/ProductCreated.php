<?php

namespace App\Domain\Storefronts\Events;

/**
 * Primer evento de dominio para `Product` en todo el proyecto — hasta
 * ahora sus mutaciones (`CreateProduct`, `UpdateProduct`...) no
 * disparaban ningún evento. Se agrega puntualmente para la integración
 * con Google Merchant Center, sin tocar el resto del flujo de creación.
 */
class ProductCreated
{
    public function __construct(public readonly int $productId) {}
}
