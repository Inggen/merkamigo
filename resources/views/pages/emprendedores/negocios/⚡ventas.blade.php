<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ChargeCommission;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Marketplace\Models\Order;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Ventas": pedidos pagados en línea + la comisión de Merkamigo sobre
 * ellos (cobrada aparte, contra la tarjeta ya guardada — el negocio
 * nunca ve esto mezclado con el dinero de la venta, que ya está en SU
 * cuenta).
 */
new #[Title('Ventas')] class extends Component
{
    #[Locked]
    public int $businessId;

    public function boot(): void
    {
        if (isset($this->businessId)) {
            setPermissionsTeamId($this->businessId);
            Auth::user()?->unsetRelation('roles');
        }
    }

    public function mount(Business $business): void
    {
        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');

        $this->authorize('update', $business);
        abort_unless($business->isOnPaidPlan() || (Auth::user()?->canBypassPlanGates() ?? false), 403);

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function orders()
    {
        return $this->business->orders()->with('product')->get();
    }

    /**
     * También incluye una comisión `fallida` (PR2 de
     * TODO_VENTAS_RENTABILIDAD.md, hallazgo #4): el negocio puede
     * reintentarla él mismo en vez de esperar al siguiente reintento
     * automático o a que alguien del equipo la vea en el panel interno.
     */
    #[Computed]
    public function openCharge(): ?CommissionCharge
    {
        return $this->business->orders()->exists()
            ? CommissionCharge::where('business_id', $this->businessId)
                ->whereIn('status', [CommissionCharge::ABIERTA, CommissionCharge::FALLIDA])
                ->first()
            : null;
    }

    public function chargeNow(): void
    {
        $this->authorize('update', $this->business);

        $charge = CommissionCharge::where('business_id', $this->businessId)
            ->whereIn('status', [CommissionCharge::ABIERTA, CommissionCharge::FALLIDA])
            ->first();

        if (! $charge) {
            return;
        }

        try {
            app(ChargeCommission::class)->handle($charge);
        } catch (InvalidArgumentException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->openCharge);

        Flux::toast(variant: 'success', text: __('Cobro de comisión enviado.'));
    }

    /**
     * Antes vivía como `match` directo en la plantilla — Pint no puede
     * ver el uso de `Order::*` dentro de la sección Blade de un SFC
     * (solo analiza el bloque de apertura PHP de arriba), así que ese
     * `use` terminaba marcado como "no usado" y en riesgo de que `pint`
     * (sin `--test`) lo borrara en un commit futuro. Moverlo acá
     * también es más fácil de probar.
     */
    public function orderStatusColor(Order $order): string
    {
        return match ($order->status) {
            Order::PAGADO => 'green',
            Order::RECHAZADO => 'red',
            Order::REEMBOLSADO => 'zinc',
            default => 'amber',
        };
    }

    public function orderStatusLabel(Order $order): string
    {
        return match ($order->status) {
            Order::PAGADO => __('Pagado'),
            Order::RECHAZADO => __('Rechazado'),
            Order::REEMBOLSADO => __('Reembolsado'),
            default => __('Pendiente'),
        };
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Ventas') }}</flux:heading>
        <flux:subheading>{{ __('Pedidos pagados en línea directo a tu cuenta Wompi.') }}</flux:subheading>
    </div>

    @if (! $this->business->hasWompiConnected())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950">
            <flux:text class="font-semibold text-amber-900 dark:text-amber-100">{{ __('Todavía no conectas Wompi') }}</flux:text>
            <flux:text class="mt-1 text-sm text-amber-700 dark:text-amber-300">{{ __('Conecta tu cuenta para que tus clientes puedan pagarte en línea.') }}</flux:text>
            <flux:button size="sm" class="mt-3" :href="route('emprendedores.negocios.cobros-en-linea', $this->business)" wire:navigate>
                {{ __('Conectar Wompi') }}
            </flux:button>
        </div>
    @endif

    @if ($this->openCharge && $this->openCharge->commission_cents > 0)
        <div @class([
            'rounded-2xl border p-5',
            'border-zinc-200 dark:border-zinc-800' => $this->openCharge->status !== CommissionCharge::FALLIDA,
            'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950' => $this->openCharge->status === CommissionCharge::FALLIDA,
        ])>
            <flux:text class="font-semibold">
                {{ $this->openCharge->status === CommissionCharge::FALLIDA ? __('El último cobro de comisión falló') : __('Comisión pendiente de cobro') }}
            </flux:text>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count venta|:count ventas', $this->openCharge->orders_count, ['count' => $this->openCharge->orders_count]) }}
                · {{ __('Comisión: $:amount', ['amount' => number_format($this->openCharge->commission_cents / 100, 0, ',', '.')]) }}
            </flux:text>
            @if ($this->openCharge->status === CommissionCharge::FALLIDA)
                <flux:text class="mt-1 text-xs text-red-700 dark:text-red-300">
                    @if ($this->openCharge->next_retry_at)
                        {{ __('Volveremos a intentarlo automáticamente el :date — o puedes reintentarlo ahora.', ['date' => $this->openCharge->next_retry_at->translatedFormat('d \d\e F')]) }}
                    @else
                        {{ __('Ya no se reintentará solo. Revisa tu tarjeta guardada y vuelve a intentarlo.') }}
                    @endif
                </flux:text>
            @else
                <flux:text class="mt-1 text-xs text-zinc-400">
                    {{ __('Se cobra automáticamente cada lunes — también puedes adelantarlo.') }}
                </flux:text>
            @endif
            <flux:button size="sm" class="mt-3" wire:click="chargeNow" wire:confirm="{{ __('¿Cobrar ya la comisión acumulada a tu tarjeta guardada?') }}">
                {{ __('Cobrar ahora') }}
            </flux:button>
        </div>
    @endif

    @if ($this->orders->isEmpty())
        <x-states.empty :title="__('Todavía no tienes ventas en línea')" :description="__('Cuando un cliente te compre con pago en línea, aparecerá aquí.')" />
    @else
        <div class="space-y-3">
            @foreach ($this->orders as $order)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div>
                        <flux:text class="font-semibold">{{ $order->product->name }}</flux:text>
                        <flux:text class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $order->created_at->diffForHumans() }}</flux:text>
                    </div>
                    <div class="text-right">
                        <flux:text class="font-semibold">${{ number_format($order->amount_cents / 100, 0, ',', '.') }}</flux:text>
                        <flux:badge size="sm" :color="$this->orderStatusColor($order)">
                            {{ $this->orderStatusLabel($order) }}
                        </flux:badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
