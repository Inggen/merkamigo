<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\Request;

/**
 * Registra la visita a una vitrina o a un producto (1.8 del TODO).
 */
class RegisterStoreView
{
    public function handle(Business $business, ?Product $product, Request $request): void
    {
        $type = $product ? AnalyticsEvent::PRODUCTO_VIEW : AnalyticsEvent::VITRINA_VIEW;

        app(RegisterAnalyticsEvent::class)->handle($business, $type, $product, $request);
    }
}
