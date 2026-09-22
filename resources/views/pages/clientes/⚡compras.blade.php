<?php

use App\Domain\Marketplace\Models\Order;
use App\Domain\Subscriptions\Actions\CancelCustomerSubscription;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Mis compras": pedidos pagados en línea vía Wompi (distinto de "Mis
 * pedidos" / OrderConfirmation, que es una constancia sin pago) — más
 * "Mis suscripciones" (Fase 8.2 del TODO social), mismo lugar natural
 * para el comprador en vez de una página aparte.
 */
new #[Title('Mis compras')] class extends Component {
    #[Computed]
    public function orders()
    {
        return Auth::user()->orders()->with(['business', 'product'])->get();
    }

    #[Computed]
    public function subscriptions()
    {
        return Auth::user()->customerSubscriptions()->with(['business', 'product'])->get();
    }

    public function cancel(int $subscriptionId): void
    {
        $subscription = Auth::user()->customerSubscriptions()->findOrFail($subscriptionId);

        app(CancelCustomerSubscription::class)->handle($subscription);

        unset($this->subscriptions);

        Flux::toast(variant: 'success', text: __('Suscripción cancelada. Tu acceso sigue activo hasta el final del periodo ya pagado.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl px-6 py-8">
    <flux:heading size="xl" class="mb-1">{{ __('Mis compras') }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
        {{ __('Productos que pagaste en línea directamente a cada negocio.') }}
    </flux:text>

    @if ($this->orders->isEmpty())
        <x-states.empty
            :title="__('Todavía no has comprado nada en línea')"
            :description="__('Cuando un negocio tenga pagos en línea activados, verás el botón «Comprar» en sus productos.')"
        />
    @else
        <div class="space-y-4">
            @foreach ($this->orders as $order)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div>
                        <flux:heading size="base">{{ $order->product->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $order->business->name }} · {{ $order->quantity }} × ${{ number_format($order->unit_price_cents / 100, 0, ',', '.') }}
                        </flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ $order->created_at->diffForHumans() }}</flux:text>
                    </div>

                    <div class="text-right">
                        <flux:text class="font-semibold text-zinc-900 dark:text-white">${{ number_format($order->amount_cents / 100, 0, ',', '.') }}</flux:text>
                        <flux:badge size="sm" :color="match ($order->status) {
                            Order::PAGADO => 'green',
                            Order::RECHAZADO => 'red',
                            Order::CANCELADO => 'zinc',
                            default => 'amber',
                        }">
                            {{ match ($order->status) {
                                Order::PAGADO => __('Pagado'),
                                Order::RECHAZADO => __('Rechazado'),
                                Order::CANCELADO => __('Cancelado'),
                                default => __('Pendiente'),
                            } }}
                        </flux:badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:heading size="xl" class="mt-10 mb-1">{{ __('Mis suscripciones') }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
        {{ __('Cobros periódicos directo a cada negocio.') }}
    </flux:text>

    @if ($this->subscriptions->isEmpty())
        <x-states.empty
            :title="__('Todavía no tienes suscripciones')"
            :description="__('Cuando un negocio venda un producto por suscripción, podrás suscribirte desde su página.')"
        />
    @else
        <div class="space-y-4">
            @foreach ($this->subscriptions as $subscription)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div>
                        <flux:heading size="base">{{ $subscription->product->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $subscription->business->name }}
                            @if ($subscription->current_period_ends_at)
                                · {{ __('Hasta :date', ['date' => $subscription->current_period_ends_at->format('d/m/Y')]) }}
                            @endif
                        </flux:text>
                        @if ($subscription->product->isDigital() && $subscription->isUsable())
                            <flux:link :href="route('products.download', $subscription->product)" class="text-sm">{{ __('Descargar') }}</flux:link>
                        @endif
                    </div>

                    <div class="text-right">
                        <flux:badge size="sm" :color="match ($subscription->status) {
                            CustomerSubscription::ACTIVA => 'green',
                            CustomerSubscription::PRUEBA => 'blue',
                            CustomerSubscription::CANCELADA => 'zinc',
                            CustomerSubscription::VENCIDA => 'red',
                            default => 'amber',
                        }">
                            {{ match ($subscription->status) {
                                CustomerSubscription::ACTIVA => __('Activa'),
                                CustomerSubscription::PRUEBA => __('Prueba'),
                                CustomerSubscription::CANCELADA => __('Cancelada'),
                                CustomerSubscription::VENCIDA => __('Vencida'),
                                default => __('Pausada'),
                            } }}
                        </flux:badge>

                        @if ($subscription->isUsable())
                            <div class="mt-2">
                                <flux:button size="sm" variant="ghost" wire:click="cancel({{ $subscription->id }})" wire:confirm="{{ __('¿Cancelar esta suscripción? Tu acceso sigue hasta el final del periodo ya pagado.') }}">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
