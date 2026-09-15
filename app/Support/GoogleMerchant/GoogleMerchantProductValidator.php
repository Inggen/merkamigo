<?php

namespace App\Support\GoogleMerchant;

use App\Domain\Storefronts\Models\Product;

/**
 * Reglas mínimas para que un producto sea elegible ante Google Merchant
 * Center, evaluadas ANTES de llamar a la API — nunca se envía un producto
 * que no las cumpla. Los mensajes están en español y pensados para
 * mostrarse tal cual al emprendedor (panel de vendedor), no son texto
 * técnico de depuración.
 */
class GoogleMerchantProductValidator
{
    /**
     * @return list<string> Mensajes de validación; vacío si el producto es elegible.
     */
    public function validate(Product $product): array
    {
        $errors = [];
        $business = $product->business;

        if (! $business) {
            return ['El producto no tiene un negocio asociado.'];
        }

        if (! $business->google_merchant_enabled) {
            $errors[] = 'El vendedor no está habilitado para Google Shopping.';
        }

        if (! $business->isPublished()) {
            $errors[] = 'El negocio no está publicado.';
        }

        if (blank($business->external_seller_id)) {
            $errors[] = 'El negocio no tiene un identificador de vendedor válido.';
        }

        if ($product->type !== 'producto') {
            $errors[] = 'Solo productos físicos son elegibles para Google Shopping (no servicios).';
        }

        if (! $product->isPublished()) {
            $errors[] = 'El producto no está publicado.';
        }

        if (blank($product->name)) {
            $errors[] = 'Falta el nombre del producto.';
        }

        if (blank($product->description) && blank($business->storefront?->description)) {
            $errors[] = 'Descripción insuficiente.';
        }

        if (blank($product->media->first()?->url())) {
            $errors[] = 'Falta imagen principal.';
        }

        if (in_array($product->price_type, ['consultar', 'sin_precio'], true)) {
            $errors[] = 'El producto no tiene un precio fijo publicable (usa "Consultar" o "Sin precio").';
        } else {
            $price = $product->hasActivePromo() ? $product->promo_price : $product->price;

            if (blank($price) || (float) $price <= 0) {
                $errors[] = 'El producto no tiene precio.';
            }
        }

        return $errors;
    }

    public function isEligible(Product $product): bool
    {
        return $this->validate($product) === [];
    }

    /**
     * Reglas adicionales para "inventario local" (fichas locales sin
     * costo / anuncios de inventario local), aparte del feed principal:
     * el negocio debe haber marcado que tiene un local físico Y tener el
     * `store_code` de SU PROPIO Perfil de Empresa de Google (nunca el de
     * Merkamigo — usar un perfil compartido para negocios independientes
     * sería una ubicación falsa ante Google). Ver TODO-Google-Merchant.md,
     * post-cierre.
     *
     * @return list<string>
     */
    public function validateLocalInventory(Product $product): array
    {
        $errors = $this->validate($product);
        $business = $product->business;

        if (! $business) {
            return $errors;
        }

        if (! $business->has_physical_location) {
            $errors[] = 'El negocio no marcó tener un local físico.';
        } elseif (blank($business->google_business_store_code)) {
            $errors[] = 'Falta el código de tienda del Perfil de Empresa de Google del negocio.';
        }

        return $errors;
    }

    public function isEligibleForLocalInventory(Product $product): bool
    {
        return $this->validateLocalInventory($product) === [];
    }
}
