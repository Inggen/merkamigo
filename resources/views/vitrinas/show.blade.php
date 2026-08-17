@php
    use Illuminate\Support\Str;

    $seoDescription = $business->storefront?->description
        ? Str::limit(strip_tags($business->storefront->description), 160)
        : __(':name en :municipio, con Merkamigo.', ['name' => $business->name, 'municipio' => $business->municipality?->name ?? '']);
    $pageUrl = route('vitrinas.show', $business);
    $storeSchemaId = $pageUrl.'#store';
    $pageImage = $business->storefront?->coverUrl() ?? $business->logoUrl() ?? asset('images/backgrounds/fondo-redes-merkamigo.png');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb(array_values(array_filter([
            ['name' => __('Inicio'), 'url' => route('home')],
            $business->municipality ? ['name' => $business->municipality->name, 'url' => route('buscar', ['municipio' => $business->municipality->slug])] : null,
            ['name' => $business->name],
        ]))),
        \App\Support\Seo\SchemaBuilder::localBusiness($business, $products),
        \App\Support\Seo\SchemaBuilder::itemList(
            $products->take(12)->map(fn ($product) => [
                'name' => $product->name,
                'url' => route('vitrinas.product', [$business, $product]),
                'image' => $product->media->first()?->url(),
            ])->all(),
            __('Productos de :business', ['business' => $business->name]),
        ),
    ];

    $businessAttributes = $business->activeAttributes();
    $galleryPhotos = $products->flatMap(fn ($product) => $product->media)->take(6)->values();
    $recommendations = $business->publishedRecommendations();
    $recommendationCount = $recommendations->count();
    // Pedido del usuario: las 5 estrellas de "Opiniones de clientes" eran
    // fijas, sin ningún dato real detrás. Recomendaciones de antes de que
    // existiera la calificación (`rating`) quedan fuera del promedio en
    // vez de contar como si tuvieran 0 estrellas.
    $ratedRecommendations = $recommendations->whereNotNull('rating');
    $averageRating = $ratedRecommendations->isNotEmpty() ? round($ratedRecommendations->avg('rating'), 1) : null;
    $featuredProducts = $products->take(6);
    $socialLinks = collect(array_filter($business->social_links ?? []));
    $hasSidebarContent = filled($business->whatsapp_number)
        || filled($business->payment_info)
        || $socialLinks->isNotEmpty()
        || $business->hasStructuredSchedule()
        || filled($business->hoursNote())
        || $business->hasVerifiedBadge()
        || $recommendationCount > 0;
    // Safari (iOS) no le da tamaño por defecto a un `<svg>` sin `width`/
    // `height` propios cuando solo el `<span>` que lo envuelve trae el
    // tamaño por CSS (`size-6`/`size-4`) — el ícono queda invisible aunque
    // el círculo de fondo sí se vea (bug real reportado por el usuario en
    // iPhone/Safari, no reproducible en Chrome). `width="100%" height="100%"`
    // en el propio `<svg>` lo hace depender del tamaño del contenedor en
    // cualquier navegador, en vez de su tamaño intrínseco por defecto.
    $socialIcons = [
        'facebook' => '<svg width="100%" height="100%" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7h2.3l.4-3h-2.7V9.1c0-.9.3-1.6 1.6-1.6H16V4.8c-.5-.1-1.3-.2-2.2-.2-2.2 0-3.8 1.3-3.8 4V11H7.8v3H10V21h3.5Z"/></svg>',
        'instagram' => '<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>',
        'tiktok' => '<svg width="100%" height="100%" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14.7 3c.2 1.8 1.2 3.3 2.9 4.2 1 .5 2 .8 3.1.8v2.8c-1.4 0-2.7-.3-4-.9v5.8c0 3-2.4 5.3-5.4 5.3s-5.4-2.4-5.4-5.3 2.4-5.3 5.4-5.3c.3 0 .6 0 .9.1V13a3.8 3.8 0 0 0-.9-.1c-1.5 0-2.7 1.2-2.7 2.7s1.2 2.7 2.7 2.7 2.8-1.1 2.8-2.7V3h2.6Z"/></svg>',
    ];
@endphp

<x-layouts::cliente
    :title="$business->name"
    :description="$seoDescription"
    :image="$pageImage"
    :canonical="$pageUrl"
    :show-municipality-selector="false"
    page-schema-type="ProfilePage"
    :page-schema-data="[
        'about' => $business->category?->name,
        'mainEntity' => ['@id' => $storeSchemaId],
        'significantLink' => $products->take(6)->map(fn ($product) => route('vitrinas.product', [$business, $product]))->values()->all(),
    ]"
    :schema-graph="$schemaGraph"
>
    <div
        x-data="{
            tab: 'inicio',
            // Bug real reportado por el usuario en la página de producto
            // (ver `product.blade.php`): en Safari/iOS, apps como Instagram
            // interceptan las URLs manuales de compartir y abren su feed
            // normal en vez del compositor. El Web Share API nativo
            // (`navigator.share`) delega en el propio sistema operativo —
            // mismo criterio acá para compartir la vitrina completa.
            shareSupported: typeof navigator !== 'undefined' && !! navigator.share,
        }"
        class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600" wire:navigate>{{ __('Inicio') }}</a>
            <span>›</span>
            @if ($business->municipality)
                <a href="{{ route('buscar', ['municipio' => $business->municipality->slug]) }}" class="hover:text-brand-600" wire:navigate>{{ $business->municipality->name }}</a>
                <span>›</span>
            @endif
            <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $business->name }}</span>
        </nav>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="space-y-6">
                <article class="overflow-hidden rounded-[32px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="relative aspect-[16/10] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 sm:aspect-[16/6]">
                        @if ($business->storefront?->coverUrl())
                            <img src="{{ $business->storefront->coverUrl() }}" class="h-full w-full object-cover" alt="{{ $business->storefront->cover_alt_text ?? __('Portada de :name', ['name' => $business->name]) }}" loading="eager" decoding="async">
                        @elseif ($business->logoUrl())
                            <img src="{{ $business->logoUrl() }}" class="h-full w-full object-cover" alt="{{ $business->logo_alt_text ?? $business->name }}" loading="eager" decoding="async">
                        @endif
                    </div>

                    <div class="relative px-5 pb-6 pt-0 sm:px-8 sm:pb-8">
                        <div class="-mt-16 flex sm:-mt-20">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-white shadow-md dark:border-zinc-900 dark:bg-zinc-950 sm:size-24">
                                @if ($business->logoUrl())
                                    <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->logo_alt_text ?? $business->name }}" loading="lazy" decoding="async">
                                @else
                                    <flux:icon.building-storefront class="size-8 text-zinc-400" variant="outline" />
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">{{ $business->name }}</h1>

                                @if ($business->hasVerifiedBadge())
                                    <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-200 dark:bg-brand-500/10 dark:text-brand-200 dark:ring-brand-500/30">
                                        {{ $business->verifiedBadgeLabel() }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($business->category)
                                    <span class="inline-flex items-center rounded-2xl bg-zinc-100 px-4 py-2 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $business->category->name }}</span>
                                @endif
                                @if ($business->municipality)
                                    <span class="inline-flex items-center rounded-2xl bg-zinc-100 px-4 py-2 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $business->municipality->name }}</span>
                                @endif
                            </div>

                            @if ($business->storefront?->description)
                                <p class="max-w-5xl text-md leading-[1.55] text-zinc-600 dark:text-zinc-300 sm:text-md">
                                    {{ $business->storefront->description }}
                                </p>
                            @elseif ($business->storefront?->headline)
                                <p class="max-w-5xl text-md leading-[1.55] text-zinc-600 dark:text-zinc-300 sm:text-md">
                                    {{ $business->storefront->headline }}
                                </p>
                            @endif
                        </div>
                    </div>
                </article>

                <div class="overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 px-5 pt-5 dark:border-zinc-800 sm:px-6">
                        <div class="flex flex-wrap gap-6 text-sm font-semibold">
                            <button type="button" x-on:click="tab = 'inicio'" :class="tab === 'inicio' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Inicio') }}</button>
                            <button type="button" x-on:click="tab = 'productos'" :class="tab === 'productos' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Productos') }}</button>
                            <button type="button" x-on:click="tab = 'opiniones'" :class="tab === 'opiniones' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Opiniones') }}</button>
                            <button type="button" x-on:click="tab = 'informacion'" :class="tab === 'informacion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Información') }}</button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pb-4">
                            <livewire:favorite-button :favoritable="$business" :key="'business-'.$business->id" />

                            <flux:button
                                x-on:click="navigator.clipboard.writeText(window.location.href); fetch('{{ route('vitrinas.compartir', $business) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado') }}')"
                                variant="ghost"
                                icon="share"
                            >
                                {{ __('Compartir') }}
                            </flux:button>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div x-show="tab === 'inicio'" class="space-y-6">
                            @if ($business->storefront?->description)
                                <div>
                                    <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Sobre este negocio') }}</h2>
                                    <p class="mt-3 whitespace-pre-line text-base leading-8 text-zinc-600 dark:text-zinc-300">{{ $business->storefront->description }}</p>
                                </div>
                            @endif

                            @if ($businessAttributes->isNotEmpty())
                                <div class="grid gap-3 md:grid-cols-3">
                                    @foreach ($businessAttributes as $attribute)
                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-200">
                                            {{ $attribute->name }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($featuredProducts->isNotEmpty())
                                <div>
                                    <div class="mb-4 flex items-center justify-between gap-4">
                                        <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Productos destacados') }}</h2>
                                        <button type="button" x-on:click="tab = 'productos'" class="text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                                            {{ __('Ver todos') }}
                                        </button>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($featuredProducts as $product)
                                            @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <div class="flex items-center gap-3 text-brand-600">
                                        <flux:icon.heart class="size-6" />
                                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Hecho con cuidado') }}</h3>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('La vitrina resume lo esencial del negocio para que el cliente encuentre rápido qué ofrece y cómo contactarlo.') }}</p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <div class="flex items-center gap-3 text-brand-600">
                                        <flux:icon.sparkles class="size-6" />
                                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Descubre lo local') }}</h3>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Explora productos, servicios y señales de confianza en un solo lugar.') }}</p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <div class="flex items-center gap-3 text-brand-600">
                                        <flux:icon.shield-check class="size-6" />
                                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Apoya negocios reales') }}</h3>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Merkamigo centraliza información pública, medios de contacto y evidencia de actividad cuando existe.') }}</p>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-2xl bg-brand-50 p-3 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                            <flux:icon.shield-check class="size-6" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Negocio visible en Merkamigo') }}</h3>
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $business->hasVerifiedBadge() ? __('Esta vitrina muestra un negocio con validación activa en Merkamigo.') : __('Esta vitrina pertenece a un negocio publicado dentro de la plaza de Merkamigo.') }}</p>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        @auth
                                            <a href="{{ route('emprendedores.home') }}" class="inline-flex items-center rounded-xl border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10" wire:navigate>
                                                {{ __('Gestionar desde mi cuenta') }}
                                            </a>
                                        @else
                                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10" wire:navigate>
                                                {{ __('¿Eres el dueño? Inicia sesión') }}
                                            </a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'productos'" x-cloak>
                            @if ($products->isEmpty())
                                <x-states.empty title="{{ __('Todavía no hay productos publicados') }}" />
                            @else
                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($products as $product)
                                        @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div x-show="tab === 'opiniones'" x-cloak>
                            <livewire:submit-opinion-form :business="$business" :key="'submit-opinion-'.$business->id" />

                            @if ($recommendations->isEmpty())
                                <x-states.empty
                                    title="{{ __('Todavía no hay opiniones') }}"
                                    description="{{ __('Sé el primero en dejar tu opinión sobre este negocio.') }}"
                                />
                            @else
                                <div class="space-y-4">
                                    @foreach ($recommendations as $recommendation)
                                        <article class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-zinc-950 dark:text-white">
                                                    {{ $recommendation->authorUser?->name ?? __('Cliente verificado') }}
                                                </span>

                                                @if ($recommendation->rating)
                                                    <div class="flex items-center gap-0.5 text-amber-500">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <flux:icon.star class="size-3.5" :variant="$i <= $recommendation->rating ? 'solid' : 'outline'" />
                                                        @endfor
                                                    </div>
                                                @endif

                                                @foreach ($recommendation->tags ?? [] as $tag)
                                                    <flux:badge size="sm" color="zinc">{{ $tag }}</flux:badge>
                                                @endforeach
                                            </div>

                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                                                {{ $recommendation->body }}
                                            </p>

                                            @if ($recommendation->business_response)
                                                <div class="mt-4 rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                    <span class="font-semibold text-zinc-950 dark:text-white">{{ __('Respuesta del negocio:') }}</span>
                                                    {{ $recommendation->business_response }}
                                                </div>
                                            @endif

                                            <div class="mt-3">
                                                <flux:link :href="route('reportes.crear.recomendacion', [$business, $recommendation])" class="text-xs text-zinc-400" wire:navigate>
                                                    {{ __('Reportar') }}
                                                </flux:link>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div x-show="tab === 'informacion'" x-cloak class="grid gap-5 lg:grid-cols-2">
                            @if ($business->hoursNote())
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Horario') }}</h3>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->hoursNote() }}</p>
                                </div>
                            @endif

                            @if ($business->hasStructuredSchedule())
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Horario por día') }}</h3>
                                    <div class="mt-3 space-y-2 text-sm">
                                        @foreach ($business->scheduleForDisplay() as $day => $state)
                                            <div class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-2 last:border-b-0 last:pb-0 dark:border-zinc-800">
                                                <span class="text-zinc-500 dark:text-zinc-400">{{ $day }}</span>
                                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $state }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($business->address)
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Dirección') }}</h3>
                                    <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->address }}</p>
                                </div>
                            @endif

                            @if ($socialLinks->isNotEmpty())
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Redes sociales') }}</h3>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($socialLinks as $network => $link)
                                            <a href="{{ $link }}" target="_blank" class="inline-flex items-center rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-200">
                                                @if (isset($socialIcons[$network]))
                                                    <span class="mr-2 inline-flex size-4 shrink-0 items-center justify-center">{!! $socialIcons[$network] !!}</span>
                                                @endif
                                                {{ ucfirst((string) $network) }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($business->payment_info)
                                <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800 lg:col-span-2">
                                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Información de pago') }}</h3>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->payment_info }}</p>
                                </div>
                            @endif

                            @if ($business->hasVerifiedBadge())
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100 lg:col-span-2">
                                    <h3 class="font-semibold">{{ $business->verifiedBadgeLabel() }}</h3>
                                    <p class="mt-2 text-sm leading-7">{{ __('Esta insignia confirma una revisión básica de identidad o documentos del negocio. No implica garantía de calidad, pago ni entrega por parte de Merkamigo.') }}</p>
                                </div>
                            @endif

                            <div class="lg:col-span-2">
                                <a href="{{ route('reportes.crear.negocio', $business) }}" class="text-sm text-zinc-400 transition hover:text-zinc-600" wire:navigate>
                                    {{ __('Reportar este negocio') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
                @if ($business->whatsapp_number)
                    <a href="{{ route('vitrinas.whatsapp', $business) }}" target="_blank" class="block rounded-[24px] bg-brand-600 p-5 text-white shadow-sm transition hover:bg-brand-700">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex size-11 items-center justify-center rounded-2xl bg-white/15">
                                <flux:icon.chat-bubble-left-right class="size-6" />
                            </span>
                            <div>
                                <div class="text-lg font-semibold">{{ __('Escríbenos por WhatsApp') }}</div>
                                <div class="text-sm text-white/80">{{ __('Atención directa del negocio') }}</div>
                            </div>
                        </div>
                    </a>
                @endif

                @if ($business->payment_info)
                    <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                <flux:icon.credit-card class="size-5" />
                            </span>
                            <div>
                                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Métodos de pago') }}</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Información publicada por el negocio') }}</p>
                            </div>
                        </div>
                        <p class="mt-4 whitespace-pre-line text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->payment_info }}</p>
                    </div>
                @endif

                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Comparte esta vitrina') }}</h3>
                    <div class="mt-4 space-y-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ route('vitrinas.qr', $business) }}" alt="QR" class="size-28 shrink-0 rounded-2xl border border-zinc-200 bg-white p-2 dark:border-zinc-700" loading="lazy" decoding="async">
                            <p class="max-w-32 text-sm font-medium leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Escanea para compartir') }}</p>
                        </div>

                        <div x-show="shareSupported" x-cloak>
                            <button
                                type="button"
                                x-on:click="navigator.share({
                                        title: @js($business->name),
                                        text: @js($business->name),
                                        url: @js(route('vitrinas.show', $business)),
                                    }).then(() => fetch('{{ route('vitrinas.compartir', $business) }}', { method: 'POST' })).catch(() => {})"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                            >
                                <flux:icon.share class="size-5" variant="outline" />
                                {{ __('Compartir') }}
                            </button>
                        </div>

                        <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800" x-show="! shareSupported" x-cloak>
                            <span class="min-w-0 flex-1 text-sm leading-5 text-zinc-500 break-words dark:text-zinc-300">{{ parse_url(route('vitrinas.show', $business), PHP_URL_HOST) . parse_url(route('vitrinas.show', $business), PHP_URL_PATH) }}</span>
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText('{{ route('vitrinas.show', $business) }}'); $flux.toast('{{ __('Enlace copiado') }}')"
                                class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-zinc-400 transition hover:bg-white hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                                aria-label="{{ __('Copiar enlace de la vitrina') }}"
                            >
                                <flux:icon.document-duplicate class="size-4" variant="outline" />
                            </button>
                        </div>
                    </div>
                </div>

                @if ($socialLinks->isNotEmpty())
                    <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Síguenos en redes') }}</h3>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-5">
                            @foreach ($socialLinks as $network => $link)
                                <a
                                    href="{{ $link }}"
                                    target="_blank"
                                    class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full transition hover:scale-105"
                                    aria-label="{{ __('Abrir :network', ['network' => ucfirst((string) $network)]) }}"
                                    title="{{ ucfirst((string) $network) }}"
                                >
                                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-50 via-pink-50 to-red-100 text-brand-600 shadow-sm ring-1 ring-rose-100 transition hover:from-rose-100 hover:via-pink-100 hover:to-red-200 hover:text-brand-700 dark:from-rose-500/10 dark:via-pink-500/10 dark:to-red-500/10 dark:text-rose-200 dark:ring-rose-500/20 dark:hover:from-rose-500/20 dark:hover:via-pink-500/20 dark:hover:to-red-500/20">
                                        @if (isset($socialIcons[$network]))
                                            <span class="inline-flex size-6 items-center justify-center">{!! $socialIcons[$network] !!}</span>
                                        @else
                                            <flux:icon.link class="size-5" variant="outline" />
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($business->hoursNote() || $business->hasStructuredSchedule())
                    <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Horario de atención') }}</h3>

                        @if ($business->hoursNote())
                            <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $business->hoursNote() }}</p>
                        @endif

                        @if ($business->hasStructuredSchedule())
                            <div class="mt-4 space-y-2 text-sm">
                                @foreach ($business->scheduleForDisplay() as $day => $state)
                                    <div class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-2 last:border-b-0 last:pb-0 dark:border-zinc-800">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ $day }}</span>
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $state }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Opiniones de clientes') }}</h3>
                    <div class="mt-4 flex items-end gap-3">
                        <div class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $recommendationCount > 0 ? $recommendationCount : 0 }}</div>
                        <div class="pb-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('opiniones publicadas') }}</div>
                    </div>
                    @if ($averageRating !== null)
                        <div class="mt-3 flex items-center gap-2">
                            <div class="flex items-center gap-0.5 text-amber-500">
                                @for ($i = 1; $i <= 5; $i++)
                                    <flux:icon.star class="size-5" :variant="$i <= round($averageRating) ? 'solid' : 'outline'" />
                                @endfor
                            </div>
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ number_format($averageRating, 1) }}</span>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Todavía sin calificaciones.') }}</p>
                    @endif
                    <button type="button" x-on:click="tab = 'opiniones'" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl border border-brand-200 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/10">
                        {{ __('Ver todas las opiniones') }}
                    </button>
                </div>

                @unless ($hasSidebarContent)
                    <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ __('Información del negocio') }}</h3>
                        <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ __('Esta vitrina irá mostrando más detalles a medida que el negocio complete su información pública.') }}</p>
                    </div>
                @endunless
            </aside>
        </div>
    </div>
</x-layouts::cliente>
