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
            'Producto artesanal',
            'Hecho en la región',
            'Ingredientes frescos',
            'Atención cercana',
            'Domicilios disponibles',
            'Acepta pagos digitales',
        ];

        foreach ($attributes as $name) {
            BusinessAttribute::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
