<x-layouts::public :title="__('Redirigiendo al pago')">
    <div class="mx-auto max-w-md px-6 py-16 text-center">
        <flux:heading size="xl" class="mb-2">{{ __('Te estamos llevando a Wompi para pagarle a :business', ['business' => $business->name]) }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Este pago va directo a la cuenta de :business — Merkamigo no ve ni guarda los datos de tu tarjeta.', ['business' => $business->name]) }}
        </flux:subheading>

        <form id="wompi-checkout-form" method="GET" action="{{ $checkoutUrl }}">
            <input type="hidden" name="public-key" value="{{ $publicKey }}">
            <input type="hidden" name="currency" value="{{ $order->currency }}">
            <input type="hidden" name="amount-in-cents" value="{{ $order->amount_cents }}">
            <input type="hidden" name="reference" value="{{ $order->reference }}">
            <input type="hidden" name="signature:integrity" value="{{ $signature }}">
            <input type="hidden" name="redirect-url" value="{{ $redirectUrl }}">

            <flux:button type="submit" variant="primary">{{ __('Continuar al pago') }}</flux:button>
        </form>

        <script>
            document.getElementById('wompi-checkout-form').submit();
        </script>
    </div>
</x-layouts::public>
