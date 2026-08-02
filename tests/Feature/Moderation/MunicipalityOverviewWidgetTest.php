<?php

namespace Tests\Feature\Moderation;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Filament\Widgets\MunicipalityOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 4.5 del TODO: panel admin de actividad por municipio.
 */
class MunicipalityOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_the_widget_shows_businesses_and_views_grouped_by_municipality(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería de Cajicá',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'b']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::WHATSAPP_CLICK, 'visitor_hash' => 'a']);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(MunicipalityOverview::class)
            ->assertSuccessful()
            ->assertTableColumnStateSet('businesses_count', 1, record: $municipality)
            ->assertTableColumnStateSet('views_count', 2, record: $municipality)
            ->assertTableColumnStateSet('whatsapp_clicks_count', 1, record: $municipality);
    }

    public function test_events_older_than_seven_days_are_not_counted(): void
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Servicios', 'slug' => 'servicios', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio de Zipaquirá',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Servicios varios.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Servicio', 'type' => 'servicio', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        $event = AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        $event->forceFill(['created_at' => now()->subDays(10)])->save();

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(MunicipalityOverview::class)
            ->assertSuccessful()
            ->assertTableColumnStateSet('views_count', 0, record: $municipality);
    }
}
