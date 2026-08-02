@php
    $status = $payment?->status;
@endphp

<x-layouts::public :title="__('Resultado del pago')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        @if ($status === \App\Domain\Billing\Models\Payment::APROBADO)
            <flux:icon.check-circle class="mx-auto mb-4 size-12 text-emerald-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('¡Pago aprobado!') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Ya activamos tu plan o producto. Puedes cerrar esta pantalla.') }}</flux:subheading>
        @elseif ($status === \App\Domain\Billing\Models\Payment::RECHAZADO)
            <flux:icon.x-circle class="mx-auto mb-4 size-12 text-red-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El pago no fue aprobado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Puedes intentar de nuevo con otro medio de pago desde el panel de tu negocio.') }}</flux:subheading>
        @elseif ($payment)
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Tu pago está en proceso') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te avisaremos apenas se confirme.') }}</flux:subheading>
        @else
            <flux:heading size="xl" class="mb-2">{{ __('No pudimos confirmar el pago') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Si el cobro sí se realizó, contáctanos por soporte.') }}</flux:subheading>
        @endif

        <flux:button variant="primary" :href="route('emprendedores.home')" wire:navigate>{{ __('Volver a mi panel') }}</flux:button>
    </div>
</x-layouts::public>
