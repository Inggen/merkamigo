<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\Story;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CalculateSocialContentPerformance
{
    /**
     * @return array{
     *   reach: int,
     *   interactions: int,
     *   rows: array<int, array{type: string, title: string, reach: int, interactions: int, published_at: mixed}>
     * }
     */
    public function handle(Business $business, int $days = 7): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 7;
        $periodStart = now()->subDays($days - 1)->startOfDay();

        $posts = $business->posts()
            ->where('status', 'publicado')
            ->where('published_at', '>=', $periodStart)
            ->withCount(['reactions', 'visibleComments as comments_count'])
            ->get();
        $stories = $business->stories()->where('created_at', '>=', $periodStart)->get();
        $lives = $business->liveStreams()
            ->where('created_at', '>=', $periodStart)
            ->withCount(['views', 'reactions', 'messages'])
            ->get();

        $reach = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->whereIn('type', [
                AnalyticsEvent::POST_VIEW,
                AnalyticsEvent::REEL_VIEW,
                AnalyticsEvent::STORY_VIEW,
                AnalyticsEvent::LIVE_VIEW,
            ])
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('type, subject_type, subject_id, COUNT(*) as total')
            ->groupBy('type', 'subject_type', 'subject_id')
            ->get()
            ->keyBy(fn ($event) => "{$event->type}|{$event->subject_type}|{$event->subject_id}");

        $rows = collect()
            ->merge($posts->map(function (Post $post) use ($reach) {
                $type = $post->type === 'video' ? AnalyticsEvent::REEL_VIEW : AnalyticsEvent::POST_VIEW;

                return [
                    'type' => $post->type === 'video' ? __('Reel') : __('Publicación'),
                    'title' => Str::limit($post->body ?: __('Contenido sin texto'), 70),
                    'reach' => $this->reachFor($reach, $type, $post),
                    'interactions' => $post->reactions_count + $post->comments_count,
                    'published_at' => $post->published_at,
                ];
            }))
            ->merge($stories->map(fn (Story $story) => [
                'type' => __('Estado'),
                'title' => Str::limit($story->caption ?: __('Estado con imagen'), 70),
                'reach' => max($story->views_count, $this->reachFor($reach, AnalyticsEvent::STORY_VIEW, $story)),
                'interactions' => 0,
                'published_at' => $story->created_at,
            ]))
            ->merge($lives->map(fn (LiveStream $live) => [
                'type' => $live->isLive() ? __('Live activo') : __('Live / replay'),
                'title' => $live->title,
                'reach' => max($live->views_count, $this->reachFor($reach, AnalyticsEvent::LIVE_VIEW, $live)),
                'interactions' => $live->reactions_count + $live->messages_count,
                'published_at' => $live->started_at ?? $live->created_at,
            ]))
            ->sortByDesc(fn (array $row) => [$row['reach'], $row['interactions']])
            ->values();

        return [
            'reach' => $rows->sum('reach'),
            'interactions' => $rows->sum('interactions'),
            'rows' => $rows->all(),
        ];
    }

    /** @param Collection<string, mixed> $reach */
    private function reachFor(Collection $reach, string $type, object $subject): int
    {
        $key = "{$type}|{$subject->getMorphClass()}|{$subject->getKey()}";

        return (int) ($reach->get($key)?->total ?? 0);
    }
}
