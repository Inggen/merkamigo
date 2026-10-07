<?php

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Actions\CreateEventReservation;
use App\Domain\Events\Actions\GetAvailableEventStartTimes;
use App\Domain\Events\Actions\QuoteEventReservation;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventDish;
use App\Domain\Events\Models\EventSetting;
use App\Domain\Events\Models\EventSpace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cotizador y reserva privada de evento (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 3). Página única con las 4 secciones numeradas del mockup de
 * referencia (`public/mockups/eventos/Reserva tu evento en Kebero.png`):
 * fecha/hora, duración, personas+platos, equipos — con un resumen que se
 * recalcula en vivo. El total mostrado aquí es solo vista previa; el
 * monto real que se cobra se recalcula en el servidor dentro de
 * `CreateEventReservation`, nunca se confía en lo que llega del cliente.
 *
 * Público, sin sesión obligatoria ("puedes explorar y reservar sin
 * registrarte" en el mockup de detalle de evento) — si hay sesión, se
 * usa el nombre/correo del cliente como valor inicial de los campos.
 */
new #[Layout('layouts::cliente')] #[Title('Reserva tu evento')] class extends Component
{
    public int $businessId;

    public ?int $spaceId = null;

    public string $selectedDate = '';

    public string $startTime = '';

    public int $durationHours = 1;

    public int $partySize = 1;

    /** @var array<int, int> */
    public array $dishQuantities = [];

    /** @var array<int, bool> */
    public array $equipmentSelected = [];

    public string $prospectName = '';

    public string $prospectEmail = '';

    public string $prospectPhone = '';

    public bool $termsAccepted = false;

    public ?int $createdReservationId = null;

    public function mount(Business $business): void
    {
        abort_unless($business->isPublished(), 404);

        $settings = $business->eventSetting;
        abort_unless($settings && $settings->enabled && $settings->private_reservations_enabled, 404);

        $this->businessId = $business->id;
        $this->spaceId = $business->eventSpaces()->where('is_active', true)->value('id');
        $this->selectedDate = now($settings->timezone)->addDay()->toDateString();
        $this->durationHours = 1;

        if (Auth::check()) {
            $this->prospectName = Auth::user()->name;
            $this->prospectEmail = Auth::user()->email;
        }

        // Fase 6: "medir... reservas atribuibles por vitrina y plan" —
        // se cuenta cada vez que alguien ABRE el cotizador, no solo al
        // confirmar, para poder ver la tasa de abandono real.
        app(RegisterAnalyticsEvent::class)->handle($business, AnalyticsEvent::EVENT_RESERVATION_STARTED, null, request());
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function settings(): ?EventSetting
    {
        return $this->business->eventSetting;
    }

    #[Computed]
    public function space(): ?EventSpace
    {
        return $this->spaceId ? $this->business->eventSpaces()->find($this->spaceId) : null;
    }

    #[Computed]
    public function dishes()
    {
        return $this->business->eventDishes()->where('is_available', true)->orderBy('position')->get();
    }

    #[Computed]
    public function equipmentOptions()
    {
        return $this->business->eventEquipment()->where('is_active', true)->with('equipmentType')->get();
    }

    #[Computed]
    public function availableStartTimes(): array
    {
        if (! $this->space || ! $this->settings || ! $this->selectedDate) {
            return [];
        }

        return app(GetAvailableEventStartTimes::class)->handle(
            $this->business, $this->space, $this->settings, $this->selectedDate, $this->durationHours,
        );
    }

    /**
     * @return array{space_total_cents: int, dishes_total_cents: int, equipment_total_cents: int, total_cents: int, lines: array<int, array{label: string, amount_cents: int}>}|null
     */
    #[Computed]
    public function quote(): ?array
    {
        if (! $this->settings) {
            return null;
        }

        try {
            return app(QuoteEventReservation::class)->handle(
                $this->settings,
                $this->durationHours,
                $this->dishSelectionsForQuote(),
                $this->equipmentSelectionsForQuote(),
            );
        } catch (EventActionException) {
            return null;
        }
    }

    public function updatedSelectedDate(): void
    {
        $this->startTime = '';
        unset($this->availableStartTimes);
    }

    public function updatedDurationHours(): void
    {
        $this->startTime = '';
        unset($this->availableStartTimes, $this->quote);
    }

    public function incrementDish(int $dishId): void
    {
        $this->dishQuantities[$dishId] = ($this->dishQuantities[$dishId] ?? 0) + 1;
        unset($this->quote);
    }

    public function decrementDish(int $dishId): void
    {
        $this->dishQuantities[$dishId] = max(0, ($this->dishQuantities[$dishId] ?? 0) - 1);
        unset($this->quote);
    }

    public function toggleEquipment(int $equipmentId): void
    {
        $this->equipmentSelected[$equipmentId] = ! ($this->equipmentSelected[$equipmentId] ?? false);
        unset($this->quote);
    }

    public function submit(): void
    {
        // Fase 7: "limitar intentos abusivos". A diferencia de una ruta
        // normal, esta acción llega por `/livewire/update` (compartida
        // por todos los componentes de la página), así que un
        // `throttle` de ruta la limitaría de más o de menos — se limita
        // aquí, por IP, igual de generoso que un formulario público real
        // pero suficiente para frenar un script.
        $throttleKey = 'event-reservation:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('submit', __('Demasiados intentos. Espera unos minutos y vuelve a intentar.'));

            return;
        }

        RateLimiter::increment($throttleKey, 600);

        $this->validate([
            'spaceId' => ['required', 'integer'],
            'selectedDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'durationHours' => ['required', 'integer', 'min:1'],
            'partySize' => ['required', 'integer', 'min:1'],
            'prospectName' => ['required', 'string', 'max:120'],
            'prospectEmail' => ['required', 'email', 'max:190'],
            'prospectPhone' => ['required', 'string', 'max:32'],
            'termsAccepted' => ['accepted'],
        ]);

        $startsAt = Carbon::parse("{$this->selectedDate} {$this->startTime}", $this->settings?->timezone);

        try {
            $reservation = app(CreateEventReservation::class)->handle(
                $this->business,
                $this->space,
                $startsAt,
                $this->durationHours,
                $this->partySize,
                $this->dishesPayload(),
                $this->equipmentPayload(),
                $this->prospectName,
                $this->prospectEmail,
                $this->prospectPhone,
                Auth::user(),
                (string) Str::uuid(),
            );
        } catch (EventActionException $e) {
            $this->addError('submit', $e->getMessage());
            unset($this->availableStartTimes);

            return;
        }

        $this->createdReservationId = $reservation->id;
    }

    /**
     * @return array<int, array{dish_id: int, quantity: int}>
     */
    private function dishesPayload(): array
    {
        return collect($this->dishQuantities)
            ->map(fn ($qty, $dishId) => ['dish_id' => (int) $dishId, 'quantity' => (int) $qty])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{equipment_id: int, quantity: int}>
     */
    private function equipmentPayload(): array
    {
        return collect($this->equipmentSelected)
            ->filter()
            ->map(fn ($selected, $equipmentId) => ['equipment_id' => (int) $equipmentId, 'quantity' => 1])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{dish: EventDish, quantity: int}>
     */
    private function dishSelectionsForQuote(): array
    {
        return $this->dishes
            ->map(fn ($dish) => ['dish' => $dish, 'quantity' => (int) ($this->dishQuantities[$dish->id] ?? 0)])
            ->all();
    }

    /**
     * @return array<int, array{equipment: EventBusinessEquipment, quantity: int}>
     */
    private function equipmentSelectionsForQuote(): array
    {
        return $this->equipmentOptions
            ->map(fn ($equipment) => ['equipment' => $equipment, 'quantity' => ($this->equipmentSelected[$equipment->id] ?? false) ? 1 : 0])
            ->all();
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <nav class="mb-4 flex flex-wrap items-center gap-1 text-sm text-zinc-500">
        <a href="{{ route('eventos') }}" wire:navigate class="hover:text-brand-700">{{ __('Eventos') }}</a>
        <span>/</span>
        <a href="{{ route('vitrinas.show', $this->business) }}" wire:navigate class="hover:text-brand-700">{{ $this->business->name }}</a>
    </nav>

    @if ($createdReservationId)
        <div class="mx-auto max-w-md rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-800 dark:bg-zinc-900">
            <flux:icon.clock class="mx-auto mb-3 size-10 text-amber-500" variant="outline" />
            <flux:heading size="lg" class="mb-2">{{ __('Confirma tu reserva con el pago') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Tenemos tu franja retenida por un tiempo limitado. Completa el pago para confirmarla.') }}</flux:subheading>

            <flux:button
                type="button"
                variant="primary"
                class="w-full"
                onclick="merkamigoOpenWompiCheckout('{{ route('eventos.reservas.checkout', $createdReservationId) }}')"
            >
                {{ __('Continuar al pago') }}
            </flux:button>

            <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                <flux:icon.lock-closed class="size-3.5" />
                {{ __('Pago seguro con Wompi') }}
            </p>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <flux:heading size="xl">{{ __('Reserva tu evento en :business', ['business' => $this->business->name]) }}</flux:heading>

                @error('submit')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-500/10 dark:text-red-300">
                        {{ $message }}
                    </div>
                @enderror

                @if (! $this->space)
                    <x-states.empty :title="__('Este negocio todavía no tiene un espacio configurado')" :description="__('Vuelve pronto.')" />
                @else
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <p class="mb-3 flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs text-white">1</span>
                            {{ __('Fecha y hora') }}
                        </p>

                        <flux:input type="date" wire:model.live="selectedDate" :min="now($this->settings?->timezone)->toDateString()" class="mb-3 max-w-xs" />

                        <div class="flex flex-wrap gap-2">
                            @forelse ($this->availableStartTimes as $time)
                                <button
                                    type="button"
                                    wire:click="$set('startTime', '{{ $time }}')"
                                    @class([
                                        'rounded-xl border px-3 py-1.5 text-sm font-medium transition',
                                        'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $startTime === $time,
                                        'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' => $startTime !== $time,
                                    ])
                                >
                                    {{ \Illuminate\Support\Carbon::parse($time)->format('g:i a') }}
                                </button>
                            @empty
                                <p class="text-sm text-zinc-500">{{ __('No hay horarios disponibles ese día. Prueba otra fecha.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <p class="mb-3 flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs text-white">2</span>
                            {{ __('Duración del evento') }}
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @for ($h = 1; $h <= ($this->settings?->max_duration_hours ?? 3); $h++)
                                <button
                                    type="button"
                                    wire:click="$set('durationHours', {{ $h }})"
                                    @class([
                                        'rounded-xl border px-4 py-1.5 text-sm font-medium transition',
                                        'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $durationHours === $h,
                                        'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' => $durationHours !== $h,
                                    ])
                                >
                                    {{ __(':hours hora(s)', ['hours' => $h]) }}
                                </button>
                            @endfor
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <p class="mb-3 flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs text-white">3</span>
                            {{ __('Personas y platos') }}
                        </p>

                        <div class="mb-4 flex items-center gap-3">
                            <flux:icon.users class="size-5 text-zinc-400" variant="outline" />
                            <flux:button type="button" size="sm" variant="ghost" wire:click="$set('partySize', {{ max(1, $partySize - 1) }})" aria-label="{{ __('Quitar una persona') }}">−</flux:button>
                            <span class="w-10 text-center font-semibold" aria-live="polite">{{ $partySize }}</span>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="$set('partySize', {{ $partySize + 1 }})" aria-label="{{ __('Agregar una persona') }}">+</flux:button>
                            <span class="text-sm text-zinc-500">{{ __('personas') }}</span>
                        </div>

                        @if ($this->dishes->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($this->dishes as $dish)
                                    <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">{{ $dish->name }}</p>
                                            <p class="text-xs text-zinc-500">${{ number_format($dish->price_cents / 100, 0, ',', '.') }} c/u</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="decrementDish({{ $dish->id }})" aria-label="{{ __('Quitar :name', ['name' => $dish->name]) }}">−</flux:button>
                                            <span class="w-6 text-center" aria-live="polite">{{ $dishQuantities[$dish->id] ?? 0 }}</span>
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="incrementDish({{ $dish->id }})" aria-label="{{ __('Agregar :name', ['name' => $dish->name]) }}">+</flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($this->equipmentOptions->isNotEmpty())
                        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                            <p class="mb-3 flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                                <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs text-white">4</span>
                                {{ __('Equipos (opcional)') }}
                            </p>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->equipmentOptions as $equipment)
                                    @php($selected = $equipmentSelected[$equipment->id] ?? false)
                                    <button
                                        type="button"
                                        wire:click="toggleEquipment({{ $equipment->id }})"
                                        @class([
                                            'flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition',
                                            'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $selected,
                                            'border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' => ! $selected,
                                        ])
                                    >
                                        {{ $equipment->displayName() }}
                                        @if ($equipment->fee_cents)
                                            <span class="text-xs text-zinc-400">(+${{ number_format($equipment->fee_cents / 100, 0, ',', '.') }})</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:heading size="lg" class="mb-3">{{ __('Tus datos') }}</flux:heading>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <flux:input wire:model="prospectName" :label="__('Nombre')" />
                            <flux:input type="tel" wire:model="prospectPhone" :label="__('Teléfono')" />
                        </div>
                        <flux:input type="email" wire:model="prospectEmail" :label="__('Correo')" class="mt-3" />
                        <flux:checkbox wire:model="termsAccepted" :label="__('Acepto las condiciones de reserva de este negocio')" class="mt-4" />
                        @if ($this->settings?->policy_text)
                            <p class="mt-2 text-xs text-zinc-500">{{ $this->settings->policy_text }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <div class="sticky top-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-3">{{ __('Resumen') }}</flux:heading>

                    @if ($this->quote && filled($this->quote['lines']))
                        <div class="mb-3 space-y-1.5 text-sm">
                            @foreach ($this->quote['lines'] as $line)
                                <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
                                    <span>{{ $line['label'] }}</span>
                                    <span>${{ number_format($line['amount_cents'] / 100, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between border-t border-zinc-200 pt-3 font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                            <span>{{ __('Total estimado') }}</span>
                            <span>${{ number_format($this->quote['total_cents'] / 100, 0, ',', '.') }}</span>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">{{ __('Elige duración y platos para ver el total.') }}</p>
                    @endif

                    <flux:button
                        type="button"
                        variant="primary"
                        class="mt-4 w-full"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        :disabled="! $this->space || ! $startTime"
                    >
                        {{ __('Continuar al pago') }}
                    </flux:button>

                    <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                        <flux:icon.lock-closed class="size-3.5" />
                        {{ __('Pago seguro con Wompi') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
