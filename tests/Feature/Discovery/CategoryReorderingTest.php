<?php

namespace Tests\Feature\Discovery;

use App\Domain\Discovery\Models\Category;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reordenar categorías desde el panel de Filament (arrastrar y soltar,
 * columna `position`).
 */
class CategoryReorderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_reorder_categories_from_the_filament_table(): void
    {
        $admin = User::factory()->create();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $first = Category::create(['name' => 'Primero', 'slug' => 'primero', 'position' => 0, 'is_active' => true]);
        $second = Category::create(['name' => 'Segundo', 'slug' => 'segundo', 'position' => 1, 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertOk()
            ->call('reorderTable', [$second->getKey(), $first->getKey()])
            ->assertHasNoErrors();

        $this->assertTrue($second->fresh()->position < $first->fresh()->position);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Pedido del usuario: poder elegir el ícono de cada categoría desde el
     * admin, en vez de que quede fijo en el seeder.
     */
    public function test_an_admin_can_choose_the_icon_when_creating_a_category(): void
    {
        $this->makeAdmin();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Autos',
                'slug' => 'autos',
                'icon' => 'truck',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['slug' => 'autos', 'icon' => 'truck']);
    }

    public function test_an_admin_can_change_the_icon_of_an_existing_category(): void
    {
        $this->makeAdmin();
        $category = Category::create(['name' => 'Mascotas', 'slug' => 'mascotas', 'icon' => 'tag', 'is_active' => true]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm(['icon' => 'hand-raised'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('hand-raised', $category->fresh()->icon);
    }
}
