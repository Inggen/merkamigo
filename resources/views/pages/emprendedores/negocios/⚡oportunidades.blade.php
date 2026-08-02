<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Actions\WithdrawOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Actions\RequestDirectOrderConfirmation;
use App\Domain\Trust\Models\OrderConfirmation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Necesidades cercanas y propuestas enviadas (2.2 del TODO): un negocio ve
 * las solicitudes abiertas en su municipio y puede responder con una
 * propuesta directamente desde acá.
 */
new #[Title('Oportunidades cercanas')] class extends Component {
    #[Locked]
    public int $businessId;

    public ?int $composingNeedId = null;

    public string $message = '';

    public ?string $price = null;

    public ?string $availability = '';

    public ?int $product_id = null;

    public string $direct_order_customer_email = '';

    public string $direct_order_summary = '';

    /**
     * Ver `⚡copiloto.blade.php`: `boot()` es el único lugar donde fijar el
     * team de forma confiable en todo el ciclo de vida del componente.
     */
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

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function products()
    {
        return $this->business->products()->where('status', 'publicado')->get();
    }

    #[Computed]
    public function needs()
    {
        return Need::query()
            ->openIn($this->business->allMunicipalityIds()->all())
            ->with(['category'])
            ->latest('published_at')
            ->get()
            ->map(function (Need $need) {
                $need->setRelation('myOffer', $need->offers()->where('business_id', $this->businessId)->first());

                return $need;
            });
    }

    public function compose(int $needId): void
    {
        $existing = Offer::where('need_id', $needId)->where('business_id', $this->businessId)->first();

        $this->composingNeedId = $needId;
        $this->message = $existing?->message ?? '';
        $this->price = $existing?->price !== null ? (string) $existing->price : null;
        $this->availability = $existing?->availability ?? '';
        $this->product_id = $existing?->product_id;
    }

    public function cancelCompose(): void
    {
        $this->reset(['composingNeedId', 'message', 'price', 'availability', 'product_id']);
    }

    public function submit(): void
    {
        $need = Need::findOrFail($this->composingNeedId);

        try {
            app(SubmitOffer::class)->handle($this->business, $need, [
                'message' => $this->message,
                'price' => $this->price,
                'availability' => $this->availability,
                'product_id' => $this->product_id,
            ], Auth::user());

            Flux::toast(variant: 'success', text: __('Propuesta enviada.'));
            $this->cancelCompose();
            unset($this->needs);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        }
    }

    public function withdraw(int $offerId): void
    {
        $offer = Offer::findOrFail($offerId);

        abort_unless($offer->business_id === $this->businessId, 403);

        app(WithdrawOffer::class)->handle($offer, Auth::user());
        unset($this->needs);
    }

    /**
     * Pedidos confirmados por el comprador que esperan la confirmación del
     * negocio (2.3 del TODO): `ConfirmOrder::confirmAsBusiness()` ya existía
     * pero ningún panel lo invocaba todavía.
     */
    #[Computed]
    public function pendingOrders()
    {
        return OrderConfirmation::query()
            ->where('business_id', $this->businessId)
            ->where('status', OrderConfirmation::PENDIENTE)
            ->whereNull('business_confirmed_at')
            ->with('source.need')
            ->latest()
            ->get();
    }

    public function confirmOrder(int $orderId): void
    {
        $order = OrderConfirmation::findOrFail($orderId);

        abort_unless($order->business_id === $this->businessId, 403);

        app(ConfirmOrder::class)->confirmAsBusiness($order, Auth::user());

        unset($this->pendingOrders);
        Flux::toast(variant: 'success', text: __('Compra confirmada.'));
    }

    /**
     * Pedidos ya confirmados por ambas partes, esperando que alguien los
     * marque como completados, en disputa o cancelados (2.3/3.2 del TODO).
     */
    #[Computed]
    public function confirmedOrders()
    {
        return OrderConfirmation::query()
            ->where('business_id', $this->businessId)
            ->where('status', OrderConfirmation::CONFIRMADO)
            ->latest()
            ->get();
    }

    private function ownedOrder(int $orderId): OrderConfirmation
    {
        $order = OrderConfirmation::findOrFail($orderId);

        abort_unless($order->business_id === $this->businessId, 403);

        return $order;
    }

    public function completeOrder(int $orderId): void
    {
        app(ConfirmOrder::class)->complete($this->ownedOrder($orderId), Auth::user());

        unset($this->confirmedOrders);
        Flux::toast(variant: 'success', text: __('Pedido marcado como completado.'));
    }

    public function disputeOrder(int $orderId): void
    {
        app(ConfirmOrder::class)->markDisputed($this->ownedOrder($orderId), Auth::user());

        unset($this->confirmedOrders);
        Flux::toast(text: __('Reportaste un problema con este pedido.'));
    }

    public function cancelOrder(int $orderId): void
    {
        app(ConfirmOrder::class)->cancel($this->ownedOrder($orderId), Auth::user());

        unset($this->pendingOrders, $this->confirmedOrders);
        Flux::toast(text: __('Pedido cancelado.'));
    }

    /**
     * "Constancia desde un contacto" (3.2 del TODO): un pedido que no pasó
     * por "Pídelo", registrado con el correo real del comprador — si esa
     * cuenta no existe, se rechaza en vez de fabricar una confirmación.
     */
    public function registerDirectOrder(): void
    {
        $data = $this->validate([
            'direct_order_customer_email' => ['required', 'email'],
            'direct_order_summary' => ['required', 'string', 'max:255'],
        ]);

        try {
            app(RequestDirectOrderConfirmation::class)->handle(
                $this->business,
                Auth::user(),
                $data['direct_order_customer_email'],
                $data['direct_order_summary'],
            );

            $this->reset(['direct_order_customer_email', 'direct_order_summary']);
            unset($this->pendingOrders, $this->confirmedOrders);
            Flux::toast(variant: 'success', text: __('Pedido registrado. Le avisamos al comprador para que confirme su lado.'));
        } catch (\InvalidArgumentException $e) {
            $this->addError('direct_order_customer_email', $e->getMessage());
        }
    }
}; ?>

<section class="mx-auto w-full max-w-3xl px-6 py-8">
    <flux:heading size="xl" class="mb-1">{{ __('Oportunidades cercanas') }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
        {{ __('Solicitudes abiertas en tu municipio. Responde con una propuesta directa — nunca se envía nada sin que la confirmes.') }}
    </flux:text>

    @if ($this->pendingOrders->isNotEmpty())
        <div class="mb-8">
            <flux:heading size="lg" class="mb-3">{{ __('Pedidos por confirmar') }}</flux:heading>
            <div class="space-y-3">
                @foreach ($this->pendingOrders as $order)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-900 dark:bg-brand-950">
                        <div>
                            <flux:text class="font-medium">{{ $order->summary }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('El comprador ya confirmó su lado. Confirma el tuyo para cerrar el pedido.') }}
                            </flux:text>
                        </div>
                        <flux:button size="sm" variant="primary" wire:click="confirmOrder({{ $order->id }})">
                            {{ __('Confirmar esta compra') }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->confirmedOrders->isNotEmpty())
        <div class="mb-8">
            <flux:heading size="lg" class="mb-3">{{ __('Pedidos confirmados') }}</flux:heading>
            <div class="space-y-3">
                @foreach ($this->confirmedOrders as $order)
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="font-medium">{{ $order->summary }}</flux:text>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <flux:button size="sm" variant="primary" wire:click="completeOrder({{ $order->id }})">
                                {{ __('Marcar como completado') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="disputeOrder({{ $order->id }})" wire:confirm="{{ __('¿Reportar un problema con este pedido?') }}">
                                {{ __('Reportar un problema') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="cancelOrder({{ $order->id }})" wire:confirm="{{ __('¿Cancelar este pedido?') }}">
                                {{ __('Cancelar') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-8 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-1">{{ __('Registrar un pedido directo') }}</flux:heading>
        <flux:text class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('¿Un cliente te contactó fuera de "Pídelo" y quieres dejar constancia? Necesitas el correo de su cuenta de Merkamigo.') }}
        </flux:text>
        <form wire:submit="registerDirectOrder" class="space-y-3">
            <flux:input wire:model="direct_order_customer_email" type="email" :label="__('Correo del comprador')" required />
            <flux:input wire:model="direct_order_summary" :label="__('¿Qué le vendiste?')" placeholder="{{ __('Ej: 2 tortas de chocolate') }}" required />
            <flux:button type="submit" size="sm" variant="primary">{{ __('Registrar pedido') }}</flux:button>
        </form>
    </div>

    @if ($this->needs->isEmpty())
        <x-states.empty
            title="{{ __('Todavía no hay solicitudes abiertas') }}"
            description="{{ __('Cuando alguien pida algo en tu municipio, aparecerá aquí.') }}"
        />
    @else
        <div class="space-y-4">
            @foreach ($this->needs as $need)
                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="base">{{ $need->title }}</flux:heading>
                            <div class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($need->category)
                                    <span>{{ $need->category->name }}</span>
                                    <span>·</span>
                                @endif
                                @if ($need->zone)
                                    <span>{{ $need->zone }}</span>
                                    <span>·</span>
                                @endif
                                <span>{{ $need->published_at?->diffForHumans() }}</span>
                            </div>
                        </div>

                        @if ($need->myOffer && ! $need->myOffer->isWithdrawn())
                            <flux:badge color="green">{{ __('Ya respondiste') }}</flux:badge>
                        @endif
                    </div>

                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $need->description }}</flux:text>

                    @if ($need->budget)
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Presupuesto: $:budget', ['budget' => number_format((float) $need->budget, 0, ',', '.')]) }}
                        </flux:text>
                    @endif

                    @if ($composingNeedId === $need->id)
                        <div class="mt-4 space-y-3 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-900 dark:bg-brand-950">
                            <flux:textarea wire:model="message" :label="__('Tu propuesta')" rows="3" placeholder="{{ __('Cuéntale cómo puedes ayudarle...') }}" />
                            <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Precio (opcional)')" />
                            <flux:input wire:model="availability" :label="__('Disponibilidad (opcional)')" placeholder="{{ __('Ej: Disponible desde mañana') }}" />

                            @if ($this->products->isNotEmpty())
                                <flux:select wire:model="product_id" :label="__('Enlazar a un producto (opcional)')">
                                    <flux:select.option value="">{{ __('Ninguno') }}</flux:select.option>
                                    @foreach ($this->products as $product)
                                        <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif

                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="cancelCompose">{{ __('Cancelar') }}</flux:button>
                                <flux:button size="sm" variant="primary" wire:click="submit">{{ __('Enviar propuesta') }}</flux:button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 flex gap-2">
                            @if ($need->myOffer && ! $need->myOffer->isWithdrawn())
                                <flux:button size="sm" variant="ghost" wire:click="compose({{ $need->id }})">{{ __('Editar propuesta') }}</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="withdraw({{ $need->myOffer->id }})" wire:confirm="{{ __('¿Retirar tu propuesta?') }}">
                                    {{ __('Retirar propuesta') }}
                                </flux:button>
                            @else
                                <flux:button size="sm" variant="primary" wire:click="compose({{ $need->id }})">{{ __('Responder') }}</flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
