<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Actions\RegisterStoreView;
use App\Domain\Analytics\Actions\RegisterWhatsAppClick;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Vitrina pública (1.3 del TODO): `/m/{slug}`, detalle de producto y QR.
 * Contenido público, sin autenticación. También registra los eventos
 * medibles de 1.8: visita, clic a WhatsApp, vista del QR y clic en
 * compartir.
 */
class VitrinaController extends Controller
{
    public function show(Business $business, Request $request): View
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['storefront', 'municipality', 'category', 'verifications', 'orderConfirmations', 'recommendations.authorUser']);

        app(RegisterStoreView::class)->handle($business, null, $request);

        return view('vitrinas.show', [
            'business' => $business,
            'products' => $business->products()->where('status', 'publicado')->with('media')->get(),
        ]);
    }

    public function product(Business $business, string $product, Request $request): View
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['storefront', 'municipality', 'category', 'verifications', 'orderConfirmations']);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        app(RegisterStoreView::class)->handle($business, $product, $request);

        return view('vitrinas.product', [
            'business' => $business,
            'product' => $product->load(['media', 'variants']),
        ]);
    }

    public function qr(Business $business, Request $request): Response
    {
        abort_unless($business->isPublished(), 404);

        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::QR_VIEW, null, $request);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'imageTransparent' => false,
            'scale' => 8,
        ]);

        $png = (new QRCode($options))->render(route('vitrinas.show', $business));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    public function whatsapp(Business $business, Request $request): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);
        abort_if(blank($business->whatsapp_number), 404);

        app(RegisterWhatsAppClick::class)->handle($business, null, $request);

        $text = __('Hola :name, te escribo desde Merkamigo 👋', ['name' => $business->name]);

        return redirect()->away($this->whatsappUrl($business->whatsapp_number, $text));
    }

    public function whatsappProduct(Business $business, string $product, Request $request): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);
        abort_if(blank($business->whatsapp_number), 404);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        app(RegisterWhatsAppClick::class)->handle($business, $product, $request);

        $text = __('Hola, me interesa ":product" que vi en Merkamigo.', ['product' => $product->name]);

        return redirect()->away($this->whatsappUrl($business->whatsapp_number, $text));
    }

    public function compartir(Business $business, Request $request): Response
    {
        abort_unless($business->isPublished(), 404);

        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::COMPARTIR_CLICK, null, $request);

        return response()->noContent();
    }

    public function compartirProduct(Business $business, string $product, Request $request): Response
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::COMPARTIR_CLICK, $product, $request);

        return response()->noContent();
    }

    private function whatsappUrl(string $whatsappNumber, string $text): string
    {
        $number = preg_replace('/\D/', '', $whatsappNumber);

        return "https://wa.me/{$number}?text=".urlencode($text);
    }
}
