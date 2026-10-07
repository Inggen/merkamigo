@php
    $remaining = $this->event->spotsRemaining();
    $soldOut = $remaining !== null && $remaining <= 0;
@endphp

<div class="rounded-2xl border border-brand-200 bg-white p-5 shadow-sm dark:border-brand-500/30 dark:bg-zinc-900">
    <div class="mb-5 flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <flux:icon.calendar-days class="size-5" variant="outline" />
            </span>
            <div>
                <flux:heading size="lg">{{ __('Reserva tu cupo') }}</flux:heading>
                <p class="mt-0.5 text-xs text-zinc-500">
                    {{ $this->event->isFree() ? __('Entrada gratuita.') : __('$:price por persona', ['price' => number_format($this->event->price_cents / 100, 0, ',', '.')]) }}
                </p>
            </div>
        </div>

        @if ($remaining !== null)
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold {{ $soldOut ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' }}">
                <flux:icon.users class="size-3.5" variant="outline" />
                {{ $soldOut ? __('Cupo agotado') : __(':count disponibles', ['count' => $remaining]) }}
            </span>
        @endif
    </div>

    @if ($createdAttendanceId)
        @if ($isPaid)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-900 dark:bg-amber-500/10">
                <flux:icon.clock class="mx-auto mb-2 size-8 text-amber-500" variant="outline" />
                <p class="mb-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('Tenemos tu cupo retenido. Completa el pago para confirmarlo.') }}</p>
                <flux:button
                    type="button"
                    variant="primary"
                    class="w-full"
                    onclick="merkamigoOpenWompiCheckout('{{ route('eventos.entradas.checkout', $createdAttendanceId) }}')"
                >
                    {{ __('Continuar al pago') }}
                </flux:button>
            </div>
        @else
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-900 dark:bg-emerald-500/10">
                <flux:icon.check-circle class="mx-auto mb-2 size-8 text-emerald-500" variant="outline" />
                <p class="mb-3 text-sm text-zinc-700 dark:text-zinc-300">{{ __('¡Cupo reservado! Te enviamos tu entrada por correo.') }}</p>
                <flux:button variant="primary" class="w-full" :href="$ticketUrl">{{ __('Ver mi entrada') }}</flux:button>
            </div>
        @endif
    @elseif ($soldOut)
        <x-states.empty :title="__('Ya no hay cupo')" :description="__('Sigue a este negocio para enterarte de sus próximos eventos.')" />
    @else
        @error('submit')
            <div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-500/10 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="submit" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Cantidad de cupos') }}</flux:label>
                <div class="mt-1 flex items-center gap-2">
                    <flux:button type="button" size="sm" variant="filled" class="!size-10 !p-0" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})" aria-label="{{ __('Quitar un cupo') }}">−</flux:button>
                    <span class="w-12 text-center text-base font-semibold" aria-live="polite">{{ $quantity }}</span>
                    <flux:button type="button" size="sm" variant="filled" class="!size-10 !p-0" wire:click="$set('quantity', {{ $quantity + 1 }})" aria-label="{{ __('Agregar un cupo') }}">+</flux:button>
                </div>
            </flux:field>

            <flux:input wire:model="attendeeName" :label="__('Nombre completo')" icon="user" />
            <flux:input type="email" wire:model="attendeeEmail" :label="__('Correo electrónico')" icon="envelope" />
            <flux:input type="tel" wire:model="attendeePhone" :label="__('Teléfono')" icon="phone" />

            @if (! $this->event->isFree() && $quantity > 0)
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-sm font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                    <span>{{ __('Total') }}</span>
                    <span>${{ number_format(($this->event->price_cents * $quantity) / 100, 0, ',', '.') }}</span>
                </div>
            @endif

            <flux:button type="submit" variant="primary" class="w-full" icon="calendar-days" wire:loading.attr="disabled" wire:target="submit">
                {{ $this->event->isFree() ? __('Reservar cupo') : __('Continuar') }}
            </flux:button>

            <p class="text-center text-xs leading-5 text-zinc-400">
                {{ __('Tu información solo se usa para este evento.') }}<br>
                {{ __('Recibirás un mensaje de confirmación.') }}
            </p>

            @unless ($this->event->isFree())
                <p class="flex items-center justify-center gap-1.5 text-xs text-zinc-400">
                    <flux:icon.lock-closed class="size-3.5" />
                    {{ __('Pago seguro con Wompi') }}
                </p>
            @endunless
        </form>
    @endif
</div>
