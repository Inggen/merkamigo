<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Actions\RegisterStoreView;
use App\Domain\Analytics\Actions\RegisterWhatsAppClick;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Actions\RegisterRecentlyViewedBusiness;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Loyalty\Models\LoyaltyPolicy;
use App\Domain\Loyalty\Models\LoyaltyReward;
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

        $business->load(['organization', 'storefront', 'municipality', 'municipalities', 'category', 'verifications', 'orderConfirmations', 'recommendations.authorUser']);

        app(RegisterStoreView::class)->handle($business, null, $request);

        if ($request->user()) {
            app(RegisterRecentlyViewedBusiness::class)->handle($request->user(), $business);
        }

        $storefrontPosts = $business->storefront?->show_posts
            ? $business->posts()
                ->where('status', 'publicado')
                ->where('type', '!=', 'video')
                ->with(['business.organization', 'business.municipality', 'media', 'products.media', 'activePromotion'])
                ->latest('published_at')
                ->take(6)
                ->get()
            : collect();
        $storefrontReels = $business->storefront?->show_reels
            ? $business->posts()
                ->where('status', 'publicado')
                ->where('type', 'video')
                ->with(['business.organization', 'business.municipality', 'media', 'products.media', 'activePromotion'])
                ->latest('published_at')
                ->take(6)
                ->get()
            : collect();

        // TODO_Merkapuntos.md, F2.1: pestaña "Recompensas" en la vitrina
        // pública, solo si el negocio tiene Merkamigo Premia activo y al
        // menos un premio publicado — nunca una pestaña vacía.
        $loyaltyRewards = $business->loyaltyEnrollment?->isActive()
            ? $business->loyaltyRewards()->where('status', LoyaltyReward::PUBLICADO)->with('product.media')->get()
            : collect();
        $loyaltyPolicy = $loyaltyRewards->isNotEmpty()
            ? $business->loyaltyPolicies()->where('status', LoyaltyPolicy::ACTIVA)->first()
            : null;

        // Pestaña "Eventos" (pedido del usuario, 2026-10-06): mismo
        // criterio de "nunca una pestaña vacía" que Recompensas/Contenido
        // — solo próximos eventos publicados, igual que la agenda pública
        // general (`PublicEventsController::index`).
        $publicEvents = $business->publicEvents()
            ->where('status', PublicEvent::PUBLICADO)
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->get();

        return view('vitrinas.show', [
            'business' => $business,
            'products' => $business->products()->where('status', 'publicado')->with('media')->get(),
            'storefrontPosts' => $storefrontPosts,
            'storefrontReels' => $storefrontReels,
            'highlightedStories' => $business->highlightedStories()->with('product')->take(12)->get(),
            'loyaltyRewards' => $loyaltyRewards,
            'loyaltyPolicy' => $loyaltyPolicy,
            'publicEvents' => $publicEvents,
            'initialTab' => in_array($request->query('tab'), ['informacion', 'productos', 'contenido', 'opiniones', 'recompensas', 'eventos'], true)
                ? $request->query('tab')
                : 'informacion',
        ]);
    }

    public function product(Business $business, string $product, Request $request): View
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['organization', 'storefront', 'municipality', 'municipalities', 'category', 'verifications', 'orderConfirmations', 'recommendations.authorUser']);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        $relatedProducts = $business->products()
            ->where('status', 'publicado')
            ->whereKeyNot($product->getKey())
            ->with('media')
            ->take(4)
            ->get();

        app(RegisterStoreView::class)->handle($business, $product, $request);

        return view('vitrinas.product', [
            'business' => $business,
            'product' => $product->load(['media', 'variants']),
            'relatedProducts' => $relatedProducts,
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

    public function qrProduct(Business $business, string $product, Request $request): Response
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::QR_VIEW, $product, $request);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'imageTransparent' => false,
            'scale' => 8,
        ]);

        $png = (new QRCode($options))->render(route('vitrinas.product', [$business, $product]));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    public function whatsapp(Business $business, Request $request): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);
        abort_if(blank($business->whatsapp_number), 404);

        app(RegisterWhatsAppClick::class)->handle($business, null, $request);

        $text = __('Hola :name, te escribo desde https://merkamigo.com', ['name' => $business->name]);

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

        $productUrl = route('vitrinas.product', [$business, $product]);
        $text = __('Hola, me interesa ":product" que vi en Merkamigo. Te comparto el enlace: :url', [
            'product' => $product->name,
            'url' => $productUrl,
        ]);

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
