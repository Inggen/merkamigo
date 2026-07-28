<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\Request;

/**
 * Registra un clic al botón de WhatsApp desde la vitrina o el detalle de un
 * producto (1.8 del TODO). Solo se llama desde páginas públicas — no cuenta
 * cuando el propio emprendedor prueba su enlace desde su panel.
 */
class RegisterWhatsAppClick
{
    public function handle(Business $business, ?Product $product, Request $request): void
    {
        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::WHATSAPP_CLICK, $product, $request);
    }
}
