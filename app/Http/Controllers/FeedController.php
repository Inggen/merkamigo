<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\Post;
use App\Domain\Storefronts\Models\Product;
use App\Support\Geo\Distance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Feed social (2.1/2.2 del TODO social) — decisión del usuario (sesión 15
 * sep 2026): este feed reemplaza a `Inicio` (antes
 * `ClientesController::home`, movido a `Explorar`), ver `routes/web.php`.
 * Visual alineado al mockup `public/images/feed.png`: columna de
 * publicaciones + columna lateral de "Negocios cerca de ti".
 */
class FeedController extends Controller
{
    public function index(Request $request): View
    {
        $municipality = $this->preferredMunicipality($request);
        $tab = $request->string('tab')->value() === 'siguiendo' ? 'siguiendo' : 'recientes';
        $eligiblePromotionIds = $this->eligiblePromotionIds($request, $municipality);

        $postsQuery = Post::query()
            ->where('status', 'publicado')
            ->with([
                'business.storefront', 'business.municipality', 'user', 'media', 'products.media',
                'activePromotion' => fn ($query) => $query->whereIn('id', $eligiblePromotionIds),
            ])
            ->withExists(['activePromotion as is_promoted' => fn ($query) => $query->whereIn('id', $eligiblePromotionIds)])
            ->withCount(['reactions', 'visibleComments as comments_count'])
            ->orderByDesc('is_promoted')
            ->latest('published_at');

        if ($tab === 'siguiendo' && Auth::check()) {
            $postsQuery->whereIn('business_id', Auth::user()->following()->pluck('businesses.id'));
        } elseif ($municipality) {
            $postsQuery->whereHas('business', fn ($query) => $query->servesMunicipality($municipality->id));
        }

        $posts = $postsQuery->paginate(10)->withQueryString();

        foreach ($posts as $post) {
            app(RegisterAnalyticsEvent::class)->handle(
                $post->business,
                $post->type === 'video' ? AnalyticsEvent::REEL_VIEW : AnalyticsEvent::POST_VIEW,
                $post,
                $request,
            );

            if ($post->activePromotion) {
                app(RegisterAnalyticsEvent::class)->handle(
                    $post->business,
                    AnalyticsEvent::PROMOTION_IMPRESSION,
                    $post->activePromotion,
                    $request,
                );
            }
        }

        $nearbyBusinesses = Business::query()
            ->where('status', 'publicado')
            ->with(['category', 'municipality', 'storefront'])
            ->when($municipality, fn ($query) => $query->servesMunicipality($municipality->id))
            ->latest()
            ->limit(5)
            ->get();

        $reels = Post::query()
            ->where('status', 'publicado')
            ->where('type', 'video')
            ->with(['business', 'media'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        $activeLive = LiveStream::query()
            ->where('status', LiveStream::EN_VIVO)
            ->with([
                'business.municipality', 'business.storefront', 'pinnedProduct.media',
                'activePromotion' => fn ($query) => $query->whereIn('id', $eligiblePromotionIds),
            ])
            ->withExists(['activePromotion as is_promoted' => fn ($query) => $query->whereIn('id', $eligiblePromotionIds)])
            ->when($municipality, fn ($query) => $query->whereHas('business', fn ($businessQuery) => $businessQuery->servesMunicipality($municipality->id)))
            ->orderByDesc('is_promoted')
            ->latest('started_at')
            ->first();

        if ($activeLive?->activePromotion) {
            app(RegisterAnalyticsEvent::class)->handle(
                $activeLive->business,
                AnalyticsEvent::PROMOTION_IMPRESSION,
                $activeLive->activePromotion,
                $request,
            );
        }

        $promotedProduct = ContentPromotion::query()
            ->active()
            ->whereIn('id', $eligiblePromotionIds)
            ->where('promotable_type', (new Product)->getMorphClass())
            ->with(['promotable.business.storefront', 'promotable.media', 'municipality'])
            ->latest('starts_at')
            ->first();

        if ($promotedProduct) {
            app(RegisterAnalyticsEvent::class)->handle(
                $promotedProduct->business,
                AnalyticsEvent::PROMOTION_IMPRESSION,
                $promotedProduct,
                $request,
            );
        }

        return view('feed.index', [
            'municipality' => $municipality,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'tab' => $tab,
            'posts' => $posts,
            'nearbyBusinesses' => $nearbyBusinesses,
            'reels' => $reels,
            'activeLive' => $activeLive,
            'promotedProduct' => $promotedProduct,
        ]);
    }

    private function preferredMunicipality(Request $request): ?Municipality
    {
        $slug = $request->cookie('municipio');

        if (! $slug) {
            return null;
        }

        return Municipality::where('slug', $slug)->where('is_active', true)->first();
    }

    /** @return array<int, int> */
    private function eligiblePromotionIds(Request $request, ?Municipality $municipality): array
    {
        $hasCoordinates = $request->filled(['lat', 'lng']);
        $latitude = $hasCoordinates ? $request->float('lat') : null;
        $longitude = $hasCoordinates ? $request->float('lng') : null;

        return ContentPromotion::query()
            ->active()
            ->with('business')
            ->get()
            ->filter(function (ContentPromotion $promotion) use ($municipality, $hasCoordinates, $latitude, $longitude) {
                if ($municipality && $promotion->municipality_id && $promotion->municipality_id !== $municipality->id) {
                    return false;
                }

                if ($promotion->category_id && $promotion->business->category_id !== $promotion->category_id) {
                    return false;
                }

                if (! $promotion->radius_km || ! $hasCoordinates) {
                    return true;
                }

                if (! $promotion->business->hasCoordinates()) {
                    return false;
                }

                return Distance::kilometers(
                    $latitude,
                    $longitude,
                    $promotion->business->latitude,
                    $promotion->business->longitude,
                ) <= $promotion->radius_km;
            })
            ->pluck('id')
            ->all();
    }
}
