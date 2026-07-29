<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Storefronts\Models\Product;

/**
 * Reemplaza las variantes de un producto (1.4 del TODO), compartido por
 * CreateProduct y UpdateProduct. Sin diffing: borra y recrea, consistente
 * con la simplicidad del resto de las acciones de este módulo.
 */
trait SyncsProductVariants
{
    /**
     * @param  array<int, array{label: string, price?: string|float|null}>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $product->variants()->delete();

        foreach (array_values($variants) as $position => $variant) {
            $product->variants()->create([
                'label' => $variant['label'],
                'price' => $variant['price'] ?? null,
                'position' => $position,
            ]);
        }
    }
}
