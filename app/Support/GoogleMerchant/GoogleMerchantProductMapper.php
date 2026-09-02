<?php

namespace App\Support\GoogleMerchant;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;

class GoogleMerchantProductMapper
{
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
            'id' => 'MKG-'.$business->id.'-'.$product->id,
            'title' => $product->name,
            'description' => trim(strip_tags($product->description ?: $business->storefront?->description ?: $product->name)),
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
            'brand' => $business->name,
            'condition' => 'new',
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
            'condition' => 'NEW',
            'identifierExists' => false,
        ];

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
