<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Actions\DetectIncompleteOrInactiveStorefronts;
use App\Domain\Analytics\Actions\SendWeeklyBusinessReports;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Analytics\Notifications\StorefrontNeedsAttention;
use App\Domain\Analytics\Notifications\WeeklyBusinessReport;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 4.5 del TODO: informe semanal por correo y alertas de vitrina
 * incompleta/inactiva.
 */
class WeeklyReportsAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(User $owner): Business
    {
        $suffix = uniqid();
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio '.$suffix,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Una descripción completa.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_weekly_report_is_sent_to_published_businesses_with_activity(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);

        $sent = app(SendWeeklyBusinessReports::class)->handle();

        $this->assertSame(1, $sent);
        Notification::assertSentTo($owner, WeeklyBusinessReport::class);
    }

    public function test_weekly_report_is_not_sent_to_businesses_without_any_activity(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $this->publishedBusiness($owner);

        $sent = app(SendWeeklyBusinessReports::class)->handle();

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
    }

    public function test_a_storefront_missing_a_logo_and_products_is_flagged(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);

        // Simula una vitrina que ya estaba publicada pero perdió su logo y
        // sus productos después (`PublishStorefront` exige ambos para
        // publicar, así que esto solo puede pasar tras la publicación).
        $business->update(['logo_path' => null]);
        $business->products()->delete();

        $notified = app(DetectIncompleteOrInactiveStorefronts::class)->handle();

        $this->assertSame(1, $notified);
        Notification::assertSentTo($owner, StorefrontNeedsAttention::class, function ($notification) use ($owner) {
            $reasons = $notification->toArray($owner)['reasons'];

            return in_array('sin logo', $reasons, true) && in_array('sin productos', $reasons, true);
        });
    }

    public function test_a_complete_and_active_storefront_is_not_flagged(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);

        $notified = app(DetectIncompleteOrInactiveStorefronts::class)->handle();

        $this->assertSame(0, $notified);
        Notification::assertNothingSent();
    }

    public function test_an_inactive_storefront_with_no_events_in_30_days_is_flagged(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);
        $event = AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        $event->forceFill(['created_at' => now()->subDays(40)])->save();

        $notified = app(DetectIncompleteOrInactiveStorefronts::class)->handle();

        $this->assertSame(1, $notified);
        Notification::assertSentTo($owner, StorefrontNeedsAttention::class, function ($notification) use ($owner) {
            $reasons = $notification->toArray($owner)['reasons'];

            return in_array('sin actividad en los últimos 30 días', $reasons, true);
        });
    }

    public function test_the_alert_is_not_repeated_within_six_days(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = $this->publishedBusiness($owner);
        $business->update(['logo_path' => null]);

        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => StorefrontNeedsAttention::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => ['business_id' => $business->id],
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $notified = app(DetectIncompleteOrInactiveStorefronts::class)->handle();

        $this->assertSame(0, $notified);
    }
}
