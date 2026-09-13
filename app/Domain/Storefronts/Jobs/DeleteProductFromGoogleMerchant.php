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
 * Retira un producto de Google Merchant Center de forma directa (sin
 * pasar por el validador) — para cuando ya se sabe que debe desaparecer
 * de Google sin importar su estado individual, como al suspender el
 * negocio completo. `withTrashed()` porque el producto podría no existir
 * más en Merkamigo para cuando el Job corre.
 */
class DeleteProductFromGoogleMerchant implements ShouldQueue
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
        $product = Product::withTrashed()->find($this->productId);

        if (! $product) {
            return;
        }

        $service->deleteProduct($product);
    }
}
