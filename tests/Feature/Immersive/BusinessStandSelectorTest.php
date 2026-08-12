<?php

namespace Tests\Feature\Immersive;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandSlot;
use App\Domain\Immersive\Models\StandZone;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * IMM-021 del TODO inmersivo: "Mi stand en la plaza" — el emprendedor
 * elige entre las plantillas publicadas; no puede mover ni redimensionar.
 */
class BusinessStandSelectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadyExperience(): ImmersiveExperience
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'plaza-zipaquira',
            'route_name' => 'labs.zipa-inmersiva',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
        $experience->update(['status' => 'publicada']);

        $plaza->zones()->create([
            'name' => 'Zona única',
            'polygon' => ['points' => [
                ['x' => -20, 'z' => -20], ['x' => 20, 'z' => -20], ['x' => 20, 'z' => 20], ['x' => -20, 'z' => 20],
            ]],
        ]);

        return $experience->fresh(['plazas.zones']);
    }

    private function makeSlot(ImmersivePlaza $plaza, string $code): StandSlot
    {
        /** @var StandZone $zone */
        $zone = $plaza->zones->first();
        $index = $zone->slots()->count();

        return $zone->slots()->create([
            'code' => $code,
            'world_position' => ['x' => -15 + ($index * 10), 'y' => 0, 'z' => -15],
            'max_width' => 4,
            'max_depth' => 4,
        ]);
    }

    /**
     * @return array<int, ImmersiveObjectTemplate>
     */
    private function makeTemplates(): array
    {
        return [
            ImmersiveObjectTemplate::create([
                'name' => 'Caseta de madera', 'slug' => 'caseta-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standBooth',
                'max_width' => 3.6, 'max_depth' => 3.2, 'max_height' => 2.9, 'status' => 'publicada',
            ]),
            ImmersiveObjectTemplate::create([
                'name' => 'Mesa exhibidora', 'slug' => 'mesa-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standTable',
                'max_width' => 3.2, 'max_depth' => 2.4, 'max_height' => 2.9, 'status' => 'publicada',
            ]),
        ];
    }

    private function makeBusiness(int $municipalityId): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio de prueba '.uniqid(),
            'municipality_id' => $municipalityId,
        ])->business;

        $this->actingAs($owner);

        return $business;
    }

    public function test_owner_sees_the_three_templates_and_current_status(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplates();
        $this->makeSlot($experience->plazas->first(), 'S1');

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        Livewire::test('pages::emprendedores.negocios.mi-stand', ['business' => $business->id])
            ->assertSuccessful()
            ->assertSee('Publicado')
            ->assertSee('Caseta de madera')
            ->assertSee('Mesa exhibidora');
    }

    public function test_owner_can_switch_template_and_keeps_the_same_slot(): void
    {
        $experience = $this->makeReadyExperience();
        [$booth, $table] = $this->makeTemplates();
        $this->makeSlot($experience->plazas->first(), 'S1');

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        $originalSlotId = $business->fresh()->standAssignment->stand_slot_id;
        $this->assertSame($booth->id, $business->fresh()->standAssignment->object_template_id);

        Livewire::test('pages::emprendedores.negocios.mi-stand', ['business' => $business->id])
            ->call('chooseTemplate', $table->id)
            ->assertSuccessful();

        $assignment = $business->fresh()->standAssignment;
        $this->assertSame($table->id, $assignment->object_template_id);
        $this->assertSame($originalSlotId, $assignment->stand_slot_id, 'Cambiar de plantilla no debe mover el stand de slot si el actual sigue siendo compatible.');
        $this->assertDatabaseCount('stand_assignments', 1);
    }

    public function test_a_business_without_a_published_experience_sees_a_pending_message(): void
    {
        $municipality = Municipality::create(['name' => 'Cota', 'slug' => 'cota']);
        $this->makeTemplates();

        $business = $this->makeBusiness($municipality->id);
        $business->update(['status' => 'publicado']);

        Livewire::test('pages::emprendedores.negocios.mi-stand', ['business' => $business->id])
            ->assertSuccessful()
            ->assertSee('Pendiente');
    }

    public function test_a_stranger_cannot_manage_someone_elses_stand(): void
    {
        $experience = $this->makeReadyExperience();
        $business = $this->makeBusiness($experience->municipality_id);

        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        Livewire::test('pages::emprendedores.negocios.mi-stand', ['business' => $business->id])
            ->assertForbidden();
    }
}
