{{--
    PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): paso previo al widget de
    Wompi cuando quien compra no tiene sesión — solo aparece para
    productos digitales con el checkout de invitado activado
    (`OrderCheckoutController::guestCheckoutAvailable()`); para
    cualquier otro caso, `create()` ya mandó a /ingresar antes de llegar
    acá.
--}}
<x-layouts::public :title="__('Comprar :product', ['product' => $product->name])">
    <div class="mx-auto max-w-lg px-6 py-10">
        <flux:heading size="xl" class="mb-2">{{ $product->name }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Puedes pagarlo sin crear una cuenta. Solo necesitamos saber a quién enviarle la confirmación y la descarga.') }}
        </flux:subheading>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('marketplace.guest.checkout.create', $product) }}" class="space-y-6">
            @csrf

            <input type="hidden" name="cantidad" value="{{ $quantity }}">

            <flux:input name="guest_name" :label="__('Tu nombre')" :value="old('guest_name')" required />
            <flux:input name="guest_email" type="email" :label="__('Tu correo')" :value="old('guest_email')" required />
            <flux:input name="guest_phone" type="tel" :label="__('Tu teléfono (WhatsApp)')" :value="old('guest_phone')" required />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Continuar al pago') }}
            </flux:button>

            <flux:text class="text-center text-xs text-zinc-400">
                {{ __('¿Prefieres tener cuenta para ver tus compras después?') }}
                <flux:link :href="route('login')" wire:navigate>{{ __('Inicia sesión') }}</flux:link>
            </flux:text>
        </form>
    </div>
</x-layouts::public>
