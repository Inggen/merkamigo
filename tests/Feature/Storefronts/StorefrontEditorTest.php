<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessAttribute;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 1.6 del TODO: editor de vitrina del panel (secciones, publicar/despublicar).
 */
class StorefrontEditorTest extends TestCase
{
    use RefreshDatabase;

    private function emprendedorPlan(): Plan
    {
        return Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Desbloquea mejores capacidades para tu negocio.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7],
            'trial_days' => 14,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    private function subscribeToEmprendedorPlan(Business $business, User $actor): void
    {
        app(SubscribeToPlan::class)->handle($business, $this->emprendedorPlan(), $actor);
    }

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_owner_can_edit_and_publish_from_the_editor(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Editor',
            'whatsapp_number' => '+573001112233',
        ])->business;

        app(CreateProduct::class)->handle($business, [
            'name' => 'Servicio X', 'type' => 'servicio', 'price_type' => 'consultar',
        ], [], $owner);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('description', 'Descripción completa del negocio.')
            ->set('category_id', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Descripción completa del negocio.', $business->fresh()->storefront->description);

        $component = Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id]);

        // Falta categoría y municipio: no debe publicar todavía.
        $component->call('publish');
        $this->assertSame('borrador', $business->fresh()->status);
        $component->assertSet('missing', fn ($missing) => count($missing) > 0);
    }

    public function test_editing_a_field_autosaves_without_an_explicit_save_call(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Autoguardado',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('headline', 'Frase corta autoguardada')
            ->assertSet('savedAt', fn ($savedAt) => $savedAt !== null);

        $this->assertSame('Frase corta autoguardada', $business->fresh()->storefront->headline);
    }

    public function test_owner_can_set_alt_text_for_the_logo_and_cover(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Alt Text',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('logo_alt_text', 'Logo de Negocio Alt Text')
            ->set('cover_alt_text', 'Fachada de Negocio Alt Text')
            ->assertHasNoErrors();

        $this->assertSame('Logo de Negocio Alt Text', $business->fresh()->logo_alt_text);
        $this->assertSame('Fachada de Negocio Alt Text', $business->fresh()->storefront->cover_alt_text);
    }

    public function test_autosave_survives_losing_the_permissions_team_context_between_requests(): void
    {
        // En producción, cada interacción de Livewire (autosave, guardar,
        // publicar...) llega por el endpoint genérico `/livewire/update`,
        // que no pasa por el middleware `business.team` de la ruta original
        // — solo `boot()` se ejecuta en cada petición. Simulamos ese
        // "olvido" de contexto entre peticiones fijando el team a otro
        // valor justo antes de editar, tal como quedaría tras una petición
        // AJAX real sin ese middleware.
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Sin Contexto',
            'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        $component = Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id]);

        setPermissionsTeamId(null);
        $owner->unsetRelation('roles');

        $component->set('headline', 'Sobrevive sin contexto de equipo')
            ->assertHasNoErrors();

        $this->assertSame('Sobrevive sin contexto de equipo', $business->fresh()->storefront->headline);
    }

    public function test_owner_can_save_a_structured_schedule_and_attributes_from_the_editor(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Horario Editor', 'whatsapp_number' => '+573001112233',
        ])->business;

        $attribute = BusinessAttribute::create(['name' => 'Producto artesanal', 'slug' => 'producto-artesanal', 'is_active' => true]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('schedule.monday.closed', false)
            ->set('schedule.monday.open', '08:00')
            ->set('schedule.monday.close', '18:00')
            ->set('business_attributes', [$attribute->slug])
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $business->fresh();

        $this->assertSame('08:00', $fresh->hours['schedule']['monday']['open']);
        $this->assertSame('18:00', $fresh->hours['schedule']['monday']['close']);
        $this->assertSame([$attribute->slug], $fresh->attributes);
    }

    public function test_owner_can_autofill_the_structured_schedule_from_the_free_text_hours(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Horario IA', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                $schedule = ['closed' => false, 'open' => '08:00', 'close' => '18:00'];

                return json_encode([
                    'monday' => $schedule, 'tuesday' => $schedule, 'wednesday' => $schedule,
                    'thursday' => $schedule, 'friday' => $schedule, 'saturday' => $schedule,
                    'sunday' => ['closed' => true, 'open' => null, 'close' => null],
                ]);
            }
        });

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('hours_text', 'Lun-Sáb 8:00am - 6:00pm')
            ->call('autofillScheduleFromText')
            ->assertSet('schedule.monday.open', '08:00')
            ->assertSet('schedule.sunday.closed', true);

        $fresh = $business->fresh();
        $this->assertSame('08:00', $fresh->hours['schedule']['monday']['open']);
        $this->assertTrue($fresh->hours['schedule']['sunday']['closed']);
    }

    public function test_autofill_does_nothing_when_the_free_text_hours_are_empty(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Horario Vacío', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('hours_text', '')
            ->call('autofillScheduleFromText')
            ->assertHasNoErrors()
            ->assertSet('schedule.monday.open', null);
    }

    public function test_owner_can_improve_the_description_with_ai(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Descripción IA', 'whatsapp_number' => '+573001112233',
        ])->business;
        $this->subscribeToEmprendedorPlan($business, $owner);

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Descripción vendedora generada a partir de los datos reales del negocio.';
            }
        });

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('headline', 'Lo mejor de la zona')
            ->call('improveDescription')
            ->assertSet('description', 'Descripción vendedora generada a partir de los datos reales del negocio.');

        $this->assertSame(
            'Descripción vendedora generada a partir de los datos reales del negocio.',
            $business->fresh()->storefront->description,
        );
    }

    public function test_improve_description_shows_an_error_when_the_ai_does_not_answer(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Descripción Sin IA', 'whatsapp_number' => '+573001112233',
        ])->business;
        $this->subscribeToEmprendedorPlan($business, $owner);

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return null;
            }
        });

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->call('improveDescription')
            ->assertHasNoErrors();

        $this->assertNull($business->fresh()->storefront->description);
    }

    public function test_improve_description_is_blocked_without_the_entrepreneur_plan(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Sin Plan', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Esto nunca debería guardarse.';
            }
        });

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->assertSet('description', null)
            ->call('improveDescription')
            ->assertSet('description', null);

        $this->assertNull($business->fresh()->storefront->description);
    }

    public function test_a_superadmin_can_improve_the_description_without_the_entrepreneur_plan(): void
    {
        $superadmin = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($superadmin, [
            'name' => 'Negocio Superadmin', 'whatsapp_number' => '+573001112233',
        ])->business;
        $this->assignPlatformRole($superadmin, 'superadmin');

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Descripción generada para el superadmin.';
            }
        });

        $this->actingAs($superadmin);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->call('improveDescription')
            ->assertSet('description', 'Descripción generada para el superadmin.');

        $this->assertSame('Descripción generada para el superadmin.', $business->fresh()->storefront->description);
    }

    public function test_the_editor_links_to_the_preview_page(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Vista Previa', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->assertSeeHtml(route('emprendedores.negocios.vista-previa', $business));
    }

    public function test_owner_can_share_and_clear_the_business_location(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Ubicación', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->call('setLocation', 4.9186, -74.0279)
            ->assertHasNoErrors();

        $fresh = $business->fresh();
        $this->assertEqualsWithDelta(4.9186, $fresh->latitude, 0.0001);
        $this->assertEqualsWithDelta(-74.0279, $fresh->longitude, 0.0001);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->call('clearLocation')
            ->assertHasNoErrors();

        $this->assertNull($business->fresh()->latitude);
        $this->assertNull($business->fresh()->longitude);
    }

    public function test_owner_can_set_a_stand_color_for_the_immersive_plaza(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Color de Stand', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('stand_color', '#6E5A80')
            ->assertHasNoErrors();

        $this->assertSame('#6E5A80', $business->fresh()->storefront->stand_color);
    }

    public function test_stand_color_rejects_a_value_that_is_not_a_hex_color(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Color Inválido', 'whatsapp_number' => '+573001112233',
        ])->business;

        $this->actingAs($owner);

        // `updated()` es autoguardado silencioso (a propósito, ver su
        // docblock): un valor inválido simplemente no se guarda, sin
        // mostrar error mientras se escribe. `save()` sí valida "de
        // verdad" y expone el error — se prueban ambos caminos.
        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('stand_color', 'purple');

        $this->assertNull($business->fresh()->storefront->stand_color);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $business->id])
            ->set('stand_color', 'purple')
            ->call('save')
            ->assertHasErrors(['stand_color']);
    }

    public function test_a_collaborator_of_another_business_cannot_open_the_editor(): void
    {
        $ownerA = User::factory()->create();
        $businessA = app(CreateStorefront::class)->handle($ownerA, ['name' => 'Negocio A'])->business;

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);

        Livewire::test('pages::emprendedores.negocios.vitrina', ['business' => $businessA->id])
            ->assertForbidden();
    }
}
