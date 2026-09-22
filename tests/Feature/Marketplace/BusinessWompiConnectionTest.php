<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Decisión de arquitectura (sesión 15 sep 2026): cada negocio conecta SU
 * PROPIA cuenta Wompi — el pago de un cliente va directo a esa cuenta,
 * Merkamigo nunca recauda dinero de terceros.
 */
class BusinessWompiConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function credentials(): array
    {
        return [
            'public_key' => 'pub_test_abc123',
            'private_key' => 'prv_test_abc123',
            'integrity_secret' => 'test-integrity-secret',
            'events_secret' => 'test-events-secret',
            'environment' => 'sandbox',
        ];
    }

    private function verify(Business $business): void
    {
        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);
    }

    public function test_a_business_can_connect_its_own_wompi_account(): void
    {
        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Midulce Violeta'])->business;
        $this->verify($business);

        $credential = app(ConnectBusinessWompi::class)->handle($business, $this->credentials(), $owner);

        $this->assertSame('pub_test_abc123', $credential->public_key);
        $this->assertTrue($credential->is_active);
        $this->assertNotNull($credential->connected_at);
        $this->assertTrue($business->fresh()->hasWompiConnected());

        // Las llaves sensibles quedan cifradas en la base, no en texto plano.
        $raw = DB::table('business_wompi_credentials')->where('business_id', $business->id)->first();
        $this->assertNotSame('prv_test_abc123', $raw->private_key);
    }

    public function test_a_business_without_identity_verification_cannot_connect_wompi(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Verificar'])->business;

        $this->expectException(ValidationException::class);

        app(ConnectBusinessWompi::class)->handle($business, $this->credentials(), $owner);
    }

    public function test_connecting_is_rejected_if_wompi_cannot_verify_the_public_key(): void
    {
        Http::fake(['*/merchants/*' => Http::response([], 404)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio'])->business;
        $this->verify($business);

        $this->expectException(ValidationException::class);

        app(ConnectBusinessWompi::class)->handle($business, $this->credentials(), $owner);
    }

    public function test_owner_can_connect_wompi_from_the_panel(): void
    {
        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Panel'])->business;
        $this->verify($business);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.cobros-en-linea', ['business' => $business->id])
            ->set('public_key', 'pub_test_panel')
            ->set('private_key', 'prv_test_panel')
            ->set('integrity_secret', 'secret-integrity')
            ->set('events_secret', 'secret-events')
            ->set('environment', 'sandbox')
            ->call('connect')
            ->assertHasNoErrors();

        $this->assertTrue($business->fresh()->hasWompiConnected());
    }

    public function test_the_panel_shows_a_verification_prompt_instead_of_the_form_when_unverified(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Verificar Panel'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.cobros-en-linea', ['business' => $business->id])
            ->assertSee(__('Primero verifica tu negocio'))
            ->assertDontSee(__('2. Pega tus llaves de Wompi'));
    }
}
