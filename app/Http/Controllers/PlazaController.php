<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Plaza de mi municipio y buscador (1.5 del TODO). "Ofertas locales",
 * "destacados" y "recomendados" quedan fuera de este pase — necesitan
 * `promotions`/marcas de destacado que todavía no existen (ver
 * docs/architecture/decisiones.md).
 */
class PlazaController extends Controller
{
    public function show(Municipality $municipio): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $businesses = Business::query()
            ->where('municipality_id', $municipio->id)
            ->where('status', 'publicado')
            ->with(['category', 'storefront'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('plaza.show', [
            'municipio' => $municipio,
            'categories' => $categories,
            'businesses' => $businesses,
            'category' => null,
        ]);
    }

    public function category(Municipality $municipio, Category $categoria): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $businesses = Business::query()
            ->where('municipality_id', $municipio->id)
            ->where('category_id', $categoria->id)
            ->where('status', 'publicado')
            ->with(['category', 'storefront'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('plaza.show', [
            'municipio' => $municipio,
            'categories' => $categories,
            'businesses' => $businesses,
            'category' => $categoria,
        ]);
    }

    public function buscar(Request $request): View
    {
        $query = trim((string) $request->string('q'));
        $municipalityId = $request->integer('municipio') ?: null;
        $categoryId = $request->integer('categoria') ?: null;

        $businesses = Business::query()
            ->where('status', 'publicado')
            ->when(
                $query !== '',
                fn ($q) => $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas('products', fn ($p) => $p
                            ->where('status', 'publicado')
                            ->where('name', 'like', "%{$query}%"));
                }),
            )
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'municipality', 'storefront'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('plaza.buscar', [
            'query' => $query,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'businesses' => $businesses,
        ]);
    }

    public function municipios(): View
    {
        return view('public.municipios', [
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function categorias(): View
    {
        return view('public.categorias', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
