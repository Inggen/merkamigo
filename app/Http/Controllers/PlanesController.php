<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Plan;
use Illuminate\View\View;

/**
 * Página pública "Planes y precios": vitrina comercial de los planes de
 * suscripción y los servicios puntuales, ambos ya editables desde Filament
 * (`Plan`, `BillingProduct`) — esta página solo los presenta, nunca
 * codifica un precio o una función por su cuenta.
 */
class PlanesController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $billingProducts = BillingProduct::query()
            ->where('is_active', true)
            ->get();

        return view('public.planes-y-precios', [
            'plans' => $plans,
            'destacados' => $billingProducts->where('kind', BillingProduct::DESTACADO)
                ->sortBy(fn (BillingProduct $product) => $product->payload['days'] ?? 0)
                ->values(),
            'vitrinaAsistida' => $billingProducts->where('kind', BillingProduct::VITRINA_ASISTIDA)->first(),
            'kitArrancaBonito' => $billingProducts->where('kind', BillingProduct::KIT_ARRANCA_BONITO)->first(),
            // `ENTITLEMENT` es genérico por diseño (ver el modelo) — se
            // identifica este producto en particular por su slug, no
            // asumiendo que sea el único de su kind.
            'asistenteIa' => $billingProducts->firstWhere('slug', 'asistente-ia'),
        ]);
    }
}
