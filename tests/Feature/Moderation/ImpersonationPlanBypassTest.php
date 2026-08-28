<?php

namespace Tests\Feature\Moderation;

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Platform\Actions\StartUserImpersonation;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bug real reportado por el usuario: al entrar "como" un usuario sin
 * plan Emprendedor desde el admin, las funciones de ese plan (IA de
 * vitrina/productos, chatbot, plantillas de stand Pro) quedaban
 * bloqueadas porque `Auth::user()` durante la impersonación ya no es el
 * superadmin, sino el usuario impersonado.
 */
class ImpersonationPlanBypassTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    private function impersonate(): Business
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');

        $owner = User::factory()->create(['experience' => 'emprendedor']);
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Plan'])->business;

        $this->actingAs($superadmin);
        app(StartUserImpersonation::class)->handle($superadmin, $owner);
        $this->assertAuthenticatedAs($owner);

        return $business;
    }

    public function test_can_improve_the_storefront_description_while_impersonating(): void
    {
        $business = $this->impersonate();

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Descripción generada durante la impersonación.';
            }
        });

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->call('improveDescription')
            ->assertSet('description', 'Descripción generada durante la impersonación.');
    }

    public function test_can_improve_a_product_description_while_impersonating(): void
    {
        $business = $this->impersonate();

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Descripción de producto generada durante la impersonación.';
            }
        });

        Livewire::test('pages::emprendedores.negocios.productos', ['business' => $business->id])
            ->call('openCreate')
            ->set('name', 'Torta de chocolate')
            ->call('improveProductDescription')
            ->assertSet('description', 'Descripción de producto generada durante la impersonación.');
    }

    public function test_can_access_the_chatbot_page_while_impersonating(): void
    {
        $business = $this->impersonate();

        $this->get(route('emprendedores.negocios.chatbot', $business))
            ->assertOk();
    }

    public function test_the_chatbot_nav_link_shows_up_while_impersonating(): void
    {
        $business = $this->impersonate();

        $this->get(route('emprendedores.negocios.vitrina', $business))
            ->assertOk()
            ->assertSee('Chatbot IA');
    }

    public function test_can_choose_a_pro_stand_template_while_impersonating(): void
    {
        $business = $this->impersonate();

        $proTemplate = ImmersiveObjectTemplate::create([
            'name' => 'Stand Pro', 'slug' => 'stand-pro-'.uniqid(), 'category' => 'stand',
            'builder_key' => 'stand', 'max_width' => 3.6, 'max_depth' => 3.2, 'max_height' => 2.9,
            'status' => 'publicada',
        ]);

        Livewire::test('pages::emprendedores.negocios.mi-stand', ['business' => $business->id])
            ->assertSee($proTemplate->name)
            ->assertDontSee('Desbloquear')
            ->assertSee('Elegir');
    }
}
