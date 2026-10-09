{{--
    PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): equivalente de
    `marketplace/checkout-return.blade.php` para un pedido de invitado —
    sin enlace a "Mis compras" (no tiene cuenta) y con botón de
    descarga directo cuando el producto es digital y ya está pagado.
--}}
@php
    $status = $order?->status;
@endphp

<x-layouts::public :title="__('Tu pedido')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        @if ($status === \App\Domain\Marketplace\Models\Order::PAGADO)
            <flux:icon.check-circle class="mx-auto mb-4 size-12 text-emerald-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('¡Pago aprobado!') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Le avisamos a :business para que prepare tu pedido. También te enviamos esta confirmación por correo.', ['business' => $order->business->name]) }}</flux:subheading>

            @if ($downloadUrl)
                <flux:button variant="primary" :href="$downloadUrl" class="mb-3 w-full">
                    {{ __('Descargar') }}
                </flux:button>
            @endif
        @elseif ($status === \App\Domain\Marketplace\Models\Order::RECHAZADO)
            <flux:icon.x-circle class="mx-auto mb-4 size-12 text-red-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El pago no fue aprobado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Puedes intentar de nuevo con otro medio de pago.') }}</flux:subheading>
        @elseif ($status === \App\Domain\Marketplace\Models\Order::REEMBOLSADO)
            <flux:icon.arrow-uturn-left class="mx-auto mb-4 size-12 text-zinc-400" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Este pedido fue reembolsado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Si no lo esperabas, contáctanos por soporte.') }}</flux:subheading>
        @elseif ($order)
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Tu pago está en proceso') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te avisaremos por correo apenas se confirme.') }}</flux:subheading>
        @else
            <flux:heading size="xl" class="mb-2">{{ __('No pudimos confirmar el pago') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Si el cobro sí se realizó, contáctanos por soporte.') }}</flux:subheading>
        @endif

        <flux:button variant="ghost" :href="route('soporte')" class="w-full">{{ __('Contactar soporte') }}</flux:button>
    </div>
</x-layouts::public>
