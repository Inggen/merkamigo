<?php

namespace Tests\Feature\Businesses;

use App\Domain\Businesses\Actions\AddBusinessMember;
use App\Domain\Businesses\Actions\UpdateMemberRole;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 0.5 del TODO: ningún negocio puede consultar o modificar información
 * privada de otro, y un colaborador no puede autoescalar su propio rol.
 */
class BusinessIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_of_one_business_cannot_view_another_business(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $businessB = app(CreateStorefront::class)->handle($ownerB, ['name' => 'Negocio B'])->business;

        Sanctum::actingAs($ownerA);

        $this->getJson(route('api.v1.businesses.show', $businessA))->assertOk();
        $this->getJson(route('api.v1.businesses.show', $businessB))->assertForbidden();
    }

    public function test_a_member_cannot_escalate_their_own_role(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio con equipo'])->business;

        $collaborator = User::factory()->create();
        app(AddBusinessMember::class)->handle($owner, $business, $collaborator, 'collaborator');

        $this->expectException(AuthorizationException::class);

        app(UpdateMemberRole::class)->handle($collaborator, $business, $collaborator, 'admin');
    }

    public function test_only_the_owner_can_grant_ownership(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio con admin'])->business;

        $admin = User::factory()->create();
        app(AddBusinessMember::class)->handle($owner, $business, $admin, 'admin');

        $collaborator = User::factory()->create();
        app(AddBusinessMember::class)->handle($owner, $business, $collaborator, 'collaborator');

        $this->expectException(AuthorizationException::class);

        app(UpdateMemberRole::class)->handle($admin, $business, $collaborator, 'owner');
    }

    public function test_a_revoked_token_no_longer_grants_access(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio con token'])->business;

        $token = $owner->createToken('device')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.businesses.show', $business))
            ->assertOk();

        $owner->tokens()->delete();

        // Laravel memoiza el usuario resuelto por el guard dentro del mismo
        // proceso de prueba; en una petición HTTP real cada request resuelve
        // el guard desde cero, así que forzamos ese mismo comportamiento
        // aquí para verificar la revocación real del token.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.businesses.show', $business))
            ->assertUnauthorized();
    }
}
