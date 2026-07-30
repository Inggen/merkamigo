@php
    $seoDescription = $business->storefront?->description
        ? \Illuminate\Support\Str::limit(strip_tags($business->storefront->description), 160)
        : __(':name en :municipio, con Merkamigo.', ['name' => $business->name, 'municipio' => $business->municipality?->name ?? '']);
    $pageImage = $business->storefront?->coverUrl() ?? $business->logoUrl() ?? asset('icons/icon-512.png');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb(array_values(array_filter([
            ['name' => __('Inicio'), 'url' => route('home')],
            $business->municipality ? ['name' => $business->municipality->name, 'url' => route('plaza.show', $business->municipality)] : null,
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
@endphp

<x-layouts::cliente
    :title="$business->name"
    :description="$seoDescription"
    :image="$pageImage"
    :canonical="route('vitrinas.show', $business)"
    page-schema-type="ProfilePage"
    :page-schema-data="['about' => $business->category?->name]"
    :schema-graph="$schemaGraph"
>
    <div x-data="{ tab: 'inicio' }" class="mx-auto max-w-4xl px-6 py-6">
        <nav class="mb-4 flex flex-wrap items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600" wire:navigate>{{ __('Inicio') }}</a>
            <span>/</span>
            @if ($business->municipality)
                <a href="{{ route('plaza.show', $business->municipality) }}" class="hover:text-brand-600" wire:navigate>{{ $business->municipality->name }}</a>
                <span>/</span>
            @endif
            <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $business->name }}</span>
        </nav>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="h-40 bg-zinc-100 sm:h-56 dark:bg-zinc-800">
                    @if ($business->storefront?->coverUrl())
                    <img src="{{ $business->storefront->coverUrl() }}" class="h-full w-full object-cover" alt="{{ __('Portada de :name', ['name' => $business->name]) }}" loading="eager">
                    @endif
                </div>

            <div class="px-6 pb-6">
                <div class="-mt-10 flex items-end gap-4 sm:-mt-12">
                    <div class="flex size-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-white shadow-sm sm:size-24 dark:border-zinc-900 dark:bg-zinc-900">
                        @if ($business->logoUrl())
                            <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}">
                        @else
                            <flux:icon.building-storefront class="size-8 text-zinc-400" variant="outline" />
                        @endif
                    </div>

                    <div class="flex flex-1 flex-wrap items-center justify-between gap-3 pb-2">
                        <div>
                            <flux:heading size="xl">{{ $business->name }}</flux:heading>
                            <div class="mt-1 flex flex-wrap gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($business->hasVerifiedBadge())
                                    <flux:badge size="sm" color="green">
                                        {{ $business->verifiedBadgeLabel() }}
                                    </flux:badge>
                                @endif
                                @if ($business->category)
                                    <flux:badge size="sm">{{ $business->category->name }}</flux:badge>
                                @endif
                                @if ($business->municipality)
                                    <flux:badge size="sm">{{ $business->municipality->name }}{{ $business->zone ? ' · '.$business->zone : '' }}</flux:badge>
                                @endif
                                @if ($business->confirmedOrdersCount() > 0)
                                    <flux:badge size="sm" color="zinc">
                                        {{ trans_choice(':count pedido confirmado|:count pedidos confirmados', $business->confirmedOrdersCount(), ['count' => $business->confirmedOrdersCount()]) }}
                                    </flux:badge>
                                @endif
                                @if (($isOpenNow = $business->isOpenNow()) !== null)
                                    <flux:badge size="sm" :color="$isOpenNow ? 'green' : 'red'">
                                        {{ $isOpenNow ? __('Abierto ahora') : __('Cerrado ahora') }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <livewire:favorite-button :favoritable="$business" :key="'business-'.$business->id" />

                            <flux:button
                                x-on:click="navigator.clipboard.writeText(window.location.href); fetch('{{ route('vitrinas.compartir', $business) }}', { method: 'POST' }); $flux.toast('{{ __('Enlace copiado') }}')"
                                variant="ghost"
                                icon="share"
                            >
                                {{ __('Compartir') }}
                            </flux:button>

                            @if ($business->whatsapp_number)
                                <flux:button
                                    href="{{ route('vitrinas.whatsapp', $business) }}"
                                    target="_blank"
                                    variant="primary"
                                    icon="chat-bubble-left-right"
                                >
                                    {{ __('WhatsApp') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-6 border-b border-zinc-200 text-sm font-medium dark:border-zinc-700">
                    <button type="button" x-on:click="tab = 'inicio'" :class="tab === 'inicio' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Inicio') }}</button>
                    <button type="button" x-on:click="tab = 'productos'" :class="tab === 'productos' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Productos') }}</button>
                    <button type="button" x-on:click="tab = 'opiniones'" :class="tab === 'opiniones' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Opiniones') }}</button>
                    <button type="button" x-on:click="tab = 'informacion'" :class="tab === 'informacion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Información') }}</button>
                </div>

                <div class="py-6">
                    <div x-show="tab === 'inicio'">
                        @if ($business->storefront?->description)
                            <flux:text class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $business->storefront->description }}</flux:text>
                        @endif

                        @php
                            $businessAttributes = $business->activeAttributes();
                            $galleryPhotos = $products->flatMap(fn ($product) => $product->media)->take(12);
                        @endphp

                        @if ($businessAttributes->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($businessAttributes as $attribute)
                                    <flux:badge size="sm" color="zinc">{{ $attribute->name }}</flux:badge>
                                @endforeach
                            </div>
                        @endif

                        @if ($galleryPhotos->isNotEmpty())
                            <flux:subheading class="mt-6 mb-3">{{ __('Galería') }}</flux:subheading>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                @foreach ($galleryPhotos as $photo)
                                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $business->name }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($products->isNotEmpty())
                            <flux:subheading class="mt-6 mb-3">{{ __('Productos destacados') }}</flux:subheading>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                @foreach ($products->take(6) as $product)
                                    @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'productos'" x-cloak>
                        @if ($products->isEmpty())
                            <x-states.empty title="{{ __('Todavía no hay productos publicados') }}" />
                        @else
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                @foreach ($products as $product)
                                    @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'opiniones'" x-cloak>
                        @php($recommendations = $business->publishedRecommendations())

                        @if ($recommendations->isEmpty())
                            <x-states.empty
                                title="{{ __('Todavía no hay opiniones') }}"
                                description="{{ __('Las recomendaciones aparecerán cuando existan interacciones elegibles y moderadas.') }}"
                            />
                        @else
                            <div class="space-y-4">
                                @foreach ($recommendations as $recommendation)
                                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-carbon dark:text-white">
                                                {{ $recommendation->authorUser?->name ?? __('Cliente verificado') }}
                                            </span>
                                            @foreach ($recommendation->tags ?? [] as $tag)
                                                <flux:badge size="sm" color="zinc">{{ $tag }}</flux:badge>
                                            @endforeach
                                        </div>

                                        <flux:text class="mt-2 whitespace-pre-line text-zinc-600 dark:text-zinc-300">
                                            {{ $recommendation->body }}
                                        </flux:text>

                                        @if ($recommendation->business_response)
                                            <div class="mt-3 rounded-xl bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                <span class="font-medium text-carbon dark:text-white">{{ __('Respuesta del negocio:') }}</span>
                                                {{ $recommendation->business_response }}
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'informacion'" x-cloak class="space-y-4">
                        @if ($business->hoursNote())
                            <div>
                                <flux:subheading>{{ __('Horario') }}</flux:subheading>
                                <flux:text>{{ $business->hoursNote() }}</flux:text>
                            </div>
                        @endif

                        @if ($business->hasStructuredSchedule())
                            <div>
                                <flux:subheading>{{ __('Horario por día') }}</flux:subheading>
                                <div class="mt-1 space-y-0.5 text-sm">
                                    @foreach ($business->scheduleForDisplay() as $day => $state)
                                        <div class="flex justify-between gap-4">
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ $day }}</span>
                                            <span>{{ $state }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($business->address)
                            <div>
                                <flux:subheading>{{ __('Dirección') }}</flux:subheading>
                                <flux:text>{{ $business->address }}</flux:text>
                            </div>
                        @endif

                        @if (! empty(array_filter($business->social_links ?? [])))
                            <div>
                                <flux:subheading>{{ __('Redes sociales') }}</flux:subheading>
                                <div class="flex flex-col gap-1">
                                    @foreach (array_filter($business->social_links ?? []) as $link)
                                        <a href="{{ $link }}" target="_blank" class="text-brand-600 hover:underline">{{ $link }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($business->payment_info)
                            <div>
                                <flux:subheading>{{ __('Información de pago') }}</flux:subheading>
                                <flux:text class="whitespace-pre-line">{{ $business->payment_info }}</flux:text>
                            </div>
                        @endif

                        @if ($business->hasVerifiedBadge())
                            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-100">
                                <div class="font-medium">{{ $business->verifiedBadgeLabel() }}</div>
                                <div class="mt-1">
                                    {{ __('Esta insignia confirma una revisión básica de identidad o documentos del negocio. No implica garantía de calidad, pago ni entrega por parte de Merkamigo.') }}
                                </div>
                            </div>
                        @endif

                        <a href="{{ route('reportes.crear.negocio', $business) }}" class="text-sm text-zinc-400 hover:text-zinc-600" wire:navigate>
                            {{ __('Reportar este negocio') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::cliente>
