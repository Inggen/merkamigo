@php
    $status = $order?->status;
@endphp

<x-layouts::public :title="__('Resultado del pago')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        @if ($status === \App\Domain\Marketplace\Models\Order::PAGADO)
            <flux:icon.check-circle class="mx-auto mb-4 size-12 text-emerald-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('¡Pago aprobado!') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Le avisamos a :business para que prepare tu pedido.', ['business' => $order->business->name]) }}</flux:subheading>
        @elseif ($status === \App\Domain\Marketplace\Models\Order::RECHAZADO)
            <flux:icon.x-circle class="mx-auto mb-4 size-12 text-red-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('El pago no fue aprobado') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Puedes intentar de nuevo con otro medio de pago.') }}</flux:subheading>
        @elseif ($order)
            <flux:icon.clock class="mx-auto mb-4 size-12 text-amber-500" variant="outline" />
            <flux:heading size="xl" class="mb-2">{{ __('Tu pago está en proceso') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Te avisaremos apenas se confirme.') }}</flux:subheading>
        @else
            <flux:heading size="xl" class="mb-2">{{ __('No pudimos confirmar el pago') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Si el cobro sí se realizó, contáctanos por soporte.') }}</flux:subheading>
        @endif

        <flux:button variant="primary" :href="route('clientes.compras')" wire:navigate>{{ __('Ver mis compras') }}</flux:button>
    </div>
</x-layouts::public>
