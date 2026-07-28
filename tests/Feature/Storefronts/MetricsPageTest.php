<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.8 del TODO: página de métricas del panel del Emprendedor.
 */
class MetricsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_a_human_summary_and_totals(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Métricas'])->business;

        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'b']);
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::WHATSAPP_CLICK, 'visitor_hash' => 'a']);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.metricas', ['business' => $business->id])
            ->assertOk()
            ->assertSee('2')
            ->assertSee('1')
            ->assertSee('Esta semana');
    }

    public function test_a_business_with_no_activity_shows_a_helpful_empty_message(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Actividad'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.metricas', ['business' => $business->id])
            ->assertOk()
            ->assertSee(__('Todavía no hay visitas ni contactos esta semana. Comparte tu enlace o QR para empezar a recibirlos.'));
    }

    public function test_a_collaborator_of_another_business_cannot_view_the_metrics(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);

        Livewire::test('pages::emprendedores.negocios.metricas', ['business' => $businessA->id])
            ->assertForbidden();
    }
}
