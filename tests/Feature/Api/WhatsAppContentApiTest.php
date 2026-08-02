<?php

namespace Tests\Feature\Api;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 5.1/1.7/4.4 del TODO: Copiloto de WhatsApp vía API.
 */
class WhatsAppContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_team_member_can_generate_and_save_a_draft(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Copiloto'])->business;
        Sanctum::actingAs($owner);

        $generated = $this->postJson(route('api.v1.businesses.whatsapp-contents.generar', $business), [
            'type' => 'estado',
            'tone' => 'cercano',
        ])->assertOk()->json('data.content');

        $this->assertNotEmpty($generated);

        $this->postJson(route('api.v1.businesses.whatsapp-contents.store', $business), [
            'type' => 'estado',
            'tone' => 'cercano',
            'content' => $generated,
        ])->assertCreated()->assertJsonPath('data.type', 'estado');

        $this->getJson(route('api.v1.businesses.whatsapp-contents.index', $business))
            ->assertOk()
            ->assertJsonPath('data.0.content', $generated);
    }

    public function test_someone_outside_the_business_cannot_use_its_copilot(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.businesses.whatsapp-contents.generar', $business), [
            'type' => 'estado', 'tone' => 'cercano',
        ])->assertForbidden();
    }
}
