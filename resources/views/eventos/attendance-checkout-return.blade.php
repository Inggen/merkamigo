@php
    use App\Domain\Events\Models\EventAttendance;

    $status = $attendance->status;
@endphp

{{--
    Retorno del pago de una reserva de CUPO. Mismo criterio que
    `checkout-return.blade.php`: puede abrirla alguien sin sesión, así
    que no muestra nombre/correo/teléfono del asistente.
--}}
<x-layouts::public :title="__('Resultado del pago')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        @if ($status === EventAttendance::CONFIRMADA)
            <flux:icon.check-circle class="mx-auto mb-4 size-12 text-emerald-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('¡Cupo confirmado!') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te enviamos tu entrada por correo.') }}</flux:subheading>

            @if ($ticketUrl)
                <flux:button variant="primary" class="mb-3 w-full" :href="$ticketUrl">{{ __('Ver mi entrada') }}</flux:button>
            @endif
        @elseif ($status === EventAttendance::PAGO_FALLIDO)
            <flux:icon.x-circle class="mx-auto mb-4 size-12 text-red-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El pago no fue aprobado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Puedes volver a intentar con otro medio de pago mientras el cupo siga disponible.') }}</flux:subheading>
        @elseif ($status === EventAttendance::VENCIDA)
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El tiempo para pagar venció') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Vuelve a la ficha del evento para reservar de nuevo.') }}</flux:subheading>
        @else
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Tu pago está en proceso') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te avisaremos apenas se confirme.') }}</flux:subheading>
        @endif

        @if ($attendance->publicEvent)
            <flux:button variant="ghost" class="w-full" :href="route('eventos.show', $attendance->publicEvent)" wire:navigate>{{ __('Volver al evento') }}</flux:button>
        @endif
    </div>
</x-layouts::public>
