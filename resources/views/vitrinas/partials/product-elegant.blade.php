<section class="elegant-product-hero" aria-label="{{ __('Vista elegante del producto') }}">
    <div class="elegant-product-background" aria-hidden="true">
        <img x-show="activeType !== 'video'" x-bind:src="activeType === 'image' ? activeMedia : @js($pageImage)" src="{{ $pageImage }}" alt="" fetchpriority="high">
        @if ($productVideo)
            <video x-ref="backgroundVideo" x-show="activeType === 'video'" src="{{ $productVideo->url() }}" poster="{{ $pageImage }}" muted playsinline loop preload="metadata"
                x-init="if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && activeType === 'video') $el.play().catch(() => {})"
                x-on:play="mediaPlaying = true" x-on:pause="mediaPlaying = false"></video>
        @endif
    </div>
    <div class="elegant-product-shell">
        <div class="elegant-product-toolbar">
            <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="elegant-back"><flux:icon.arrow-left class="size-5" /> {{ __('Volver a la tienda') }}</a>
            <div class="flex items-center gap-3">
                <x-owner-edit-link :business="$business" :href="route('emprendedores.negocios.productos', $business).'#producto-'.$product->id" :label="__('Editar')" class="elegant-product-edit !text-white hover:!text-white" />
                <button type="button" class="elegant-media-control" aria-label="{{ __('Compartir producto') }}" x-on:click="if (shareSupported) { navigator.share({ title: @js($product->name), url: @js($productUrl) }).catch(() => {}); } else { navigator.clipboard.writeText(@js($productUrl)).then(() => $flux.toast(@js(__('Enlace copiado')))).catch(() => {}); }"><flux:icon.share class="size-5" /></button>
                @if ($productVideo)
                    <button type="button" x-cloak x-show="activeType === 'video'" class="elegant-media-control" x-on:click="toggleBackgroundVideo()" x-bind:aria-label="mediaPlaying ? @js(__('Pausar video de fondo')) : @js(__('Reproducir video de fondo'))" x-bind:title="mediaPlaying ? @js(__('Pausar video de fondo')) : @js(__('Reproducir video de fondo'))">
                        <flux:icon.pause x-show="mediaPlaying" class="size-5" />
                        <flux:icon.play x-show="!mediaPlaying" class="size-5" />
                    </button>
                @endif
                <livewire:favorite-button :favoritable="$product" :key="'elegant-product-'.$product->id" />
            </div>
        </div>
        <div class="elegant-product-grid">
            <div class="elegant-product-thumbnails" aria-label="{{ __('Galería del producto') }}">
                @foreach ($gallery as $media)
                    <button type="button" x-on:click="setActive({{ $loop->index }})" x-bind:aria-pressed="activeMedia === @js($media->url())" class="elegant-thumbnail" aria-label="{{ $media->isVideo() ? __('Ver video del producto') : __('Ver foto :number', ['number' => $loop->iteration]) }}">
                        @if ($media->isVideo())
                            <img src="{{ $pageImage }}" alt="" loading="lazy"><span class="elegant-play"><flux:icon.play class="size-5" /></span>
                        @else
                            <img src="{{ $media->url() }}" alt="{{ $media->alt_text ?? $product->name }}" loading="lazy">
                        @endif
                    </button>
                @endforeach
                @if ($gallery->isNotEmpty())
                    <button type="button" class="elegant-thumbnail elegant-gallery-open" x-on:click="openLightbox(Math.max(0, gallery.findIndex(item => item.url === activeMedia)))"><flux:icon.arrows-pointing-out class="size-5" /><span>{{ __('Ampliar') }}</span></button>
                @endif
            </div>
            <div class="elegant-product-heading">
                <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="elegant-business-name">{{ $business->name }} @if ($business->hasVerifiedBadge())<flux:icon.check-badge class="size-5 text-sky-400" variant="solid" />@endif</a>
                <h1>{{ $product->name }}</h1>
                <div class="elegant-product-meta">
                    @if ($business->category)<span><flux:icon.tag class="size-4" />{{ $business->category->name }}</span>@endif
                    @if ($business->municipality)<span><flux:icon.map-pin class="size-4" />{{ $business->municipality->name }}</span>@endif
                    <span class="{{ $product->isSoldOut() ? 'text-rose-200' : 'text-green-300' }}">● {{ $product->isSoldOut() ? __('Agotado') : __('Disponible') }}</span>
                </div>
            </div>
            <aside class="elegant-product-sidebar">
                <div class="elegant-product-purchase">
                    @include('vitrinas.partials.product-purchase')
                </div>
                <a class="elegant-store-card" href="{{ route('vitrinas.show', $business) }}" wire:navigate>
                    @if ($business->logoUrl())<img src="{{ $business->logoUrl() }}" alt="" loading="lazy">@endif
                    <div><strong>{{ $business->name }}</strong><span>{{ $business->municipality?->name }}</span><b>{{ __('Ver tienda') }} →</b></div>
                </a>
            </aside>
            <div class="elegant-product-details">
                @include('vitrinas.partials.product-tabs')
            </div>
        </div>
    </div>
</section>
@if ($relatedProducts->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="mb-5 text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Productos relacionados') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($relatedProducts as $relatedProduct)
                @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $relatedProduct])
            @endforeach
        </div>
    </section>
@endif
