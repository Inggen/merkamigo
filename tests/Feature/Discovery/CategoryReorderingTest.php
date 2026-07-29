<?php

namespace Tests\Feature\Discovery;

use App\Domain\Discovery\Models\Category;
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
}
