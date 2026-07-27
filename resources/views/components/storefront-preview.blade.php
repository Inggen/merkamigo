@props(['business'])

@php
    $previewProducts = $business->products()->where('status', '!=', 'archivado')->take(6)->get();
@endphp

{{--
    Vista previa reutilizada por el paso 4 del wizard y por
    `/emprendedores/negocios/{business}/vista-previa` (1.2 y 1.6 del TODO).
    No es la vitrina pública final (esa vive en resources/views/vitrinas/show.blade.php).
--}}
<div {{ $attributes->class('overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900') }}>
    <div class="h-32 bg-zinc-100 dark:bg-zinc-800">
        @if ($business->storefront?->coverUrl())
            <img src="{{ $business->storefront->coverUrl() }}" class="h-full w-full object-cover" alt="">
        @endif
    </div>

    <div class="-mt-8 flex items-end gap-4 px-6">
        <div class="flex size-16 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-zinc-100 dark:border-zinc-900 dark:bg-zinc-800">
            @if ($business->logoUrl())
                <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="">
            @else
                <flux:icon.building-storefront class="size-6 text-zinc-400" variant="outline" />
            @endif
        </div>
    </div>

    <div class="space-y-3 p-6 pt-3">
        <flux:heading size="lg">{{ $business->name ?: __('Tu negocio') }}</flux:heading>

        <div class="flex flex-wrap gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            @if ($business->category)
                <flux:badge size="sm">{{ $business->category->name }}</flux:badge>
            @endif
            @if ($business->municipality)
                <flux:badge size="sm">{{ $business->municipality->name }}</flux:badge>
            @endif
        </div>

        @if ($business->storefront?->description)
            <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $business->storefront->description }}</flux:text>
        @endif

        @if ($business->whatsapp_number)
            <flux:button variant="primary" icon="chat-bubble-left-right" disabled>
                {{ __('Contactar por WhatsApp') }}
            </flux:button>
        @endif

        @if ($previewProducts->isNotEmpty())
            <div>
                <flux:subheading class="mb-2">{{ __('Productos') }}</flux:subheading>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($previewProducts as $product)
                        <div class="rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                            <div class="truncate font-medium">{{ $product->name }}</div>
                            @if ($product->price_type === 'exacto' && $product->price)
                                <div class="text-zinc-500">${{ number_format((float) $product->price, 0, ',', '.') }}</div>
                            @elseif ($product->price_type === 'desde' && $product->price)
                                <div class="text-zinc-500">{{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}</div>
                            @elseif ($product->price_type === 'consultar')
                                <div class="text-zinc-500">{{ __('Consultar') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
