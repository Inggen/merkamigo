<x-layouts::cliente :title="$product->name.' · '.$business->name">
    <div class="mx-auto max-w-4xl px-6 py-6">
        <nav class="mb-4 flex flex-wrap items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600" wire:navigate>{{ __('Inicio') }}</a>
            <span>/</span>
            <a href="{{ route('vitrinas.show', $business) }}" class="hover:text-brand-600" wire:navigate>{{ $business->name }}</a>
            <span>/</span>
            <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $product->name }}</span>
        </nav>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-8 sm:grid-cols-2">
                <div>
                    <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->media->isNotEmpty())
                            <img src="{{ $product->media->first()->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                        @endif
                    </div>

                    @if ($product->media->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach ($product->media->skip(1)->take(4) as $media)
                                <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <img src="{{ $media->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:heading size="xl">{{ $product->name }}</flux:heading>
                        <a href="{{ route('vitrinas.show', $business) }}" class="text-sm text-zinc-500 hover:text-brand-600" wire:navigate>
                            {{ $business->name }}
                        </a>
                    </div>

                    <div class="text-lg font-medium">
                        @if ($product->hasActivePromo())
                            <span class="text-zinc-400 line-through">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                            <span class="text-red-600 dark:text-red-400">${{ number_format((float) $product->promo_price, 0, ',', '.') }}</span>
                            @if ($product->promo_label)
                                <flux:badge size="sm" color="red">{{ $product->promo_label }}</flux:badge>
                            @endif
                        @elseif ($product->price_type === 'exacto' && $product->price)
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

                    <flux:badge :color="$product->isSoldOut() ? 'red' : 'green'">
                        {{ $product->isSoldOut() ? __('Agotado') : __('Disponible') }}
                    </flux:badge>

                    @if ($product->variants->isNotEmpty())
                        <div class="space-y-1 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            @foreach ($product->variants as $variant)
                                <div class="flex items-center justify-between">
                                    <span>{{ $variant->label }}</span>
                                    <span class="font-medium">
                                        @if ($variant->price)
                                            ${{ number_format((float) $variant->price, 0, ',', '.') }}
                                        @else
                                            ${{ number_format((float) $product->price, 0, ',', '.') }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($product->description)
                        <flux:text class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $product->description }}</flux:text>
                    @endif

                    <div x-data class="flex gap-2">
                        <livewire:favorite-button :favoritable="$product" :key="'product-'.$product->id" />

                        <flux:button
                            type="button"
                            x-on:click="navigator.clipboard.writeText(window.location.href); fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado') }}')"
                            variant="ghost"
                            icon="share"
                        >
                            {{ __('Compartir') }}
                        </flux:button>
                    </div>

                    @if ($business->whatsapp_number)
                        <flux:button
                            href="{{ route('vitrinas.whatsapp.product', [$business, $product]) }}"
                            target="_blank"
                            variant="primary"
                            icon="chat-bubble-left-right"
                            class="w-full"
                        >
                            {{ $product->isSoldOut() ? __('Consultar disponibilidad') : __('Preguntar por WhatsApp') }}
                        </flux:button>
                    @endif

                    <a href="{{ route('reportes.crear.producto', [$business, $product]) }}" class="block text-sm text-zinc-400 hover:text-zinc-600" wire:navigate>
                        {{ __('Reportar este producto') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts::cliente>
