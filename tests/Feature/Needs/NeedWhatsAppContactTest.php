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
 * 2.2 del TODO: "CTA para continuar por WhatsApp... registrar propuesta
 * vista y contacto iniciado".
 */
class NeedWhatsAppContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_continuing_by_whatsapp_registers_the_contact_and_redirects(): void
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito algo', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio WhatsApp', 'whatsapp_number' => '+573001112233',
        ])->business;
        $offer = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Puedo ayudarte'], $owner);

        $response = $this->actingAs($buyer)->get(route('mis-solicitudes.whatsapp', [$need, $offer]));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/573001112233', $response->headers->get('Location'));

        $this->assertDatabaseHas('analytics_events', [
            'business_id' => $business->id,
            'type' => 'whatsapp_click',
            'subject_id' => $offer->id,
        ]);
    }

    public function test_a_stranger_cannot_use_someone_elses_need_to_contact_whatsapp(): void
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesito algo', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio WhatsApp', 'whatsapp_number' => '+573001112233',
        ])->business;
        $offer = app(SubmitOffer::class)->handle($business, $need, ['message' => 'Puedo ayudarte'], $owner);

        $this->actingAs(User::factory()->create())
            ->get(route('mis-solicitudes.whatsapp', [$need, $offer]))
            ->assertForbidden();
    }
}
