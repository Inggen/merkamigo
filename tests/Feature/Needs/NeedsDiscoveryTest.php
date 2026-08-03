<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2.1 del TODO: exploración pública de "Pídelo en Merkamigo" y "Mis solicitudes".
 */
class NeedsDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_pidelo_shows_published_needs_from_the_preferred_municipality_with_offer_counts(): void
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $other = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito jardinero', 'description' => 'Podar el jardín.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $elsewhere = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesidad en otro municipio', 'description' => 'Descripción.', 'municipality_id' => $other->id,
        ]);
        $elsewhere->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Jardinería Test'])->business;
        app(SubmitOffer::class)->handle($business, $need, ['message' => 'Puedo ayudarte'], $owner);

        $response = $this->withUnencryptedCookie('municipio', 'cajica')->get(route('pidelo'));

        $response->assertOk()
            ->assertSee($need->title)
            ->assertDontSee($elsewhere->title)
            ->assertSee('1 propuesta');
    }

    public function test_an_explicit_municipio_query_parameter_overrides_the_preferred_municipality_cookie(): void
    {
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        Municipality::create(['name' => 'Chía', 'slug' => 'chia', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Torta de Red Velvet', 'description' => 'Para un cumpleaños.', 'municipality_id' => $zipaquira->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        // La cookie de preferencia guardada es Chía, pero el enlace "Ver
        // todas" desde la Plaza de Zipaquirá debe respetar ese municipio
        // explícito en vez de caer a la cookie desactualizada.
        $this->withUnencryptedCookie('municipio', 'chia')
            ->get(route('pidelo', ['municipio' => 'zipaquira']))
            ->assertOk()
            ->assertSee('Torta de Red Velvet');
    }

    public function test_a_need_can_be_viewed_publicly_regardless_of_the_preferred_municipality_cookie(): void
    {
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        Municipality::create(['name' => 'Chía', 'slug' => 'chia', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Torta de Red Velvet', 'description' => 'Para un cumpleaños.', 'municipality_id' => $zipaquira->id, 'budget' => 80000,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $this->withUnencryptedCookie('municipio', 'chia')
            ->get(route('pidelo.show', $need))
            ->assertOk()
            ->assertSee('Torta de Red Velvet')
            ->assertSee('Presupuesto: $80.000');
    }

    public function test_a_draft_need_is_not_publicly_viewable(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Aún sin publicar', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);

        $this->get(route('pidelo.show', $need))->assertNotFound();
    }

    public function test_publishing_a_new_need_still_works_and_is_not_intercepted_by_the_need_detail_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pidelo.nueva'))
            ->assertOk();
    }

    public function test_pidelo_asks_to_choose_a_municipality_when_there_is_none_preferred(): void
    {
        $this->get(route('pidelo'))
            ->assertOk()
            ->assertSee(__('Elige tu municipio para ver solicitudes'));
    }

    public function test_mis_solicitudes_only_lists_the_authenticated_users_needs(): void
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $mine = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Mi necesidad', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);

        app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesidad ajena', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('mis-solicitudes'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee('Necesidad ajena');
    }

    public function test_guests_are_redirected_to_login_from_mis_solicitudes(): void
    {
        $this->get(route('mis-solicitudes'))->assertRedirect(route('login'));
    }
}
