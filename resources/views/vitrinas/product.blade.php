<x-layouts::public :title="$product->name.' · '.$business->name">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ route('vitrinas.show', $business) }}" class="text-sm text-zinc-500 hover:text-brand-600" wire:navigate>
            ← {{ $business->name }}
        </a>

        <div class="mt-4 grid gap-8 sm:grid-cols-2">
            <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                @if ($product->media->isNotEmpty())
                    <img src="{{ $product->media->first()->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                @endif
            </div>

            <div class="space-y-4">
                <flux:heading size="xl">{{ $product->name }}</flux:heading>

                <div class="text-lg font-medium">
                    @if ($product->price_type === 'exacto' && $product->price)
                        ${{ number_format((float) $product->price, 0, ',', '.') }}
                    @elseif ($product->price_type === 'desde' && $product->price)
                        {{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}
                    @elseif ($product->price_type === 'consultar')
                        {{ __('Consultar precio') }}
                    @endif

                    @if ($product->unit)
                        <span class="text-sm font-normal text-zinc-500"> / {{ $product->unit }}</span>
                    @endif
                </div>

                @if (! $product->is_available)
                    <flux:badge color="zinc">{{ __('No disponible') }}</flux:badge>
                @endif

                @if ($product->description)
                    <flux:text class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $product->description }}</flux:text>
                @endif

                @if ($business->whatsapp_number)
                    <flux:button
                        href="https://wa.me/{{ preg_replace('/\D/', '', $business->whatsapp_number) }}?text={{ urlencode(__('Hola, me interesa ":product" que vi en Merkamigo.', ['product' => $product->name])) }}"
                        target="_blank"
                        variant="primary"
                        icon="chat-bubble-left-right"
                        class="w-full"
                    >
                        {{ __('Preguntar por WhatsApp') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</x-layouts::public>
