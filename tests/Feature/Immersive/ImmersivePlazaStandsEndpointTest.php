<?php

namespace Tests\Feature\Immersive;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandAssignment;
use App\Domain\Immersive\Models\StandSlot;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMM-020b (puente mínimo de stands dinámicos): el endpoint público que
 * `dynamic-stand-loader.js` consume para dibujar, encima de una plaza fija,
 * los stands realmente ocupados según la base de datos.
 */
class ImmersivePlazaStandsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function makePlaza(): ImmersivePlaza
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira']);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'plaza-zipaquira',
            'route_name' => 'labs.generic-plaza',
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

        return $plaza->fresh(['zones']);
    }

    private function makeSlot(ImmersivePlaza $plaza, string $code): StandSlot
    {
        $zone = $plaza->zones->first();
        $index = $zone->slots()->count();

        return $zone->slots()->create([
            'code' => $code,
            'world_position' => ['x' => -15 + ($index * 10), 'y' => 0, 'z' => -15],
            'max_width' => 4,
            'max_depth' => 4,
        ]);
    }

    private function makeTemplate(): ImmersiveObjectTemplate
    {
        return ImmersiveObjectTemplate::create([
            'name' => 'Caseta de madera', 'slug' => 'caseta-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standBooth',
            'max_width' => 4.2, 'max_depth' => 3.8, 'max_height' => 2.9, 'status' => 'publicada',
        ]);
    }

    private function makeBusiness(int $municipalityId): Business
    {
        $owner = User::factory()->create();

        return app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio de prueba '.uniqid(),
            'municipality_id' => $municipalityId,
        ])->business;
    }

    public function test_a_live_stand_appears_with_its_builder_key(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('S1', $data[0]['slot_code']);
        $this->assertSame('standBooth', $data[0]['builder_key']);
    }

    public function test_a_live_stand_with_a_glb_exposes_its_model_url(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Caseta GLB', 'slug' => 'caseta-glb-'.uniqid(), 'category' => 'stand',
            'builder_key' => 'standBooth', 'model_path' => 'immersive-object-templates/models/caseta.glb',
            'max_width' => 4.2, 'max_depth' => 3.8, 'max_height' => 2.9, 'status' => 'publicada',
        ]);
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertStringContainsString('immersive-object-templates/models/caseta.glb', $data[0]['model_url']);
    }

    /**
     * Pedido del usuario: un stand con una plantilla "nativa" más chica
     * que el slot que ocupa se veía siempre al tamaño fijo de la
     * plantilla, sin importar el ancho/profundidad real del slot — el
     * endpoint nunca mandaba ningún `scale` (a diferencia del de props).
     * La escala debe ser UNIFORME (misma en X, Y, Z) para no deformar la
     * plantilla — se usa la menor de las dos proporciones (ancho,
     * profundidad) para no desbordar el slot en ningún eje.
     */
    public function test_a_live_stand_scales_uniformly_to_fit_a_slot_bigger_than_its_template(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1'); // max_width=4, max_depth=4
        $template = $this->makeTemplate(); // max_width=4.2, max_depth=3.8
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        // menor proporción: ancho (4/4.2 ≈ 0.9524) vs profundidad (4/3.8 ≈ 1.0526)
        $expectedScale = round(4 / 4.2, 4);
        $this->assertEquals($expectedScale, $data[0]['scale']['x']);
        $this->assertEquals($expectedScale, $data[0]['scale']['y']);
        $this->assertEquals($expectedScale, $data[0]['scale']['z']);
    }

    /**
     * IMM-032 (activación por proximidad): el indicador "Ver vitrina" en
     * la escena 3D necesita saber a qué negocio pertenece el stand y a
     * dónde llevar al hacer clic — antes el endpoint no mandaba nada del
     * negocio, solo geometría.
     */
    public function test_a_live_stand_exposes_its_business_for_the_proximity_indicator(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertSame($business->name, $data[0]['business']['name']);
        $this->assertSame(route('vitrinas.show', $business), $data[0]['business']['vitrina_url']);
    }

    /**
     * Color de stand elegido por el emprendedor (editor de vitrina): el
     * endpoint debe exponerlo para que `applyPrimaryColor()`
     * (`dynamic-stand-loader.js`) lo aplique en la escena, y debe ser
     * `null` cuando el negocio nunca eligió uno (el render usa los colores
     * por defecto de la plantilla).
     */
    public function test_a_live_stand_exposes_its_chosen_stand_color(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);
        $business->storefront->update(['stand_color' => '#6E5A80']);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertSame('#6E5A80', $data[0]['business']['stand_color']);
    }

    public function test_a_live_stand_without_a_chosen_color_exposes_null(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertNull($data[0]['business']['stand_color']);
    }

    /**
     * La "persona" que `dynamic-stand-loader.js` planta junto a cada stand
     * (`attachOwnerFigure`) usa el preset hombre/mujer que el DUEÑO del
     * negocio (dueño de la `Organization`, no cualquier miembro) eligió
     * para sí mismo en `/settings/avatar`.
     */
    public function test_a_live_stand_exposes_its_owners_chosen_avatar_preset(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);
        $owner = $business->organization->owner;
        $owner->avatar_preset = 'mujer';
        $owner->save();

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertSame('mujer', $data[0]['business']['owner_avatar_preset']);
    }

    public function test_a_live_stand_without_an_owner_avatar_preset_defaults_to_hombre(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $data = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands")->json('data');

        $this->assertSame('hombre', $data[0]['business']['owner_avatar_preset']);
    }

    public function test_a_paused_assignment_does_not_appear(): void
    {
        $plaza = $this->makePlaza();
        $slot = $this->makeSlot($plaza, 'S1');
        $template = $this->makeTemplate();
        $business = $this->makeBusiness($plaza->experience->municipality_id);

        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'pausado',
        ]);

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands");

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_a_slot_without_any_assignment_does_not_appear(): void
    {
        $plaza = $this->makePlaza();
        $this->makeSlot($plaza, 'S1');

        $response = $this->getJson("/api/v1/inmersivo/plazas/{$plaza->id}/stands");

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
