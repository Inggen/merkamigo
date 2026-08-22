@php
    use Illuminate\Support\Str;

    $seoDescription = $product->description
        ? Str::limit(strip_tags($product->description), 160)
        : __(':product en :business, disponible en Merkamigo.', ['product' => $product->name, 'business' => $business->name]);
    $productUrl = route('vitrinas.product', [$business, $product]);
    $productSchemaId = $productUrl.'#product';
    $pageImage = $product->media->first()?->url() ?? $business->storefront?->coverUrl() ?? $business->logoUrl() ?? asset('images/backgrounds/fondo-redes-merkamigo.png');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => $business->name, 'url' => route('vitrinas.show', $business)],
            ['name' => $product->name],
        ]),
        \App\Support\Seo\SchemaBuilder::localBusiness($business, collect([$product])),
        \App\Support\Seo\SchemaBuilder::commerceEntity($product, $business),
    ];

    $recommendations = $business->publishedRecommendations();
    $recommendationCount = $recommendations->count();
    // Mismo fix que en vitrinas/show.blade.php: las estrellas eran fijas,
    // sin ningún dato real detrás.
    $ratedRecommendations = $recommendations->whereNotNull('rating');
    $averageRating = $ratedRecommendations->isNotEmpty() ? round($ratedRecommendations->avg('rating'), 1) : null;
    $acceptedPaymentMethods = $business->paymentMethods;
    $gallery = $product->media;
    $galleryItems = $gallery->map(fn ($media) => [
        'url' => $media->url(),
        'alt' => $media->alt_text ?? $product->name,
    ])->values();
    $productShareLabel = parse_url($productUrl, PHP_URL_HOST) . parse_url($productUrl, PHP_URL_PATH);
@endphp

<x-layouts::cliente
    :title="$product->name.' · '.$business->name"
    :description="$seoDescription"
    :image="$pageImage"
    :canonical="$productUrl"
    :show-municipality-selector="false"
    :show-chat-widget="false"
    page-schema-type="ItemPage"
    :page-schema-data="[
        'mainEntity' => ['@id' => $productSchemaId],
        'isPartOf' => ['@id' => route('vitrinas.show', $business).'#store'],
    ]"
    :schema-graph="$schemaGraph"
    og-type="product"
>
    <div
        x-data="{
            tab: 'descripcion',
            quantity: 1,
            // Bug real reportado por el usuario: en el navegador de
            // escritorio los enlaces `https://wa.me/...`,
            // `facebook.com/sharer/...`, etc. sí abren el diálogo de
            // compartir esperado, pero en Safari/iOS cada app suele
            // interceptar esas URLs vía universal links y abrir su feed
            // normal en vez del compositor de compartir (o, en el caso de
            // Instagram, ni siquiera existe una URL de compartir — el botón
            // solo abría instagram.com). El Web Share API nativo
            // (`navigator.share`) delega en el propio sistema operativo,
            // que sí sabe abrir el compositor correcto de cada app
            // instalada — está soportado en todos los navegadores móviles
            // relevantes (Safari iOS desde 12.2) pero no en la mayoría de
            // navegadores de escritorio, por eso la fila de íconos por red
            // se mantiene como respaldo cuando no está disponible.
            shareSupported: typeof navigator !== 'undefined' && !! navigator.share,
            gallery: @js($galleryItems),
            activeImage: @js($gallery->first()?->url() ?? $pageImage),
            activeAlt: @js($gallery->first()?->alt_text ?? $product->name),
            lightboxOpen: false,
            lightboxIndex: 0,
            setActive(index) {
                if (! this.gallery[index]) return;
                this.activeImage = this.gallery[index].url;
                this.activeAlt = this.gallery[index].alt;
            },
            openLightbox(index = 0) {
                this.setActive(index);
                this.lightboxIndex = index;
                this.lightboxOpen = true;
                document.body.classList.add('overflow-hidden');
            },
            closeLightbox() {
                this.lightboxOpen = false;
                document.body.classList.remove('overflow-hidden');
            },
            showPrevious() {
                if (this.gallery.length === 0) return;
                this.lightboxIndex = (this.lightboxIndex - 1 + this.gallery.length) % this.gallery.length;
                this.setActive(this.lightboxIndex);
            },
            showNext() {
                if (this.gallery.length === 0) return;
                this.lightboxIndex = (this.lightboxIndex + 1) % this.gallery.length;
                this.setActive(this.lightboxIndex);
            },
        }"
        x-on:keydown.escape.window="if (lightboxOpen) closeLightbox()"
        x-on:keydown.arrow-left.window="if (lightboxOpen) showPrevious()"
        x-on:keydown.arrow-right.window="if (lightboxOpen) showNext()"
        class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
    >
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <nav class="flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600" wire:navigate>{{ __('Inicio') }}</a>
                <span>›</span>
                @if ($business->municipality)
                    <a href="{{ route('buscar', ['municipio' => $business->municipality->slug]) }}" class="hover:text-brand-600" wire:navigate>{{ $business->municipality->name }}</a>
                    <span>›</span>
                @endif
                <a href="{{ route('vitrinas.show', $business) }}" class="hover:text-brand-600" wire:navigate>{{ $business->name }}</a>
                <span>›</span>
                <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $product->name }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-5 text-sm text-zinc-700 dark:text-zinc-300">
                <flux:button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(window.location.href); fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado') }}')"
                    variant="ghost"
                    icon="share"
                >
                    {{ __('Compartir') }}
                </flux:button>
                <livewire:favorite-button :favoritable="$product" :key="'product-'.$product->id" />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <section class="space-y-6">
                <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
                    <div class="self-start space-y-4 p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
                        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-3xl sm:leading-[1.02]">{{ $product->name }}</h1>
                        <div class="group relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                            <div class="aspect-[4/3]">
                                @if ($gallery->isNotEmpty())
                                    <button
                                        type="button"
                                        x-on:click="openLightbox(gallery.findIndex((item) => item.url === activeImage) >= 0 ? gallery.findIndex((item) => item.url === activeImage) : 0)"
                                        class="relative block h-full w-full text-left"
                                        aria-label="{{ __('Abrir galería de imágenes') }}"
                                    >
                                        <img x-bind:src="activeImage" src="{{ $gallery->first()->url() }}" x-bind:alt="activeAlt" alt="{{ $gallery->first()->alt_text ?? $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" loading="eager" decoding="async">
                                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-zinc-950/0 transition duration-300 group-hover:bg-zinc-950/20">
                                            <span class="inline-flex translate-y-2 items-center justify-center rounded-full bg-white/95 p-3 text-zinc-900 opacity-0 shadow-lg transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                                                <flux:icon.magnifying-glass-plus class="size-6" variant="outline" />
                                            </span>
                                        </div>
                                    </button>
                                @endif
                            </div>

                            @if ($product->hasActivePromo())
                                <span class="absolute left-4 top-4 inline-flex rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                                    {{ $product->promo_label ?: __('Promo') }}
                                </span>
                            @elseif (! $product->isSoldOut())
                                <span class="absolute left-4 top-4 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-zinc-700 shadow-sm dark:bg-zinc-900 dark:text-zinc-100">
                                    {{ __('Disponible') }}
                                </span>
                            @endif
                        </div>

                        @if ($gallery->count() > 1)
                            <div class="grid grid-cols-5 gap-3">
                                @foreach ($gallery->take(5) as $media)
                                    <button
                                        type="button"
                                        x-on:click="setActive({{ $loop->index }})"
                                        class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 transition hover:border-brand-300 dark:border-zinc-800 dark:bg-zinc-800"
                                        aria-label="{{ __('Ver imagen :numero', ['numero' => $loop->iteration]) }}"
                                    >
                                        <div class="aspect-square">
                                            <img src="{{ $media->url() }}" class="h-full w-full object-cover" alt="{{ $media->alt_text ?? $product->name }}" loading="lazy" decoding="async">
                                        </div>
                                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-zinc-950/0 transition duration-300 group-hover:bg-zinc-950/20">
                                            <span class="inline-flex items-center justify-center rounded-full bg-white/95 p-2 text-zinc-900 opacity-0 shadow-md transition duration-300 group-hover:opacity-100">
                                                <flux:icon.magnifying-glass class="size-4" variant="outline" />
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="self-start space-y-5 p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                        <div class="space-y-3">
                            <a href="{{ route('vitrinas.show', $business) }}" class="inline-flex items-center gap-2 text-lg text-zinc-600 transition hover:text-brand-600 dark:text-zinc-300" wire:navigate>
                                {{ $business->name }}
                                @if ($business->hasVerifiedBadge())
                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                        <flux:icon.check-badge class="size-4" />
                                    </span>
                                @endif
                            </a>

                            <div class="flex flex-wrap items-center gap-x-1 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                                @php
                                    $metaItems = collect([
                                        $business->category?->name,
                                        $product->type ? Str::headline($product->type) : null,
                                        $business->municipality?->name,
                                    ])->filter()->values();
                                @endphp

                                @foreach ($metaItems as $metaItem)
                                    <span>{{ $metaItem }}</span>
                                    @if (! $loop->last)
                                        <span>•</span>
                                    @endif
                                @endforeach
                            </div>

                            @if ($averageRating !== null)
                                <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    <div class="flex items-center gap-0.5 text-rose-500">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <flux:icon.star class="size-4" :variant="$i <= round($averageRating) ? 'solid' : 'outline'" />
                                        @endfor
                                    </div>
                                    <span>{{ number_format($averageRating, 1) }} · {{ __(':count reseñas', ['count' => $recommendationCount]) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">
                            @if ($product->hasActivePromo())
                                <span class="mr-2 text-lg font-normal text-zinc-400 line-through">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                                ${{ number_format((float) $product->promo_price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'exacto' && $product->price)
                                ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'desde' && $product->price)
                                {{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'consultar')
                                {{ __('Consultar precio') }}
                            @endif

                            @if ($product->unit)
                                <span class="text-base font-normal text-zinc-500">/ {{ $product->unit }}</span>
                            @endif
                        </div>

                        @if ($product->variants->isNotEmpty())
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <h2 class="mb-3 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Variantes') }}</h2>
                                <div class="space-y-2 text-sm">
                                    @foreach ($product->variants as $variant)
                                        <div class="flex items-center justify-between gap-4 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800/70">
                                            <span>{{ $variant->label }}</span>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                                @if ($variant->price)
                                                    ${{ number_format((float) $variant->price, 0, ',', '.') }}
                                                @elseif ($product->price)
                                                    ${{ number_format((float) $product->price, 0, ',', '.') }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            <div class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Cantidad') }}</div>
                            <div class="inline-flex items-center rounded-2xl border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
                                <button type="button" x-on:click="quantity = Math.max(1, quantity - 1)" class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">-</button>
                                <div class="min-w-12 text-center text-lg font-semibold text-zinc-950 dark:text-white" x-text="quantity"></div>
                                <button type="button" x-on:click="quantity = quantity + 1" class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">+</button>
                            </div>
                        </div>

                        @if ($business->whatsapp_number)
                            <a
                                href="{{ route('vitrinas.whatsapp.product', [$business, $product]) }}"
                                target="_blank"
                                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-600 px-5 py-4 text-lg font-semibold text-white transition hover:bg-brand-700"
                            >
                                <flux:icon.chat-bubble-left-right class="size-6" />
                                {{ $product->isSoldOut() ? __('Consultar disponibilidad') : __('Pedir por WhatsApp') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div x-ref="paymentTab" class="overflow-hidden rounded-xl  border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-wrap gap-6 border-b border-zinc-200 px-5 pt-5 text-sm font-semibold dark:border-zinc-800 sm:px-6">
                        <button type="button" x-on:click="tab = 'descripcion'" :class="tab === 'descripcion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Descripción') }}</button>
                        <button type="button" x-on:click="tab = 'resenas'" :class="tab === 'resenas' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Reseñas') }}{{ $recommendationCount > 0 ? ' ('.$recommendationCount.')' : '' }}</button>
                        <button type="button" x-on:click="tab = 'pago'" :class="tab === 'pago' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Información de pago') }}</button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div x-show="tab === 'descripcion'">
                            <div>
                                <p class="whitespace-pre-line text-base leading-8 text-zinc-600 dark:text-zinc-300">{{ $product->description ?: __('Este producto todavía no tiene descripción detallada.') }}</p>
                            </div>
                        </div>

                        <div x-show="tab === 'resenas'" x-cloak>
                            @if ($recommendations->isEmpty())
                                <x-states.empty title="{{ __('Todavía no hay reseñas') }}" description="{{ __('Sé el primero en dejar tu opinión sobre este negocio.') }}" />
                            @else
                                <div class="space-y-4">
                                    @foreach ($recommendations as $recommendation)
                                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-zinc-950 dark:text-white">{{ $recommendation->authorUser?->name ?? __('Cliente verificado') }}</span>
                                                @if ($recommendation->rating)
                                                    <div class="flex items-center gap-0.5 text-rose-500">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <flux:icon.star class="size-3.5" :variant="$i <= $recommendation->rating ? 'solid' : 'outline'" />
                                                        @endfor
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $recommendation->body }}</p>
                                            @if ($recommendation->business_response)
                                                <div class="mt-3 rounded-2xl bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                    <span class="font-semibold text-zinc-950 dark:text-white">{{ __('Respuesta del negocio:') }}</span>
                                                    {{ $recommendation->business_response }}
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div x-show="tab === 'pago'" x-cloak>
                            @if ($acceptedPaymentMethods->isNotEmpty() || filled($business->payment_info))
                                @if ($acceptedPaymentMethods->isNotEmpty())
                                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                                        @foreach ($acceptedPaymentMethods as $method)
                                            <div class="flex items-center justify-center rounded-xl border-zinc-200 dark:border-zinc-800" title="{{ $method->name }}">
                                                @if ($method->logoUrl())
                                                    <img src="{{ $method->logoUrl() }}" alt="{{ $method->name }}" class="w-auto rounded-xl object-contain">
                                                @else
                                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $method->name }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (filled($business->payment_info))
                                    <p class="whitespace-pre-line text-base leading-8 text-zinc-600 dark:text-zinc-300 {{ $acceptedPaymentMethods->isNotEmpty() ? 'mt-5' : '' }}">{{ $business->payment_info }}</p>
                                @endif
                            @else
                                <x-states.empty title="{{ __('Todavía no hay información de pago') }}" />
                            @endif
                        </div>
                    </div>
                </div>

                @if ($relatedProducts->isNotEmpty())
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Productos relacionados') }}</h2>
                            <a href="{{ route('vitrinas.show', $business) }}" class="text-sm font-semibold text-brand-600 transition hover:text-brand-700" wire:navigate>
                                {{ __('Ver más productos') }}
                            </a>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($relatedProducts as $relatedProduct)
                                @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $relatedProduct])
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Comparte este producto') }}</h3>
                    <div class="mt-4 space-y-5">
                        <div class="flex items-center justify-center gap-4">
                            <img src="{{ route('vitrinas.qr.product', [$business, $product]) }}" alt="QR" class="size-28 shrink-0 rounded-2xl border border-zinc-200 bg-white p-2 dark:border-zinc-700" loading="lazy" decoding="async">
                            <p class="max-w-32 text-sm font-medium leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Escanea para compartir') }}</p>
                        </div>
                        <div x-show="shareSupported" x-cloak>
                            <button
                                type="button"
                                x-on:click="navigator.share({
                                        title: @js($product->name.' · '.$business->name),
                                        text: @js($product->name.' · '.$business->name),
                                        url: @js($productUrl),
                                    }).then(() => fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' })).catch(() => {})"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                            >
                                <flux:icon.share class="size-5" variant="outline" />
                                {{ __('Compartir') }}
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-3" x-show="! shareSupported" x-cloak>
                            <a
                                href="https://wa.me/?text={{ urlencode($product->name.' · '.$business->name.' '.$productUrl) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                x-on:click="fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' })"
                                class="inline-flex size-11 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                aria-label="{{ __('Compartir por WhatsApp') }}"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.62 2 2.23 6.38 2.23 11.79c0 1.73.45 3.42 1.31 4.91L2 22l5.46-1.43a9.74 9.74 0 0 0 4.57 1.16h.01c5.41 0 9.79-4.39 9.79-9.8 0-2.62-1.02-5.08-2.88-6.92Zm-7.02 15.16h-.01a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.24.85.87-3.15-.2-.32a8.12 8.12 0 0 1-1.25-4.35c0-4.49 3.67-8.15 8.18-8.15 2.18 0 4.22.85 5.75 2.39a8.08 8.08 0 0 1 2.38 5.76c0 4.5-3.66 8.16-8.05 8.16Zm4.47-6.11c-.24-.12-1.42-.7-1.64-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1-.37-1.91-1.18-.71-.64-1.19-1.42-1.33-1.66-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.48-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.57 4.09 3.61.57.25 1.02.4 1.37.52.58.18 1.1.15 1.52.09.46-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/></svg>
                            </a>
                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($productUrl) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                x-on:click="fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' })"
                                class="inline-flex size-11 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                aria-label="{{ __('Compartir en Facebook') }}"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true"><path d="M13.5 21v-7h2.3l.4-3h-2.7V9.1c0-.9.3-1.6 1.6-1.6H16V4.8c-.5-.1-1.3-.2-2.2-.2-2.2 0-3.8 1.3-3.8 4V11H7.8v3H10V21h3.5Z"/></svg>
                            </a>
                            <a
                                href="https://www.instagram.com/"
                                target="_blank"
                                rel="noopener noreferrer"
                                x-on:click="navigator.clipboard.writeText('{{ $productUrl }}'); fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado para compartir en Instagram') }}')"
                                class="inline-flex size-11 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                aria-label="{{ __('Compartir en Instagram') }}"
                            >
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor" stroke="none"/></svg>
                            </a>
                            <a
                                href="https://twitter.com/intent/tweet?url={{ urlencode($productUrl) }}&text={{ urlencode($product->name.' · '.$business->name) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                x-on:click="fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' })"
                                class="inline-flex size-11 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                aria-label="{{ __('Compartir en Twitter') }}"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true"><path d="M22 5.92c-.74.33-1.53.55-2.36.65a4.1 4.1 0 0 0 1.8-2.27 8.18 8.18 0 0 1-2.6.99A4.08 4.08 0 0 0 11.85 8a11.58 11.58 0 0 1-8.4-4.26 4.08 4.08 0 0 0 1.26 5.45 4.04 4.04 0 0 1-1.85-.51v.05a4.08 4.08 0 0 0 3.27 4 4.1 4.1 0 0 1-1.84.07 4.09 4.09 0 0 0 3.81 2.84A8.2 8.2 0 0 1 2 17.54 11.55 11.55 0 0 0 8.26 19.4c7.52 0 11.64-6.23 11.64-11.63 0-.18 0-.36-.01-.53A8.24 8.24 0 0 0 22 5.92Z"/></svg>
                            </a>
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText('{{ $productUrl }}'); fetch('{{ route('vitrinas.compartir.product', [$business, $product]) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado') }}')"
                                class="inline-flex size-11 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                aria-label="{{ __('Copiar enlace') }}"
                            >
                                <flux:icon.document-duplicate class="size-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-14 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
                                @if ($business->logoUrl())
                                    <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->logo_alt_text ?? $business->name }}" loading="lazy" decoding="async">
                                @else
                                    <flux:icon.building-storefront class="size-6 text-zinc-400" variant="outline" />
                                @endif
                            </div>
                            <div>
                                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $business->name }}</h3>
                                <a href="{{ route('vitrinas.show', $business) }}" class="mt-2 inline-flex items-center rounded-xl border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10" wire:navigate>
                                    {{ __('Ver tienda') }}
                                </a>
                            </div>
                        </div>
                        @if ($business->hasVerifiedBadge())
                            <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                <flux:icon.check-badge class="size-4" />
                            </span>
                        @endif
                    </div>

                    @if ($business->hoursNote() || $business->hasStructuredSchedule())
                        <div class="mt-5 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                            <h4 class="mb-3 font-semibold text-zinc-950 dark:text-white">{{ __('Horario de atención') }}</h4>
                            @if ($business->hasStructuredSchedule())
                                <div class="space-y-2 text-sm">
                                    @foreach ($business->scheduleForDisplay() as $day => $state)
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ $day }}</span>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $state }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($business->hoursNote())
                                <p class="text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->hoursNote() }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($acceptedPaymentMethods->isNotEmpty() || filled($business->payment_info))
                    <button
                        type="button"
                        x-on:click="tab = 'pago'; $nextTick(() => $refs.paymentTab.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                        class="flex w-full items-center gap-3 rounded-xl border border-zinc-200 bg-white p-5 text-left shadow-sm transition hover:border-brand-300 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200 cursor-pointer">
                            <flux:icon.credit-card class="size-5" />
                        </span>
                        <span>
                            <span class="block font-semibold text-zinc-950 dark:text-white">{{ __('Métodos de pago') }}</span>
                            <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ __('Ver formas de pago aceptadas') }}</span>
                        </span>
                    </button>
                @endif
            </aside>
        </div>

        @if ($gallery->isNotEmpty())
            <div
                x-cloak
                x-show="lightboxOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/88 px-4 py-6"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('Galería de imágenes') }}"
            >
                <button type="button" x-on:click="closeLightbox()" class="absolute inset-0 cursor-zoom-out" aria-label="{{ __('Cerrar galería') }}"></button>

                <div class="relative z-10 w-full max-w-5xl">
                    <div class="relative overflow-hidden rounded-xl  bg-white/5 shadow-2xl backdrop-blur-sm">
                        <button
                            type="button"
                            x-on:click="closeLightbox()"
                            class="absolute right-4 top-4 z-20 inline-flex size-11 items-center justify-center rounded-full bg-white/90 text-zinc-900 transition hover:bg-white"
                            aria-label="{{ __('Cerrar galería') }}"
                        >
                            <flux:icon.x-mark class="size-5" />
                        </button>

                        @if ($gallery->count() > 1)
                            <button
                                type="button"
                                x-on:click="showPrevious()"
                                class="absolute left-4 top-1/2 z-20 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-900 transition hover:bg-white"
                                aria-label="{{ __('Imagen anterior') }}"
                            >
                                <flux:icon.chevron-left class="size-6" />
                            </button>

                            <button
                                type="button"
                                x-on:click="showNext()"
                                class="absolute right-4 top-1/2 z-20 inline-flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-900 transition hover:bg-white"
                                aria-label="{{ __('Siguiente imagen') }}"
                            >
                                <flux:icon.chevron-right class="size-6" />
                            </button>
                        @endif

                        <div class="flex min-h-[60vh] items-center justify-center bg-zinc-950/40 p-4 sm:p-8">
                            <img x-bind:src="activeImage" x-bind:alt="activeAlt" class="max-h-[72vh] w-auto max-w-full rounded-2xl object-contain">
                        </div>

                        @if ($gallery->count() > 1)
                            <div class="flex items-center justify-between gap-4 border-t border-white/10 px-4 py-4 sm:px-6">
                                <div class="text-sm text-white/80">
                                    <span x-text="lightboxIndex + 1"></span> / {{ $gallery->count() }}
                                </div>

                                <div class="flex max-w-full gap-2 overflow-x-auto">
                                    @foreach ($gallery as $media)
                                        <button
                                            type="button"
                                            x-on:click="lightboxIndex = {{ $loop->index }}; setActive({{ $loop->index }})"
                                            class="overflow-hidden rounded-xl border border-white/15 transition"
                                            x-bind:class="lightboxIndex === {{ $loop->index }} ? 'border-white shadow-lg' : 'border-white/15 opacity-70 hover:opacity-100'"
                                            aria-label="{{ __('Ir a imagen :numero', ['numero' => $loop->iteration]) }}"
                                        >
                                            <img src="{{ $media->url() }}" class="size-16 object-cover" alt="{{ $media->alt_text ?? $product->name }}" loading="lazy" decoding="async">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <x-storefront-chat-widget :business="$business" :with-sound="false" />
</x-layouts::cliente>

@push('scripts')
    <script async src="https://static.addtoany.com/menu/page.js"></script>
@endpush
