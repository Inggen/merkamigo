<?php

namespace Tests\Feature\Needs;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Actions\WithdrawOffer;
use App\Domain\Needs\Exceptions\NeedClosedException;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * 2.2 del TODO: descubrimiento y propuestas.
 */
class OfferManagementTest extends TestCase
{
    use RefreshDatabase;

    private function publishedNeed(): Need
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito tortas', 'description' => 'Para una fiesta de cumpleaños.', 'municipality_id' => $municipality->id,
        ]);

        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        return $need->fresh();
    }

    private function business(): Business
    {
        $owner = User::factory()->create();

        return app(CreateStorefront::class)->handle($owner, ['name' => 'Pastelería Test'])->business;
    }

    public function test_a_business_can_submit_an_offer_and_the_need_starts_receiving_offers(): void
    {
        $need = $this->publishedNeed();
        $business = $this->business();

        $offer = app(SubmitOffer::class)->handle($business, $need, [
            'message' => 'Puedo hacer tu torta, tengo disponibilidad esta semana.',
            'price' => 80000,
        ], User::factory()->create());

        $this->assertSame(Offer::ENVIADA, $offer->status);
        $this->assertSame($need->id, $offer->need_id);
        $this->assertSame(Need::RECIBIENDO_OFERTAS, $need->fresh()->status);
    }

    public function test_a_business_only_has_one_offer_per_need_resubmitting_updates_it(): void
    {
        $need = $this->publishedNeed();
        $business = $this->business();
        $actor = User::factory()->create();

        $first = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Primer mensaje'], $actor);
        $second = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Mensaje actualizado'], $actor);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Mensaje actualizado', $second->fresh()->message);
        $this->assertSame(1, Offer::where('need_id', $need->id)->where('business_id', $business->id)->count());
    }

    public function test_withdrawing_an_offer_allows_resubmitting_later(): void
    {
        $need = $this->publishedNeed();
        $business = $this->business();
        $actor = User::factory()->create();

        $offer = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Mensaje'], $actor);
        app(WithdrawOffer::class)->handle($offer, $actor);

        $this->assertSame(Offer::RETIRADA, $offer->fresh()->status);
        $this->assertNotNull($offer->fresh()->withdrawn_at);

        $resubmitted = app(SubmitOffer::class)->handle($business, $need, ['message' => 'De nuevo disponible'], $actor);

        $this->assertSame($offer->id, $resubmitted->id);
        $this->assertSame(Offer::ENVIADA, $resubmitted->status);
        $this->assertNull($resubmitted->withdrawn_at);
    }

    public function test_an_offer_message_with_a_link_is_rejected(): void
    {
        $need = $this->publishedNeed();
        $business = $this->business();

        $this->expectException(ValidationException::class);

        app(SubmitOffer::class)->handle($business, $need, [
            'message' => 'Escríbeme a https://wa.example.com',
        ], User::factory()->create());
    }

    public function test_a_closed_need_no_longer_accepts_offers(): void
    {
        $need = $this->publishedNeed();
        $need->update(['status' => Need::CERRADA, 'closed_at' => now()]);
        $business = $this->business();

        $this->expectException(NeedClosedException::class);

        app(SubmitOffer::class)->handle($business, $need, ['message' => 'Tarde'], User::factory()->create());
    }

    public function test_a_suspended_need_no_longer_accepts_offers(): void
    {
        $need = $this->publishedNeed();
        $need->update(['suspended_at' => now(), 'suspension_reason' => 'Revisión']);
        $business = $this->business();

        $this->expectException(NeedClosedException::class);

        app(SubmitOffer::class)->handle($business, $need, ['message' => 'Tarde'], User::factory()->create());
    }
}
