@php
    $business = $post->business;
@endphp

<article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    @if ($post->activePromotion)
        <div class="mb-3 flex items-center justify-between gap-3">
            <flux:badge color="red" icon="megaphone">{{ __('Patrocinado') }}</flux:badge>
            <flux:link :href="route('promotions.click', $post->activePromotion)" class="text-xs">{{ __('Ver destacado') }}</flux:link>
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="flex min-w-0 items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
                @if ($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}" loading="lazy">
                @else
                    <flux:icon.building-storefront class="size-5 text-zinc-400" variant="outline" />
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $business->name }}</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $business->municipality?->name }} · {{ $post->published_at?->diffForHumans() }}
                </p>
            </div>
        </a>

        <livewire:follow-button :business="$business" compact :key="'follow-'.$business->id.'-post-'.$post->id" />
    </div>

    @if ($post->body)
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ $post->body }}</p>
    @endif

    @if ($post->media->isNotEmpty())
        <div class="mt-3 grid gap-2 overflow-hidden rounded-xl {{ $post->media->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
            @foreach ($post->media as $media)
                @if ($media->isVideo())
                    <x-media.video-player :src="$media->url()" fit="cover" loop />
                @else
                    <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}" class="w-full object-cover {{ $post->media->count() > 1 ? 'aspect-square' : 'aspect-video' }}" loading="lazy">
                @endif
            @endforeach
        </div>
    @endif

    @if ($post->products->isNotEmpty())
        <div class="mt-3 space-y-2">
            @foreach ($post->products as $product)
                @php $photo = $product->media->first(); @endphp
                <div class="flex items-center gap-3 rounded-xl border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <a href="{{ route('vitrinas.product', [$business, $product]) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @if ($photo)
                                <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}" loading="lazy">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-950 dark:text-white">{{ $product->name }}</p>
                            @if ($product->price_type === 'exacto' && $product->price)
                                <p class="text-sm font-semibold text-brand-600 dark:text-brand-300">${{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            @elseif ($product->price_type === 'desde' && $product->price)
                                <p class="text-sm font-semibold text-brand-600 dark:text-brand-300">{{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </a>

                    @if ($business->hasWompiConnected() && ! $product->isSoldOut() && in_array($product->price_type, ['exacto', 'desde'], true) && $product->price)
                        <flux:button
                            size="sm"
                            variant="primary"
                            :href="route('marketplace.checkout.create', ['product' => $product, 'promotion' => $post->activePromotion?->id])"
                            wire:navigate
                            class="shrink-0"
                        >
                            {{ __('Comprar') }}
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="ghost" :href="route('vitrinas.product', [$business, $product])" wire:navigate class="shrink-0">
                            {{ __('Ver') }}
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-3 flex items-center gap-1 border-t border-zinc-100 pt-2 dark:border-zinc-800">
        <livewire:post-reaction-button :post="$post" :key="'reaction-'.$post->id" />

        @if ($business->whatsapp_number)
            <a
                href="{{ route('vitrinas.whatsapp', $business) }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
            >
                <flux:icon.chat-bubble-left-right class="size-4" variant="outline" />
                {{ __('WhatsApp') }}
            </a>
        @endif

        <button
            type="button"
            x-data
            x-on:click="navigator.share ? navigator.share({ title: {{ Js::from($business->name) }}, url: {{ Js::from(route('vitrinas.show', $business)) }} }) : $flux.toast({ text: {{ Js::from(__('Copia el enlace desde tu navegador para compartir.')) }} })"
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
        >
            <flux:icon.share class="size-4" variant="outline" />
            {{ __('Compartir') }}
        </button>

        <livewire:favorite-button :favoritable="$post" compact :key="'save-'.$post->id" />
    </div>

    <livewire:post-comments :post="$post" :key="'comments-'.$post->id" />
</article>
