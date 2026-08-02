<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Actions\SyncBusinessMunicipalities;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 0.2.2 del TODO: una vitrina puede estar en varios municipios, además del
 * principal (`municipality_id`).
 */
class MultiMunicipalityTest extends TestCase
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

    public function test_a_business_with_an_additional_municipality_appears_in_that_municipalitys_plaza(): void
    {
        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Multi Municipio',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $cajica->id,
            'category_id' => $category->id,
            'description' => 'Atiende dos municipios.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business->fresh(), $owner);

        app(SyncBusinessMunicipalities::class)->handle($business, [$zipaquira->id]);

        $this->get(route('buscar', ['municipio' => $zipaquira->slug]))->assertOk()->assertSee('Negocio Multi Municipio');
        $this->get(route('buscar', ['municipio' => $cajica->slug]))->assertOk()->assertSee('Negocio Multi Municipio');
    }

    public function test_syncing_municipalities_never_duplicates_the_primary_one(): void
    {
        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Test', 'municipality_id' => $cajica->id,
        ])->business;

        app(SyncBusinessMunicipalities::class)->handle($business, [$cajica->id, $zipaquira->id]);

        $this->assertSame([$zipaquira->id], $business->municipalities()->pluck('municipalities.id')->all());
        $this->assertSame([$cajica->id, $zipaquira->id], $business->allMunicipalityIds()->sort()->values()->all());
    }

    public function test_oportunidades_shows_needs_from_every_municipality_the_business_serves(): void
    {
        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Oportunidades Multi', 'municipality_id' => $cajica->id,
        ])->business;
        app(SyncBusinessMunicipalities::class)->handle($business, [$zipaquira->id]);

        $needInCajica = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesito algo en Cajicá', 'description' => 'Descripción.', 'municipality_id' => $cajica->id,
        ]);
        $needInCajica->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $needInZipaquira = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesito algo en Zipaquirá', 'description' => 'Descripción.', 'municipality_id' => $zipaquira->id,
        ]);
        $needInZipaquira->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $business->id])
            ->assertSee('Necesito algo en Cajicá')
            ->assertSee('Necesito algo en Zipaquirá');
    }

    public function test_an_admin_can_assign_additional_municipalities_from_filament(): void
    {
        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Filament Multi', 'municipality_id' => $cajica->id,
        ])->business;

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(ListBusinesses::class)->assertSuccessful();

        Livewire::test(EditBusiness::class, ['record' => $business->getRouteKey()])
            ->fillForm(['municipalities' => [$zipaquira->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($business->fresh()->municipalities->contains('id', $zipaquira->id));
    }
}
