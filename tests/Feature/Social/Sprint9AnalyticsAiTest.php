<?php

namespace Tests\Feature\Social;

use App\Domain\Analytics\Actions\CalculateSocialContentPerformance;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Social\Actions\GenerateSocialSalesCopy;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class Sprint9AnalyticsAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_metrics_combine_posts_stories_reels_and_lives_for_the_selected_period(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Social'])->business;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Novedad del día'], [], $owner);
        $reel = app(CreatePost::class)->handle($business, ['type' => 'video', 'body' => 'Nuestro proceso'], [
            UploadedFile::fake()->create('reel.mp4', 100, 'video/mp4'),
        ], $owner);
        $story = $business->stories()->create([
            'user_id' => $owner->id,
            'type' => 'imagen',
            'image_path' => 'stories/example.jpg',
            'expires_at' => now()->addDay(),
            'views_count' => 4,
        ]);
        $live = $business->liveStreams()->create([
            'user_id' => $owner->id,
            'title' => 'Live de lanzamiento',
            'slug' => 'live-de-lanzamiento',
            'provider' => 'youtube',
            'stream_url' => 'https://youtube.com/watch?v=abcdefghijk',
            'status' => LiveStream::FINALIZADO,
            'started_at' => now(),
            'ended_at' => now(),
        ]);
        $live->views()->create(['user_id' => $owner->id, 'visitor_hash' => 'live-viewer', 'last_seen_at' => now()]);
        $live->messages()->create(['user_id' => $owner->id, 'body' => 'Hola', 'status' => 'publicado']);

        foreach ([[$post, AnalyticsEvent::POST_VIEW], [$reel, AnalyticsEvent::REEL_VIEW], [$story, AnalyticsEvent::STORY_VIEW]] as [$subject, $type]) {
            AnalyticsEvent::create([
                'business_id' => $business->id,
                'type' => $type,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->id,
                'visitor_hash' => "view-{$subject->id}-{$type}",
            ]);
        }

        $metrics = app(CalculateSocialContentPerformance::class)->handle($business, 30);

        $this->assertCount(4, $metrics['rows']);
        $this->assertGreaterThanOrEqual(7, $metrics['reach']);
        $this->assertSame(1, $metrics['interactions']);
        $this->assertEqualsCanonicalizing(
            [__('Publicación'), __('Reel'), __('Estado'), __('Live / replay')],
            collect($metrics['rows'])->pluck('type')->all(),
        );
    }

    public function test_metrics_page_can_switch_between_supported_periods(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Periodos'])->business;
        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.metricas', ['business' => $business->id])
            ->set('period', 30)
            ->assertSet('period', 30)
            ->assertSee(__('Rendimiento de contenido'))
            ->assertSee(__('Últimos 90 días'));
    }

    public function test_ai_sales_copy_uses_real_catalog_context_and_remains_an_editable_draft(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio IA'])->business;
        $product = $business->products()->create([
            'name' => 'Café de origen',
            'slug' => 'cafe-de-origen',
            'type' => 'producto',
            'price' => 32000,
            'price_type' => 'exacto',
            'status' => 'publicado',
        ]);

        $spy = new class implements GeneratesAssistedText
        {
            /** @var array<string, mixed> */
            public array $context = [];

            public function generate(string $prompt, array $context = []): ?string
            {
                $this->context = $context;

                return 'Descubre nuestro café de origen y conoce su historia.';
            }
        };
        $this->app->instance(GeneratesAssistedText::class, $spy);

        $copy = app(GenerateSocialSalesCopy::class)->handle($business, 'publicacion', [
            'text' => 'Texto inicial',
            'product_ids' => [$product->id],
        ]);

        $this->assertSame('Descubre nuestro café de origen y conoce su historia.', $copy);
        $this->assertSame('Café de origen', $spy->context['productos_seleccionados'][0]['nombre']);

        $this->actingAs($owner);
        Livewire::test('pages::emprendedores.negocios.publicaciones', ['business' => $business->id])
            ->set('product_ids', [$product->id])
            ->call('suggestWithAi')
            ->assertSet('body', $copy);

        $this->assertDatabaseCount('posts', 0);
    }
}
