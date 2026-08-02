<?php

use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Actions\SubmitRecommendation;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Mis pedidos" (3.2 del TODO: pedido confirmado): el lado del comprador
 * para confirmar, completar, reportar un problema o cancelar una
 * constancia de pedido, y para dejar una recomendación (3.3 del TODO) una
 * vez completado. El lado del negocio vive en "Oportunidades".
 */
new #[Title('Mis pedidos')] class extends Component {
    public ?int $recommendingOrderId = null;

    public string $recommendation_body = '';

    /** @var array<int, string> */
    public array $recommendation_tags = [];

    #[Computed]
    public function orders()
    {
        return Auth::user()->customerOrderConfirmations()
            ->with(['business', 'recommendation'])
            ->get();
    }

    private function ownedOrder(int $orderId): OrderConfirmation
    {
        $order = OrderConfirmation::findOrFail($orderId);

        abort_unless($order->customer_user_id === Auth::id(), 403);

        return $order;
    }

    public function confirm(int $orderId): void
    {
        app(ConfirmOrder::class)->confirmAsCustomer($this->ownedOrder($orderId), Auth::user());

        unset($this->orders);
        Flux::toast(variant: 'success', text: __('Pedido confirmado.'));
    }

    public function complete(int $orderId): void
    {
        app(ConfirmOrder::class)->complete($this->ownedOrder($orderId), Auth::user());

        unset($this->orders);
        Flux::toast(variant: 'success', text: __('Pedido marcado como completado.'));
    }

    public function dispute(int $orderId, string $note = ''): void
    {
        app(ConfirmOrder::class)->markDisputed($this->ownedOrder($orderId), Auth::user(), $note ?: null);

        unset($this->orders);
        Flux::toast(text: __('Reportaste un problema con este pedido.'));
    }

    public function cancel(int $orderId): void
    {
        app(ConfirmOrder::class)->cancel($this->ownedOrder($orderId), Auth::user());

        unset($this->orders);
        Flux::toast(text: __('Pedido cancelado.'));
    }

    public function recommend(int $orderId): void
    {
        $this->recommendingOrderId = $orderId;
        $this->recommendation_body = '';
        $this->recommendation_tags = [];
    }

    public function cancelRecommend(): void
    {
        $this->reset(['recommendingOrderId', 'recommendation_body', 'recommendation_tags']);
    }

    public function submitRecommendation(): void
    {
        try {
            app(SubmitRecommendation::class)->handle(
                $this->ownedOrder($this->recommendingOrderId),
                Auth::user(),
                $this->recommendation_body,
                $this->recommendation_tags,
            );

            $this->cancelRecommend();
            unset($this->orders);
            Flux::toast(variant: 'success', text: __('¡Gracias! Tu recomendación quedó enviada para revisión.'));
        } catch (\InvalidArgumentException $e) {
            $this->addError('recommendation_body', $e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('recommendation_body', $e->validator->errors()->first());
        }
    }
}; ?>

<section class="mx-auto w-full max-w-3xl px-6 py-8">
    <flux:heading size="xl" class="mb-1">{{ __('Mis pedidos') }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
        {{ __('Constancias de pedidos con negocios de Merkamigo. Esto no reemplaza el pago ni la entrega — es solo un registro de que ambas partes están de acuerdo.') }}
    </flux:text>

    @if ($this->orders->isEmpty())
        <x-states.empty
            :title="__('Todavía no tienes pedidos')"
            :description="__('Cuando cierres una solicitud de «Pídelo» con «encontré lo que buscaba», o un negocio registre un pedido directo contigo, aparecerá aquí.')"
        />
    @else
        <div class="space-y-4">
            @foreach ($this->orders as $order)
                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="base">{{ $order->business->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $order->summary }}</flux:text>
                        </div>
                        <flux:badge size="sm" :color="match ($order->status) {
                            \App\Domain\Trust\Models\OrderConfirmation::COMPLETADO => 'green',
                            \App\Domain\Trust\Models\OrderConfirmation::CONFIRMADO => 'blue',
                            \App\Domain\Trust\Models\OrderConfirmation::EN_DISPUTA => 'red',
                            \App\Domain\Trust\Models\OrderConfirmation::CANCELADO => 'zinc',
                            default => 'amber',
                        }">
                            {{ match ($order->status) {
                                \App\Domain\Trust\Models\OrderConfirmation::COMPLETADO => __('Completado'),
                                \App\Domain\Trust\Models\OrderConfirmation::CONFIRMADO => __('Confirmado por ambos'),
                                \App\Domain\Trust\Models\OrderConfirmation::EN_DISPUTA => __('En disputa'),
                                \App\Domain\Trust\Models\OrderConfirmation::CANCELADO => __('Cancelado'),
                                default => __('Pendiente de tu confirmación'),
                            } }}
                        </flux:badge>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (! $order->customer_confirmed_at)
                            <flux:button size="sm" variant="primary" wire:click="confirm({{ $order->id }})">
                                {{ __('Confirmar') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="cancel({{ $order->id }})" wire:confirm="{{ __('¿Cancelar este pedido?') }}">
                                {{ __('Cancelar') }}
                            </flux:button>
                        @elseif ($order->status === \App\Domain\Trust\Models\OrderConfirmation::CONFIRMADO)
                            <flux:button size="sm" variant="primary" wire:click="complete({{ $order->id }})">
                                {{ __('Marcar como completado') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="dispute({{ $order->id }})" wire:confirm="{{ __('¿Reportar un problema con este pedido?') }}">
                                {{ __('Reportar un problema') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="cancel({{ $order->id }})" wire:confirm="{{ __('¿Cancelar este pedido?') }}">
                                {{ __('Cancelar') }}
                            </flux:button>
                        @endif
                    </div>

                    @if ($order->status === \App\Domain\Trust\Models\OrderConfirmation::COMPLETADO)
                        @if ($order->recommendation)
                            <flux:text class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Ya enviaste una recomendación para este pedido.') }}
                            </flux:text>
                        @elseif ($recommendingOrderId === $order->id)
                            <div class="mt-4 space-y-3 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-900 dark:bg-brand-950">
                                <flux:textarea wire:model="recommendation_body" :label="__('Cuéntale a otros cómo te fue')" rows="3" maxlength="500" required />

                                <flux:checkbox.group wire:model="recommendation_tags" :label="__('Etiquetas (opcional)')">
                                    @foreach (\App\Domain\Trust\Models\Recommendation::SUGGESTED_TAGS as $tag)
                                        <flux:checkbox value="{{ $tag }}" :label="$tag" />
                                    @endforeach
                                </flux:checkbox.group>

                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="cancelRecommend">{{ __('Cancelar') }}</flux:button>
                                    <flux:button size="sm" variant="primary" wire:click="submitRecommendation">{{ __('Enviar recomendación') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="mt-3">
                                <flux:button size="sm" variant="ghost" wire:click="recommend({{ $order->id }})">
                                    {{ __('Recomendar este negocio') }}
                                </flux:button>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
