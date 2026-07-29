<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Categorías iniciales provisionales (0.1 del TODO); se ajustarán con datos
 * reales del piloto (ver docs/product/alcance-fase0.md). El ícono es un
 * nombre de icono Heroicon (outline) de los que trae Flux en
 * vendor/livewire/flux/stubs/resources/views/flux/icon.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos y bebidas' => 'cake',
            'Moda y accesorios' => 'shopping-bag',
            'Hogar y decoración' => 'home',
            'Belleza y cuidado personal' => 'sparkles',
            'Servicios profesionales' => 'briefcase',
            'Servicios para el hogar' => 'wrench-screwdriver',
            'Salud y bienestar' => 'heart',
            'Hoteles y renta de inmuebles' => 'building-office-2',
            'Otros' => 'tag',
        ];

        foreach (array_keys($categories) as $position => $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $categories[$name], 'position' => $position, 'is_active' => true],
            );
        }
    }
}
