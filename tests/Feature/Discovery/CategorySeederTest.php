<?php

namespace Tests\Feature\Discovery;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido del usuario: reemplazar el listado provisional de categorías por
 * el definitivo de 19 — conservando las que coinciden, borrando las que ya
 * no están en el listado nuevo, y agregando las que faltan.
 */
class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_leaves_exactly_the_19_categories_from_the_final_list(): void
    {
        Category::create(['name' => 'Hoteles y renta de inmuebles', 'slug' => 'hoteles-y-renta-de-inmuebles', 'icon' => 'building-office-2', 'is_active' => true]);
        Category::create(['name' => 'Autos', 'slug' => 'autos', 'icon' => 'tag', 'is_active' => true]);

        $this->seed(CategorySeeder::class);

        $this->assertSame(19, Category::count());
        $this->assertDatabaseMissing('categories', ['slug' => 'hoteles-y-renta-de-inmuebles']);
        $this->assertDatabaseMissing('categories', ['slug' => 'autos']);
        $this->assertDatabaseHas('categories', ['slug' => 'inmuebles-y-arriendos']);
        $this->assertDatabaseHas('categories', ['slug' => 'turismo-y-hospedaje']);
        $this->assertDatabaseHas('categories', ['slug' => 'tecnologia-y-electronica']);
        $this->assertDatabaseHas('categories', ['slug' => 'mascotas']);
    }

    /**
     * Un negocio que ya usaba una categoría que SÍ sigue en el listado
     * nuevo no debe perder esa relación al re-correr el seeder.
     */
    public function test_re_seeding_keeps_the_category_of_existing_businesses(): void
    {
        Category::create(['name' => 'Alimentos y bebidas', 'slug' => 'alimentos-y-bebidas', 'icon' => 'cake', 'is_active' => true]);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $owner = User::factory()->create();

        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería',
            'category_id' => Category::where('slug', 'alimentos-y-bebidas')->value('id'),
            'municipality_id' => $municipality->id,
        ])->business;

        $this->seed(CategorySeeder::class);

        $this->assertSame('Alimentos y bebidas', $business->fresh()->category?->name);
    }

    /**
     * Un negocio cuya categoría vieja se elimina (no está en el listado
     * nuevo) queda sin categoría, nunca se borra el negocio — el índice
     * de la migración es `nullOnDelete()`.
     */
    public function test_re_seeding_nulls_the_category_of_businesses_using_a_removed_one(): void
    {
        $removed = Category::create(['name' => 'Hoteles y renta de inmuebles', 'slug' => 'hoteles-y-renta-de-inmuebles', 'icon' => 'building-office-2', 'is_active' => true]);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $owner = User::factory()->create();

        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Hotel Ejemplo',
            'category_id' => $removed->id,
            'municipality_id' => $municipality->id,
        ])->business;

        $this->seed(CategorySeeder::class);

        $this->assertNotNull(Business::find($business->id));
        $this->assertNull($business->fresh()->category_id);
    }
}
