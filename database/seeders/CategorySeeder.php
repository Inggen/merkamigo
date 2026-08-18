<?php

namespace Database\Seeders;

use App\Domain\Discovery\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Categorías del catálogo (pedido del usuario: reemplazar el listado
 * provisional de 0.1 por el definitivo de 19 categorías). El ícono es un
 * nombre de icono Heroicon (outline) de los que trae Flux en
 * vendor/livewire/flux/stubs/resources/views/flux/icon — también
 * editable desde el admin (Categorías > Editar).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos y bebidas' => 'cake',
            'Moda y accesorios' => 'shopping-bag',
            'Belleza y cuidado personal' => 'sparkles',
            'Salud y bienestar' => 'heart',
            'Hogar y decoración' => 'home',
            'Servicios para el hogar' => 'wrench-screwdriver',
            'Servicios profesionales' => 'briefcase',
            'Tecnología y electrónica' => 'device-phone-mobile',
            'Mascotas' => 'hand-raised',
            'Transporte y movilidad' => 'truck',
            'Educación y cursos' => 'academic-cap',
            'Eventos y entretenimiento' => 'ticket',
            'Artesanías y regalos' => 'gift',
            'Niños y bebés' => 'face-smile',
            'Deportes y actividad física' => 'trophy',
            'Campo y productos locales' => 'sun',
            'Inmuebles y arriendos' => 'building-office-2',
            'Turismo y hospedaje' => 'map',
            'Otros' => 'tag',
        ];

        $slugs = [];

        foreach (array_keys($categories) as $position => $name) {
            $slug = Str::slug($name);
            $slugs[] = $slug;

            Category::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $categories[$name], 'position' => $position, 'is_active' => true],
            );
        }

        // Pedido del usuario: "borrando las que no están en el listado" —
        // cualquier categoría que ya no aparece en el listado definitivo
        // (ej. la vieja "Hoteles y renta de inmuebles", dividida ahora en
        // "Inmuebles y arriendos" y "Turismo y hospedaje"). Los negocios,
        // solicitudes y slots que la referenciaban quedan con
        // `category_id` nulo (`nullOnDelete()` en las 3 tablas), nunca se
        // borran ellos.
        Category::query()->whereNotIn('slug', $slugs)->delete();
    }
}
