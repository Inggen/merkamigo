<?php

namespace App\Domain\Social\Actions;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Models\Story;
use App\Domain\Social\Models\StoryView;
use App\Models\User;

/**
 * Registra que un usuario vio un estado (Fase 3 del TODO social:
 * "registrar visualizaciones"). Idempotente — una sola vista cuenta por
 * usuario, sin importar cuántas veces lo vuelva a abrir.
 */
class RecordStoryView
{
    public function handle(User $viewer, Story $story): void
    {
        $created = StoryView::query()->firstOrCreate([
            'story_id' => $story->id,
            'user_id' => $viewer->id,
        ]);

        if ($created->wasRecentlyCreated) {
            $story->increment('views_count');
            app(RegisterAnalyticsEvent::class)->handle($story->business, AnalyticsEvent::STORY_VIEW, $story, request());
        }

        if ($story->activePromotion) {
            app(RegisterAnalyticsEvent::class)->handle(
                $story->business,
                AnalyticsEvent::PROMOTION_IMPRESSION,
                $story->activePromotion,
                request(),
            );
        }
    }
}
