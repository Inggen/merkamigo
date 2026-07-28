<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\BusinessMembership;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 1.6 del TODO: gestión básica de colaboradores.
 */
class CollaboratorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_and_remove_a_collaborator(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Equipo'])->business;

        $collaborator = User::factory()->create(['email' => 'ayuda@example.com']);

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.colaboradores', ['business' => $business->id])
            ->set('email', 'ayuda@example.com')
            ->set('role', 'collaborator')
            ->call('invite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $collaborator->id,
        ]);

        $membershipId = BusinessMembership::where('business_id', $business->id)->where('user_id', $collaborator->id)->firstOrFail()->id;

        $component->call('remove', $membershipId);

        $this->assertDatabaseMissing('business_memberships', [
            'business_id' => $business->id,
            'user_id' => $collaborator->id,
        ]);
    }

    public function test_inviting_an_unregistered_email_fails_with_a_friendly_error(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Equipo'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.colaboradores', ['business' => $business->id])
            ->set('email', 'nadie@example.com')
            ->set('role', 'collaborator')
            ->call('invite')
            ->assertHasErrors('email');
    }

    public function test_a_collaborator_cannot_manage_other_collaborators(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Equipo'])->business;

        $other = User::factory()->create();
        $this->actingAs($other);

        Livewire::test('pages::emprendedores.negocios.colaboradores', ['business' => $business->id])
            ->assertForbidden();
    }
}
