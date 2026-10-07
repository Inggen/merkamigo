@php
    $business = $post->business;
    $ownerManagerRoute = $post->type === 'video'
        ? route('emprendedores.negocios.reels', $business).'#reel-'.$post->id
        : route('emprendedores.negocios.publicaciones', $business).'#publicacion-'.$post->id;
    $ownerManagerLabel = $post->type === 'video' ? __('Gestionar reel') : __('Gestionar publicación');
    [$contentLabel, $contentIcon] = match ($post->type) {
        'video' => [__('Reel'), 'play-circle'],
        'imagen' => [__('Publicación'), 'photo'],
        default => [__('Publicación'), 'newspaper'],
    };
@endphp

@if ($post->publicEvent)
    @include('feed.partials.event-post-card', ['post' => $post])
@else
<article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="grid items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
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

        <div data-post-badges class="flex flex-wrap items-center justify-start gap-2 sm:justify-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">
                <flux:icon :name="$contentIcon" class="size-4" variant="outline" />
                {{ $contentLabel }}
            </span>
            @if ($post->products->isNotEmpty())
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                    <flux:icon.shopping-bag class="size-4" />
                    {{ trans_choice(':count producto|:count productos', $post->products->count(), ['count' => $post->products->count()]) }}
                </span>
            @endif
            @if ($post->activePromotion)
                <a href="{{ route('promotions.click', $post->activePromotion) }}" class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                    <flux:icon.megaphone class="size-4" />
                    {{ __('Patrocinado') }}
                </a>
            @endif
        </div>

        <div class="flex items-center justify-end gap-1">
            <x-owner-edit-link
                :business="$business"
                :href="$ownerManagerRoute"
                :label="$ownerManagerLabel"
                compact
            />
            <livewire:follow-button :business="$business" compact :key="'follow-'.$business->id.'-post-'.$post->id" />
        </div>
    </div>

    @if ($post->body)
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ $post->body }}</p>
    @endif

    @if ($post->media->isNotEmpty())
        <div class="mt-3 grid gap-2 overflow-hidden rounded-xl {{ $post->media->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
            @foreach ($post->media as $media)
                @if ($media->isVideo())
                    <x-media.video-player :src="$media->url()" aspect="feed" fit="contain" loop />
                @else
                    <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}" class="w-full object-cover {{ $post->media->count() > 1 ? 'aspect-square' : 'aspect-[4/3]' }}" loading="lazy">
                @endif
            @endforeach
        </div>
    @endif

    @if ($post->products->isNotEmpty())
        <div
            class="mt-3 flex snap-x snap-mandatory gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            aria-label="{{ __('Productos de la publicación') }}"
        >
            @foreach ($post->products as $product)
                @php $photo = $product->primaryImage(); @endphp
                <div class="flex w-72 shrink-0 snap-start items-center gap-2 rounded-xl border border-zinc-200 p-2 dark:border-zinc-700">
                    <a href="{{ route('vitrinas.product', [$business, $product]) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                        <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @if ($photo)
                                <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}" loading="lazy">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="line-clamp-2 text-sm font-medium leading-tight text-zinc-950 dark:text-white">{{ $product->name }}</p>
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

    <div class="mt-3 flex flex-wrap items-center gap-1 border-t border-zinc-100 pt-2 dark:border-zinc-800">
        <livewire:post-reaction-button :post="$post" :key="'reaction-'.$post->id" />

        <button
            type="button"
            x-data
            x-on:click="navigator.share ? navigator.share({ title: {{ Js::from($business->name) }}, url: {{ Js::from(route('vitrinas.show', $business)) }} }) : $flux.toast({ text: {{ Js::from(__('Copia el enlace desde tu navegador para compartir.')) }} })"
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
        >
            <flux:icon.share class="size-4" variant="outline" />
            {{ __('Compartir') }}
        </button>

        <livewire:post-comments :post="$post" :key="'comments-'.$post->id" />

        <livewire:favorite-button :favoritable="$post" compact :key="'save-'.$post->id" />
    </div>
</article>
@endif
