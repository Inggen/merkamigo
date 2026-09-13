<?php

namespace App\Domain\Storefronts\Jobs;

use App\Domain\Storefronts\Models\Product;
use App\Support\GoogleMerchant\GoogleMerchantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envía o actualiza un producto en Google Merchant Center. Si el producto
 * ya no es elegible (y antes sí lo era), `GoogleMerchantService` lo
 * retira de Google por su cuenta — este Job no necesita distinguir el
 * caso, solo pedir la sincronización.
 */
class SyncProductToGoogleMerchant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(private readonly int $productId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300, 900, 3600];
    }

    public function handle(GoogleMerchantService $service): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            return;
        }

        $service->syncProduct($product);
    }
}
