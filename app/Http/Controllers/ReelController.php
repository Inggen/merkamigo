<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Reels (Fase 4 del TODO social, Sprint 4): scroll vertical de posts
 * `type = video` — reutiliza `Post`/reacciones/comentarios/seguir tal
 * cual, sin un dominio paralelo. Alcance reducido a propósito: paginado
 * clásico (como el resto del sitio) en vez de scroll infinito, que queda
 * para cuando haya volumen real de reels que lo justifique.
 */
class ReelController extends Controller
{
    public function index(Request $request): View
    {
        $reels = Post::query()
            ->where('type', 'video')
            ->where('status', 'publicado')
            ->whereHas('media')
            ->with(['business.storefront', 'business.municipality', 'media', 'products'])
            ->withCount(['reactions', 'visibleComments as comments_count'])
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        foreach ($reels as $reel) {
            app(RegisterAnalyticsEvent::class)->handle($reel->business, AnalyticsEvent::REEL_VIEW, $reel, $request);
        }

        return view('reels.index', [
            'reels' => $reels,
        ]);
    }
}
