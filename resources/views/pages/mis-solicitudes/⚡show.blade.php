<?php

use App\Domain\Needs\Actions\CancelNeed;
use App\Domain\Needs\Actions\CloseNeed;
use App\Domain\Needs\Actions\PreselectOffer;
use App\Domain\Needs\Actions\RegisterOfferViewed;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detalle de una solicitud propia (2.2/2.3 del TODO): comparar propuestas,
 * preseleccionar, cerrar con un resultado o cancelar.
 *
 * `#[Layout('layouts::cliente')]`: ver la nota equivalente en
 * `⚡nueva.blade.php` — el layout por defecto de una página Livewire es el
 * shell con sidebar del Emprendedor, y esta es una página del Cliente.
 */
new #[Layout('layouts::cliente')] #[Title('Mi solicitud')] class extends Component {
    public int $needId;

    public ?int $selectedOfferId = null;

    public function mount(Need $need): void
    {
        $this->authorize('view', $need);

        $this->needId = $need->id;

        $need->load('offers');

        foreach ($need->offers as $offer) {
            if ($offer->viewed_at === null) {
                app(RegisterOfferViewed::class)->handle($offer, request());
            }
        }

        unset($this->need);
    }

    #[Computed]
    public function need(): Need
    {
        return Need::with(['offers.business', 'offers.product', 'category', 'municipality'])->findOrFail($this->needId);
    }

    public function preselect(int $offerId): void
    {
        $offer = Offer::findOrFail($offerId);
        abort_unless($offer->need_id === $this->needId, 403);

        app(PreselectOffer::class)->handle($offer, Auth::user());
        unset($this->need);
    }

    public function selectForClosing(int $offerId): void
    {
        $this->selectedOfferId = $this->selectedOfferId === $offerId ? null : $offerId;
    }

    public function close(string $outcome): void
    {
        $this->authorize('update', $this->need);

        $offer = $this->selectedOfferId ? Offer::find($this->selectedOfferId) : null;

        app(CloseNeed::class)->handle($this->need, Auth::user(), $outcome, $offer);
        unset($this->need);

        Flux::toast(variant: 'success', text: __('Solicitud cerrada.'));
    }

    public function cancel(): void
    {
        $this->authorize('update', $this->need);

        app(CancelNeed::class)->handle($this->need, Auth::user());
        unset($this->need);

        Flux::toast(variant: 'success', text: __('Solicitud cancelada.'));
    }
}; ?>

<div class="mx-auto max-w-3xl px-6 py-8">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $this->need->title }}</flux:heading>
                <div class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                    <span>{{ $this->need->municipality->name }}</span>
                    @if ($this->need->zone)
                        <span>· {{ $this->need->zone }}</span>
                    @endif
                    @if ($this->need->category)
                        <span>· {{ $this->need->category->name }}</span>
                    @endif
                </div>
            </div>

            @if ($this->need->isOpenForOffers())
                <flux:button size="sm" variant="ghost" wire:click="cancel" wire:confirm="{{ __('¿Cancelar esta solicitud?') }}">
                    {{ __('Cancelar') }}
                </flux:button>
            @endif
        </div>

        <flux:text class="mb-6 text-zinc-600 dark:text-zinc-300">{{ $this->need->description }}</flux:text>

        @if ($this->need->media->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach ($this->need->media as $photo)
                    <img src="{{ $photo->url() }}" class="size-20 rounded-lg object-cover">
                @endforeach
            </div>
        @endif

        @if ($this->need->isClosed())
            <div class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text class="font-medium">
                    @if ($this->need->status === \App\Domain\Needs\Models\Need::VENCIDA)
                        {{ __('Esta solicitud venció.') }}
                    @else
                        {{ __('Esta solicitud está cerrada.') }}
                    @endif
                </flux:text>
            </div>
        @endif

        <flux:heading size="lg" class="mb-4">
            {{ trans_choice(':count propuesta recibida|:count propuestas recibidas', $this->need->offers->count(), ['count' => $this->need->offers->count()]) }}
        </flux:heading>

        @if ($this->need->offers->where(fn ($offer) => $offer->isActive())->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no has recibido propuestas') }}"
                description="{{ __('Los negocios cercanos verán tu solicitud y podrán responderte por acá.') }}"
            />
        @else
            <div class="space-y-4">
                @foreach ($this->need->offers->where(fn ($offer) => $offer->isActive()) as $offer)
                    <div class="rounded-2xl border p-5 {{ $selectedOfferId === $offer->id ? 'border-brand-500 bg-brand-50 dark:bg-brand-950' : 'border-zinc-200 dark:border-zinc-700' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="base">{{ $offer->business->name }}</flux:heading>
                                @if ($offer->product)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sobre: :producto', ['producto' => $offer->product->name]) }}</flux:text>
                                @endif
                            </div>

                            @if ($offer->status === \App\Domain\Needs\Models\Offer::PRESELECCIONADA)
                                <flux:badge color="amber">{{ __('Preseleccionada') }}</flux:badge>
                            @elseif ($offer->status === \App\Domain\Needs\Models\Offer::ACEPTADA)
                                <flux:badge color="green">{{ __('Aceptada') }}</flux:badge>
                            @endif
                        </div>

                        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $offer->message }}</flux:text>

                        <div class="mt-2 flex flex-wrap gap-x-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($offer->price !== null)
                                <span>{{ __('Precio: $:precio', ['precio' => number_format((float) $offer->price, 0, ',', '.')]) }}</span>
                            @endif
                            @if ($offer->availability)
                                <span>{{ $offer->availability }}</span>
                            @endif
                        </div>

                        @if ($this->need->isOpenForOffers())
                            <div class="mt-4 flex flex-wrap gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="preselect({{ $offer->id }})">
                                    {{ __('Preseleccionar') }}
                                </flux:button>
                                <flux:button size="sm" :variant="$selectedOfferId === $offer->id ? 'primary' : 'ghost'" wire:click="selectForClosing({{ $offer->id }})">
                                    {{ $selectedOfferId === $offer->id ? __('Elegida para cerrar') : __('Elegir para cerrar') }}
                                </flux:button>
                                <flux:button size="sm" variant="primary" :href="route('mis-solicitudes.whatsapp', [$this->need, $offer])" target="_blank">
                                    {{ __('Continuar por WhatsApp') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($this->need->isOpenForOffers())
                <div class="mt-8 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="base" class="mb-3">{{ __('¿Cómo te fue?') }}</flux:heading>
                    <flux:text class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                        @if ($selectedOfferId)
                            {{ __('Vas a cerrar esta solicitud con la propuesta elegida arriba.') }}
                        @else
                            {{ __('Puedes cerrar sin elegir una propuesta específica.') }}
                        @endif
                    </flux:text>

                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="close('{{ \App\Domain\Needs\Models\Need::OUTCOME_ENCONTRE }}')">
                            {{ __('Encontré lo que buscaba') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="close('{{ \App\Domain\Needs\Models\Need::OUTCOME_CONTACTE }}')">
                            {{ __('Ya contacté, sigo viendo') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="close('{{ \App\Domain\Needs\Models\Need::OUTCOME_NO_ENCONTRE }}')">
                            {{ __('No encontré lo que buscaba') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        @endif
</div>
