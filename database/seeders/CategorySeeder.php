<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Categorías iniciales provisionales (0.1 del TODO); se ajustarán con datos
 * reales del piloto (ver docs/product/alcance-fase0.md).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos y bebidas',
            'Moda y accesorios',
            'Hogar y decoración',
            'Belleza y cuidado personal',
            'Servicios profesionales',
            'Servicios para el hogar',
            'Salud y bienestar',
            'Otros',
        ];

        foreach ($categories as $name) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
