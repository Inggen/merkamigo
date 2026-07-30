<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Actions\WithdrawOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
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
            ->where('municipality_id', $this->business->municipality_id)
            ->whereIn('status', [Need::PUBLICADA, Need::RECIBIENDO_OFERTAS])
            ->whereNull('suspended_at')
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
}; ?>

<section class="mx-auto w-full max-w-3xl px-6 py-8">
    <flux:heading size="xl" class="mb-1">{{ __('Oportunidades cercanas') }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
        {{ __('Solicitudes abiertas en tu municipio. Responde con una propuesta directa — nunca se envía nada sin que la confirmes.') }}
    </flux:text>

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
