<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\IssueLoyaltyIdentityToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F1.8 — el QR de identificación del cliente
 * identifica, nunca autoriza un retiro de puntos.
 */
class LoyaltyIdentityTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_freshly_issued_token_resolves_back_to_its_owner(): void
    {
        $customer = User::factory()->create();

        $token = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $this->assertStringStartsWith('idn_', $token);
        $resolved = app(IssueLoyaltyIdentityToken::class)->resolve($token);
        $this->assertSame($customer->id, $resolved?->id);
    }

    public function test_calling_handle_again_without_rotating_returns_the_same_token(): void
    {
        // El QR tiene que poder mostrarse de nuevo sin cambiar en cada
        // visita a la página — si cambiara en cada llamada, un QR
        // guardado o impreso dejaría de servir.
        $customer = User::factory()->create();

        $first = app(IssueLoyaltyIdentityToken::class)->handle($customer);
        $second = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $this->assertSame($first, $second);
    }

    public function test_rotating_the_token_invalidates_the_previous_one(): void
    {
        $customer = User::factory()->create();
        $first = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $second = app(IssueLoyaltyIdentityToken::class)->handle($customer, forceRotate: true);

        $this->assertNotSame($first, $second);
        $this->assertNull(app(IssueLoyaltyIdentityToken::class)->resolve($first));
        $this->assertSame($customer->id, app(IssueLoyaltyIdentityToken::class)->resolve($second)?->id);
    }

    public function test_a_redemption_token_is_never_accepted_as_an_identity_token(): void
    {
        $this->assertNull(app(IssueLoyaltyIdentityToken::class)->resolve('rdm_notarealidentitytoken'));
    }

    public function test_an_unknown_or_tampered_token_resolves_to_nothing(): void
    {
        $this->assertNull(app(IssueLoyaltyIdentityToken::class)->resolve('idn_doesnotexist'));
    }
}
