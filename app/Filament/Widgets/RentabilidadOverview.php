<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Marketplace\Models\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard de rentabilidad (PR5 de TODO_VENTAS_RENTABILIDAD.md, P1.4,
 * hallazgo #2 de la auditoría — docs/auditoria-ventas-rentabilidad.md
 * §1.4: antes ningún superadmin podía ver desde /admin el GMV, el MRR
 * ni el estado de las comisiones).
 *
 * GMV ≠ ingresos de Merkamigo (el pago del cliente va directo a la
 * cuenta Wompi del negocio) — por eso se muestran por separado, nunca
 * sumados entre sí: GMV es la venta total facilitada; la comisión
 * cobrada es lo único que Merkamigo realmente recibió de esas ventas;
 * el MRR es aparte, son suscripciones recurrentes (planes + add-ons
 * tipo entitlement), nunca pagos únicos ni la comisión.
 *
 * Deliberadamente NO incluye CAC, ARPA, churn ni margen de
 * contribución — esas métricas necesitan costos (pauta, infraestructura,
 * soporte) que hoy no se capturan en ningún lugar del sistema; mostrar
 * un número ahí sería inventar un dato, no calcularlo (regla 0 del TODO:
 * "no inventar métricas").
 */
class RentabilidadOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Ventas y rentabilidad';

    protected ?string $description = 'GMV, comisión y MRR reales — no incluye costos (pauta, infraestructura, soporte) porque todavía no se capturan en ningún lugar.';

    protected int|array|null $columns = ['default' => 2, 'md' => 3, 'xl' => 4];

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }

    protected function getStats(): array
    {
        $gmvCents = Order::query()->where('status', Order::PAGADO)->sum('amount_cents');
        $paidOrders = Order::query()->where('status', Order::PAGADO)->count();

        $commissionCollectedCents = CommissionCharge::query()->where('status', CommissionCharge::PAGADA)->sum('commission_cents');
        $commissionPendingCents = CommissionCharge::query()
            ->whereIn('status', [CommissionCharge::ABIERTA, CommissionCharge::PENDIENTE_COBRO])
            ->sum('commission_cents');
        $commissionNeedsAttention = CommissionCharge::query()
            ->where('status', CommissionCharge::FALLIDA)
            ->whereNull('next_retry_at')
            ->count();

        $planMrrCents = Business::query()
            ->where('status', 'publicado')
            ->with('subscription.plan')
            ->get()
            ->sum(function (Business $business) {
                $subscription = $business->subscription;

                if (! $subscription || ! $subscription->isUsable() || $subscription->status === Subscription::PRUEBA) {
                    return 0;
                }

                return $subscription->plan->isFree() ? 0 : $subscription->plan->price_cents;
            });

        $addonMrrCents = BusinessEntitlement::query()
            ->whereIn('status', [BusinessEntitlement::ACTIVA, BusinessEntitlement::EN_GRACIA])
            ->whereNotNull('expires_at')
            ->with('sourceBillingProduct')
            ->get()
            ->sum(fn (BusinessEntitlement $entitlement) => $entitlement->sourceBillingProduct?->price_cents ?? 0);

        $businessesWithWompi = Business::query()
            ->whereHas('wompiCredential', fn ($query) => $query->where('is_active', true))
            ->count();

        $checkoutsStarted30d = AnalyticsEvent::query()
            ->where('type', AnalyticsEvent::MARKETPLACE_CHECKOUT_STARTED)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $paymentsApproved30d = AnalyticsEvent::query()
            ->where('type', AnalyticsEvent::MARKETPLACE_PAYMENT_APPROVED)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $checkoutConversionRate = $checkoutsStarted30d > 0
            ? round(($paymentsApproved30d / $checkoutsStarted30d) * 100, 1)
            : null;

        return [
            Stat::make('GMV histórico', $this->money($gmvCents))
                ->description("{$paidOrders} pedidos pagados")
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('primary'),
            Stat::make('Comisión cobrada', $this->money($commissionCollectedCents))
                ->description('Lo único que Merkamigo recibió de esas ventas')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Comisión pendiente', $this->money($commissionPendingCents))
                ->description('Abierta o en proceso de cobro')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Comisión necesita atención', $commissionNeedsAttention)
                ->description('Fallida, agotó los reintentos automáticos')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($commissionNeedsAttention > 0 ? 'danger' : 'success'),
            Stat::make('MRR planes', $this->money($planMrrCents))
                ->description('Solo suscripciones de plan activas, sin prueba')
                ->icon(Heroicon::OutlinedCreditCard)
                ->color('info'),
            Stat::make('MRR add-ons', $this->money($addonMrrCents))
                ->description('Suscripciones recurrentes tipo entitlement (ej. asistente IA)')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('info'),
            Stat::make('Negocios con Wompi conectado', $businessesWithWompi)
                ->description('Pueden cobrar en línea sus propias ventas')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('primary'),
            Stat::make('Conversión de checkout (30 días)', $checkoutConversionRate !== null ? "{$checkoutConversionRate}%" : '—')
                ->description("{$checkoutsStarted30d} iniciados, {$paymentsApproved30d} aprobados")
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
        ];
    }

    /**
     * `sum()` de Eloquent puede devolver `int|float|string` según el
     * driver de BD — nunca `null` para una columna no nulable, pero el
     * tipo de retorno de la query builder es genérico.
     */
    private function money(int|float|string $cents): string
    {
        return '$'.number_format(((float) $cents) / 100, 0, ',', '.').' COP';
    }
}
