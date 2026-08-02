<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\CloseNeed;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Notifications\OrderConfirmedByBusiness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2.2 del TODO: "Necesidades cercanas" en la experiencia del Emprendedor.
 */
class OportunidadesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_owner_sees_open_needs_in_their_municipality_and_can_respond(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Oportunidades'])->business;
        $municipality = Municipality::find($business->municipality_id) ?? Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true,
        ]);
        $business->update(['municipality_id' => $municipality->id]);

        $need = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesito un servicio', 'description' => 'Descripción detallada.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $business->id])
            ->assertSee($need->title)
            ->call('compose', $need->id)
            ->set('message', 'Puedo ayudarte con esto')
            ->call('submit');

        $this->assertDatabaseHas('offers', [
            'need_id' => $need->id, 'business_id' => $business->id, 'status' => Offer::ENVIADA,
        ]);
    }

    public function test_a_business_owner_can_confirm_a_pending_order_and_the_customer_is_notified(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pedidos'])->business;
        $municipality = Municipality::find($business->municipality_id) ?? Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true,
        ]);
        $business->update(['municipality_id' => $municipality->id]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito flores', 'description' => 'Para un aniversario.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $offer = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Tengo un ramo hermoso'], $owner);
        app(CloseNeed::class)->handle($need, $buyer, Need::OUTCOME_ENCONTRE, $offer);

        $order = OrderConfirmation::where('business_id', $business->id)->firstOrFail();
        $this->assertNotNull($order->customer_confirmed_at);
        $this->assertNull($order->business_confirmed_at);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $business->id])
            ->assertSee($order->summary)
            ->call('confirmOrder', $order->id);

        $order->refresh();
        $this->assertNotNull($order->business_confirmed_at);
        $this->assertSame(OrderConfirmation::CONFIRMADO, $order->status);

        Notification::assertSentTo($buyer, OrderConfirmedByBusiness::class);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_page(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $businessA->id])
            ->assertForbidden();
    }
}
