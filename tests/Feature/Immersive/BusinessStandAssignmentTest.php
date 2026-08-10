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
use Tests\TestCase;

/**
 * IMM-022/IMM-023 del TODO inmersivo: "toda vitrina elegible recibe plaza
 * y ubicación automáticamente" / estados del stand sincronizados con el
 * ciclo de vida del negocio.
 */
class BusinessStandAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadyExperience(string $municipalitySlug = 'zipaquira'): ImmersiveExperience
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => $municipalitySlug]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => "plaza-{$municipalitySlug}",
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

    private function makeSlot(ImmersivePlaza $plaza, string $code, float $size = 4.0): StandSlot
    {
        /** @var StandZone $zone */
        /** @var StandZone $zone */
        $zone = $plaza->zones->first();
        $index = $zone->slots()->count();

        return $zone->slots()->create([
            'code' => $code,
            'world_position' => ['x' => -15 + ($index * 10), 'y' => 0, 'z' => -15],
            'max_width' => $size,
            'max_depth' => $size,
        ]);
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Stand estándar', 'slug' => 'stand-estandar-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standTable',
            'max_width' => 3.2, 'max_depth' => 2.4, 'max_height' => 2.9, 'status' => 'publicada',
        ]);
    }

    private function makeBusiness(?int $municipalityId = null): Business
    {
        $owner = User::factory()->create();

        return app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio de prueba '.uniqid(),
            'municipality_id' => $municipalityId,
        ])->business;
    }

    public function test_publishing_a_business_automatically_assigns_a_stand(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplate();
        $this->makeSlot($experience->plazas->first(), 'S1');

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        $assignment = $business->fresh()->standAssignment;

        $this->assertNotNull($assignment);
        $this->assertSame('publicado', $assignment->status);
        $this->assertNotNull($assignment->stand_slot_id);
        $this->assertSame('ocupada', $assignment->slot->status);
    }

    public function test_publishing_without_a_published_experience_leaves_it_pending(): void
    {
        $this->makeTemplate();
        $municipality = Municipality::create(['name' => 'Cota', 'slug' => 'cota-sin-experiencia']);
        $business = $this->makeBusiness($municipality->id);
        $business->update(['status' => 'publicado']);

        $assignment = $business->fresh()->standAssignment;

        $this->assertSame('pendiente', $assignment->status);
        $this->assertNull($assignment->stand_slot_id);
    }

    public function test_no_compatible_slot_results_in_sin_cupo(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplate();
        // Un solo slot, ya ocupado por otro negocio.
        $slot = $this->makeSlot($experience->plazas->first(), 'S1');
        $slot->update(['status' => 'ocupada']);

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        $this->assertSame('sin_cupo', $business->fresh()->standAssignment->status);
    }

    public function test_suspending_a_business_releases_its_slot_and_can_be_recovered(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplate();
        $this->makeSlot($experience->plazas->first(), 'S1');

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        $originalSlotId = $business->fresh()->standAssignment->stand_slot_id;
        $this->assertNotNull($originalSlotId);

        $business->update(['status' => 'suspendido']);

        $paused = $business->fresh()->standAssignment;
        $this->assertSame('pausado', $paused->status);
        $this->assertNull($paused->stand_slot_id);
        $this->assertSame($originalSlotId, $paused->previous_slot_id);
        $this->assertSame('disponible', StandSlot::find($originalSlotId)->status);

        $business->update(['status' => 'publicado']);

        $recovered = $business->fresh()->standAssignment;
        $this->assertSame('publicado', $recovered->status);
        $this->assertSame($originalSlotId, $recovered->stand_slot_id, 'Debe recuperar el mismo slot que tenía antes de pausarse.');
    }

    public function test_deleting_a_business_releases_its_slot(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplate();
        $slot = $this->makeSlot($experience->plazas->first(), 'S1');

        $business = $this->makeBusiness($experience->municipality_id);
        $business->update(['status' => 'publicado']);

        $this->assertSame('ocupada', $slot->fresh()->status);

        $business->delete();

        $this->assertSame('disponible', $slot->fresh()->status);
    }

    public function test_two_businesses_get_different_slots(): void
    {
        $experience = $this->makeReadyExperience();
        $this->makeTemplate();
        $slotA = $this->makeSlot($experience->plazas->first(), 'S1');
        $slotB = $this->makeSlot($experience->plazas->first(), 'S2');

        $businessA = $this->makeBusiness($experience->municipality_id);
        $businessA->update(['status' => 'publicado']);

        $businessB = $this->makeBusiness($experience->municipality_id);
        $businessB->update(['status' => 'publicado']);

        $assignmentA = $businessA->fresh()->standAssignment;
        $assignmentB = $businessB->fresh()->standAssignment;

        $this->assertNotSame($assignmentA->stand_slot_id, $assignmentB->stand_slot_id);
        $this->assertContains($assignmentA->stand_slot_id, [$slotA->id, $slotB->id]);
        $this->assertContains($assignmentB->stand_slot_id, [$slotA->id, $slotB->id]);
    }
}
