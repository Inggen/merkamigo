@php
    use App\Domain\Events\Models\EventReservation;

    $status = $reservation->status;
@endphp

{{--
    Página de retorno del pago (Fase 4). A diferencia de
    `marketplace/checkout-return.blade.php`, esta URL puede llegar a
    abrirse por alguien sin sesión (reserva de invitado) — por eso no
    muestra nombre, correo ni teléfono del prospecto, solo el estado del
    pago y el negocio.
--}}
<x-layouts::public :title="__('Resultado del pago')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        @if ($status === EventReservation::CONFIRMADA)
            <flux:icon.check-circle class="mx-auto mb-4 size-12 text-emerald-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('¡Reserva confirmada!') }}</flux:heading>
            <flux:subheading class="mb-6">
                {{ __('Te enviamos los detalles por correo. :business ya fue avisado.', ['business' => $reservation->business->name]) }}
            </flux:subheading>
        @elseif ($status === EventReservation::PAGO_FALLIDO)
            <flux:icon.x-circle class="mx-auto mb-4 size-12 text-red-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El pago no fue aprobado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Puedes volver a intentar con otro medio de pago mientras tu franja siga disponible.') }}</flux:subheading>
        @elseif ($status === EventReservation::VENCIDA)
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El tiempo para pagar esta reserva venció') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Vuelve a la vitrina del negocio para cotizar de nuevo.') }}</flux:subheading>
        @else
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Tu pago está en proceso') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te avisaremos apenas se confirme.') }}</flux:subheading>
        @endif

        @if ($reservation->business)
            <flux:button variant="primary" :href="route('vitrinas.show', $reservation->business)" wire:navigate>{{ __('Volver a la vitrina') }}</flux:button>
        @endif
    </div>
</x-layouts::public>
