<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\Response;

class GoogleMerchantFeedController extends Controller
{
    public function index(): Response
    {
        $items = [];

        Product::query()
            ->where('type', 'producto')
            ->where('status', 'publicado')
            ->whereHas('business', fn ($query) => $query->where('status', 'publicado'))
            ->with(['business.category', 'media'])
            ->chunk(200, function ($products) use (&$items): void {
                foreach ($products as $product) {
                    $item = $this->feedItem($product);

                    if ($item !== null) {
                        $items[] = $item;
                    }
                }
            });

        $xml = view('feeds.google-merchant', [
            'items' => $items,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function feedItem(Product $product): ?array
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
}
