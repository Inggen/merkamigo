<?php

namespace Database\Seeders;

use App\Domain\Businesses\Models\BusinessAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Etiquetas iniciales provisionales para "atributos administrables" (1.3
 * del TODO); se ajustarán con datos reales del piloto.
 */
class BusinessAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'Producto artesanal' => 'heart',
            'Hecho en la región' => 'map-pin',
            'Ingredientes frescos' => 'sparkles',
            'Atención cercana' => 'users',
            'Domicilios disponibles' => 'truck',
            'Acepta pagos digitales' => 'credit-card',
        ];

        foreach ($attributes as $name => $icon) {
            BusinessAttribute::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'is_active' => true],
            );
        }
    }
}
