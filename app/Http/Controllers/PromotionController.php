<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\Story;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function click(ContentPromotion $promotion, Request $request): RedirectResponse
    {
        abort_unless($promotion->isActive(), 404);

        app(RegisterAnalyticsEvent::class)->handle(
            $promotion->business,
            AnalyticsEvent::PROMOTION_CLICK,
            $promotion,
            $request,
        );

        return redirect()->to(match (true) {
            $promotion->promotable instanceof Product => route('vitrinas.product', [
                'business' => $promotion->business,
                'product' => $promotion->promotable,
                'promotion' => $promotion->id,
            ]),
            $promotion->promotable instanceof LiveStream => route('live.show', $promotion->promotable),
            $promotion->promotable instanceof Post, $promotion->promotable instanceof Story => route('vitrinas.show', $promotion->business),
            default => route('home'),
        });
    }
}
