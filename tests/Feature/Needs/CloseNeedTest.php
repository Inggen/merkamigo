<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\CancelNeed;
use App\Domain\Needs\Actions\CloseNeed;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2.3 del TODO: cierre de la solicitud.
 */
class CloseNeedTest extends TestCase
{
    use RefreshDatabase;

    private function publishedNeedWithOffer(User $buyer): array
    {
        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito flores', 'description' => 'Para un aniversario.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Floristería Test'])->business;
        $offer = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Tengo un ramo hermoso'], $owner);

        return [$need->fresh(), $offer, $business];
    }

    public function test_closing_as_found_with_a_selected_offer_prepares_a_trust_event(): void
    {
        $buyer = User::factory()->create();
        [$need, $offer, $business] = $this->publishedNeedWithOffer($buyer);

        app(CloseNeed::class)->handle($need, $buyer, Need::OUTCOME_ENCONTRE, $offer);

        $need->refresh();
        $this->assertSame(Need::CERRADA, $need->status);
        $this->assertSame(Need::OUTCOME_ENCONTRE, $need->outcome);
        $this->assertSame($offer->id, $need->selected_offer_id);
        $this->assertSame(Offer::ACEPTADA, $offer->fresh()->status);

        $order = OrderConfirmation::where('source_type', (new Offer)->getMorphClass())->where('source_id', $offer->id)->first();
        $this->assertNotNull($order);
        $this->assertSame($business->id, $order->business_id);
        $this->assertNotNull($order->customer_confirmed_at);
        $this->assertNull($order->business_confirmed_at);
    }

    public function test_closing_without_finding_anything_does_not_create_a_trust_event(): void
    {
        $buyer = User::factory()->create();
        [$need] = $this->publishedNeedWithOffer($buyer);

        app(CloseNeed::class)->handle($need, $buyer, Need::OUTCOME_NO_ENCONTRE);

        $need->refresh();
        $this->assertSame(Need::CERRADA, $need->status);
        $this->assertSame(Need::OUTCOME_NO_ENCONTRE, $need->outcome);
        $this->assertSame(0, OrderConfirmation::count());
    }

    public function test_a_selected_offer_from_a_different_need_is_rejected(): void
    {
        $buyer = User::factory()->create();
        [$need, $offer] = $this->publishedNeedWithOffer($buyer);
        [$otherNeed] = $this->publishedNeedWithOffer(User::factory()->create());

        $this->expectException(\InvalidArgumentException::class);

        app(CloseNeed::class)->handle($otherNeed, $buyer, Need::OUTCOME_ENCONTRE, $offer);
    }

    public function test_the_owner_can_cancel_a_need(): void
    {
        $buyer = User::factory()->create();
        [$need] = $this->publishedNeedWithOffer($buyer);

        app(CancelNeed::class)->handle($need, $buyer);

        $this->assertSame(Need::CANCELADA, $need->fresh()->status);
        $this->assertNotNull($need->fresh()->closed_at);
    }

    public function test_the_detail_page_lets_the_owner_preselect_and_close_with_an_offer(): void
    {
        $buyer = User::factory()->create();
        [$need, $offer] = $this->publishedNeedWithOffer($buyer);

        $this->actingAs($buyer);

        $component = Livewire::test('pages::mis-solicitudes.show', ['need' => $need->id])
            ->call('preselect', $offer->id);

        $this->assertSame(Offer::PRESELECCIONADA, $offer->fresh()->status);

        $component->call('selectForClosing', $offer->id)
            ->assertSet('selectedOfferId', $offer->id)
            ->call('close', Need::OUTCOME_ENCONTRE);

        $this->assertSame(Need::CERRADA, $need->fresh()->status);
        $this->assertSame($offer->id, $need->fresh()->selected_offer_id);
    }

    public function test_a_user_cannot_open_someone_elses_need_detail(): void
    {
        $buyer = User::factory()->create();
        [$need] = $this->publishedNeedWithOffer($buyer);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::mis-solicitudes.show', ['need' => $need->id])->assertForbidden();
    }
}
