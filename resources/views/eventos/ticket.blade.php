@php
    use App\Domain\Events\Models\EventAttendance;

    $event = $attendance->publicEvent;
@endphp

{{--
    "Mi entrada": lo que ve el asistente, con su QR de ingreso. Solo
    alcanzable por un enlace firmado (ver `EventAttendanceTicketController`)
    — nunca indexable ni adivinable.
--}}
<x-layouts::public :title="__('Tu entrada')">
    <div class="mx-auto max-w-md px-4 py-10">
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="bg-brand-600 p-5 text-center text-white">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">{{ __('Entrada') }}</p>
                <h1 class="text-xl font-bold">{{ $event->title }}</h1>
            </div>

            <div class="p-6 text-center">
                @if ($attendance->status === EventAttendance::CONFIRMADA)
                    <div class="mx-auto mb-4 inline-block rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                        <img src="{{ $qrUrl }}" alt="{{ __('Código QR de tu entrada') }}" class="size-48">
                    </div>
                    <p class="mb-1 text-sm text-zinc-500">{{ __('Muestra este código al llegar.') }}</p>

                    @if ($attendance->isCheckedIn())
                        <flux:badge size="sm" color="green" class="mt-3">{{ __('Ingreso registrado') }}</flux:badge>
                    @endif
                @elseif ($attendance->status === EventAttendance::PENDIENTE_PAGO)
                    <flux:icon.clock class="mx-auto mb-3 size-10 text-amber-500" variant="outline" />
                    <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Tu reserva todavía no está confirmada. Completa el pago para generar tu entrada.') }}</p>
                    <flux:button
                        type="button"
                        variant="primary"
                        class="w-full"
                        onclick="merkamigoOpenWompiCheckout('{{ route('eventos.entradas.checkout', $attendance) }}')"
                    >
                        {{ __('Continuar al pago') }}
                    </flux:button>
                @else
                    <flux:icon.x-circle class="mx-auto mb-3 size-10 text-red-500" variant="outline" />
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Esta entrada ya no está vigente (estado: :status).', ['status' => $attendance->status]) }}</p>
                @endif

                <div class="mt-6 space-y-1 text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <p class="flex items-center gap-1.5"><flux:icon.calendar-days class="size-4 text-brand-600" variant="outline" /> {{ $event->starts_at->translatedFormat('l j \d\e F \d\e Y, g:i a') }}</p>
                    @if ($event->location_text || $event->municipality)
                        <p class="flex items-center gap-1.5"><flux:icon.map-pin class="size-4 text-brand-600" variant="outline" /> {{ $event->location_text ?: $event->municipality?->name }}</p>
                    @endif
                    <p class="flex items-center gap-1.5"><flux:icon.user class="size-4 text-brand-600" variant="outline" /> {{ $attendance->attendee_name }} · {{ __(':count cupo(s)', ['count' => $attendance->quantity]) }}</p>
                </div>
            </div>
        </div>

        <flux:button :href="route('eventos.show', $event)" variant="ghost" class="mt-4 w-full" wire:navigate>{{ __('Ver el evento') }}</flux:button>
    </div>
</x-layouts::public>
