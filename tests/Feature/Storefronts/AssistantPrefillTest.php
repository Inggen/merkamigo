<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Models\Need;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pedido del usuario: el asistente general arma "Pídelo en Merkamigo" o el
 * primer paso de "Mi Merkamigo en cinco minutos" a partir de la
 * conversación y lleva a la persona al formulario real ya con los datos
 * listos — nunca crea nada directamente. Cubre que esos formularios sí
 * leen los parámetros que el asistente les manda.
 *
 * Se compara contra el estado real del componente (el `wire:snapshot`
 * que Livewire manda en el HTML inicial) en vez de `assertSee` sobre el
 * HTML crudo: los `flux:input`/`flux:textarea` de este proyecto no
 * imprimen un atributo `value="..."` del lado del servidor — el valor
 * inicial se hidrata en el navegador a partir de ese snapshot — así que
 * `assertSee` no vería el valor precargado (y con tildes, json_encode lo
 * escapa a \uXXXX, rompiendo la comparación de todas formas).
 */
class AssistantPrefillTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function livewireData(TestResponse $response): array
    {
        $html = $response->getContent();
        preg_match('/wire:snapshot="([^"]+)"/', $html, $matches);

        $decoded = json_decode(html_entity_decode($matches[1]), true);

        return $decoded['data'] ?? [];
    }

    public function test_pidelo_nueva_prefills_from_query_params_when_there_is_no_draft(): void
    {
        $category = Category::create(['name' => 'Plomería', 'slug' => 'plomeria', 'is_active' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('pidelo.nueva', [
            'titulo' => 'Necesito un plomero urgente',
            'descripcion' => 'Se dañó una tubería en la cocina.',
            'categoria' => $category->slug,
        ]))->assertOk();

        $data = $this->livewireData($response);

        $this->assertSame('Necesito un plomero urgente', $data['title']);
        $this->assertSame('Se dañó una tubería en la cocina.', $data['description']);
        $this->assertSame($category->id, $data['category_id']);
    }

    public function test_pidelo_nueva_does_not_overwrite_an_existing_draft_with_query_params(): void
    {
        $user = User::factory()->create();
        Need::create([
            'user_id' => $user->id,
            'title' => 'Mi borrador original',
            'description' => 'Descripción original.',
            'status' => Need::BORRADOR,
        ]);

        $response = $this->actingAs($user)->get(route('pidelo.nueva', ['titulo' => 'Título que no debería aplicar']))
            ->assertOk();

        $this->assertSame('Mi borrador original', $this->livewireData($response)['title']);
    }

    public function test_crear_vitrina_prefills_step_one_from_query_params_when_there_is_no_draft(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos y bebidas', 'slug' => 'alimentos-y-bebidas', 'is_active' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('emprendedores.crear-vitrina', [
            'nombre' => 'Panadería Doña Rosa',
            'whatsapp' => '3001234567',
            'descripcion' => 'Pan artesanal recién horneado.',
            'categoria' => $category->slug,
            'municipio' => $municipality->slug,
        ]))->assertOk();

        $data = $this->livewireData($response);

        $this->assertSame('Panadería Doña Rosa', $data['name']);
        $this->assertSame('3001234567', $data['whatsapp_number']);
        $this->assertSame('Pan artesanal recién horneado.', $data['description']);
        $this->assertSame($category->id, $data['category_id']);
        $this->assertSame($municipality->id, $data['municipality_id']);
    }

    public function test_crear_vitrina_does_not_overwrite_an_existing_draft_business_with_query_params(): void
    {
        $user = User::factory()->create();
        app(CreateStorefront::class)->handle($user, ['name' => 'Mi Negocio En Curso']);

        $response = $this->actingAs($user)->get(route('emprendedores.crear-vitrina', ['nombre' => 'Nombre que no debería aplicar']))
            ->assertOk();

        $this->assertSame('Mi Negocio En Curso', $this->livewireData($response)['name']);
    }
}
