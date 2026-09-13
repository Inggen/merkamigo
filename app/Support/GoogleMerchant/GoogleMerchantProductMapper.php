<?php

namespace App\Support\GoogleMerchant;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;

class GoogleMerchantProductMapper
{
    /**
     * Identificador estable del producto ante Google (`offerId`). No
     * depende de que `business`/`media` estén cargados, para poder
     * calcularse también en `delete()`/`get()` sobre productos que ya no
     * cumplen los requisitos de elegibilidad de `map()`.
     */
    public function offerId(Product $product): string
    {
        return 'MKG-'.$product->business_id.'-'.$product->id;
    }

    /**
     * Nombre de recurso de Merchant API: `{contentLanguage}~{feedLabel}~{offerId}`.
     * Confirmado contra la documentación vigente de Merchant API: ya no
     * lleva el prefijo `channel~` que usaba Content API for Shopping.
     */
    public function productResourceId(Product $product): string
    {
        return sprintf(
            '%s~%s~%s',
            config('services.google_merchant.content_language'),
            config('services.google_merchant.feed_label'),
            $this->offerId($product),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function map(Product $product): ?array
    {
        /** @var Business|null $business */
        $business = $product->business;
        $image = $product->media->first()?->url();
        $price = $product->hasActivePromo() ? $product->promo_price : $product->price;

        if (! $business || ! $image || ! filled($price) || $product->price_type === 'consultar') {
            return null;
        }

        return [
            'id' => $this->offerId($product),
            'title' => $this->stripEmoji($product->name),
            'description' => $this->stripEmoji(trim(strip_tags($product->description ?: $business->storefront?->description ?: $product->name))),
            'link' => route('vitrinas.product', [$business, $product]),
            'image_link' => $image,
            'additional_image_links' => $product->media->skip(1)->take(10)->map(fn ($media) => $media->url())->values()->all(),
            'availability' => $product->isSoldOut() ? 'out of stock' : 'in stock',
            'price' => number_format((float) $product->price, 2, '.', '').' COP',
            'sale_price' => $product->hasActivePromo() && filled($product->promo_price)
                ? number_format((float) $product->promo_price, 2, '.', '').' COP'
                : null,
            'sale_price_effective_date' => $product->hasActivePromo() && $product->promo_ends_at
                ? now()->toAtomString().'/'.$product->promo_ends_at->toAtomString()
                : null,
            // Sin marca propia declarada, se usa el nombre del negocio —
            // nunca se inventa una marca genérica (0.4 del TODO de esta
            // integración: "si el producto no tiene marca, no inventarla").
            'brand' => $product->brand ?: $business->name,
            'condition' => $product->condition,
            'gtin' => $product->gtin,
            'mpn' => $product->mpn,
            'external_seller_id' => $business->externalSellerId(),
            'product_type' => $business->category?->name,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function apiPayload(Product $product): ?array
    {
        $item = $this->map($product);

        if ($item === null) {
            return null;
        }

        $attributes = [
            'title' => $item['title'],
            'description' => $item['description'],
            'link' => $item['link'],
            'imageLink' => $item['image_link'],
            'additionalImageLinks' => $item['additional_image_links'],
            'availability' => $item['availability'] === 'in stock' ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'price' => $this->apiPrice($product->price),
            'brand' => $item['brand'],
            'condition' => $this->apiCondition($item['condition']),
            'externalSellerId' => $item['external_seller_id'],
            // Nunca se inventan códigos: `identifierExists` solo es true
            // cuando el producto tiene un GTIN o MPN real cargado (0.5 del
            // TODO de esta integración — productos artesanales/únicos sin
            // identificador real deben declarar explícitamente que no
            // existen, tal como exige Google).
            'identifierExists' => filled($item['gtin']) || filled($item['mpn']),
        ];

        if (filled($item['gtin'])) {
            $attributes['gtins'] = [$item['gtin']];
        }

        if (filled($item['mpn'])) {
            $attributes['mpn'] = $item['mpn'];
        }

        if ($item['sale_price']) {
            $attributes['salePrice'] = $this->apiPrice($product->promo_price);
        }

        if ($product->hasActivePromo() && ($product->promo_starts_at || $product->promo_ends_at)) {
            $attributes['salePriceEffectiveDate'] = array_filter([
                'startTime' => $product->promo_starts_at?->toAtomString(),
                'endTime' => $product->promo_ends_at?->toAtomString(),
            ]);
        }

        if ($item['product_type']) {
            $attributes['productTypes'] = [$item['product_type']];
        }

        return [
            'offerId' => $item['id'],
            'contentLanguage' => config('services.google_merchant.content_language'),
            'feedLabel' => config('services.google_merchant.feed_label'),
            'productAttributes' => $attributes,
        ];
    }

    /**
     * Google rechaza (o bloquea la edición de) productos con emojis en
     * `title`/`description` — se limpian solo para el envío a Google; el
     * nombre/descripción reales del producto en Merkamigo no se tocan.
     */
    private function stripEmoji(string $text): string
    {
        $pattern = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{2190}-\x{21FF}]/u';

        $cleaned = (string) preg_replace($pattern, '', $text);

        return trim((string) preg_replace('/\s{2,}/', ' ', $cleaned));
    }

    private function apiCondition(?string $condition): string
    {
        return match ($condition) {
            'usado' => 'USED',
            'reacondicionado' => 'REFURBISHED',
            default => 'NEW',
        };
    }

    /**
     * @return array{amountMicros: string, currencyCode: string}
     */
    private function apiPrice(mixed $price): array
    {
        return [
            'amountMicros' => (string) round((float) $price * 1_000_000),
            'currencyCode' => config('services.google_merchant.currency'),
        ];
    }
}
