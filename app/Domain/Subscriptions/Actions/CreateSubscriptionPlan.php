<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Validator;

/**
 * Marca un producto/servicio del negocio como venta por suscripción y
 * define su plan (Fase 8.1 del TODO social) — un plan por producto en
 * este alcance, `updateOrCreate` en vez de permitir varios niveles.
 */
class CreateSubscriptionPlan
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Product $product, array $data): SubscriptionPlan
    {
        $validated = Validator::make($data, [
            'frequency' => ['required', 'in:semanal,mensual,trimestral,anual'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'benefits' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        $product->update(['sale_type' => 'suscripcion']);

        return SubscriptionPlan::updateOrCreate(
            ['product_id' => $product->id],
            [
                'frequency' => $validated['frequency'],
                'trial_days' => $validated['trial_days'] ?? null,
                'benefits' => $validated['benefits'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ],
        );
    }
}
