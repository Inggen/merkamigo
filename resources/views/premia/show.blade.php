@php
    $gallery = collect();

    if ($reward->imageUrl()) {
        $gallery->push([
            'url' => $reward->imageUrl(),
            'alt' => $reward->title,
            'label' => __('Promoción'),
        ]);
    }

    foreach ($reward->product?->media?->where('type', 'image') ?? [] as $media) {
        $gallery->push([
            'url' => $media->url(),
            'alt' => $media->alt_text ?? $reward->product->name,
            'label' => __('Producto'),
        ]);
    }

    $photo = $gallery->first();
    $available = $reward->stockAvailable();
    $policy = $business->loyaltyPolicies()->where('status', \App\Domain\Loyalty\Models\LoyaltyPolicy::ACTIVA)->first();
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Merkapuntos'), 'url' => route('premia.index')],
            ['name' => $business->name, 'url' => route('vitrinas.show', $business)],
            ['name' => $reward->title],
        ]),
    ];
@endphp

<x-layouts::cliente
    :title="$reward->title.' · '.$business->name"
    :description="$reward->description ?? __('Canjea :title en :business con Merkamigo Premia.', ['title' => $reward->title, 'business' => $business->name])"
    :image="$photo['url'] ?? null"
    :canonical="route('premia.show', $reward)"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Ruta de navegación') }}">
            <a href="{{ route('home') }}" class="flex items-center rounded-full p-1 hover:bg-zinc-100 hover:text-brand-600 dark:hover:bg-zinc-800" wire:navigate aria-label="{{ __('Inicio') }}">
                <flux:icon.home class="size-4" variant="outline" />
            </a>
            <flux:icon.chevron-right class="size-3.5 shrink-0 text-zinc-300 dark:text-zinc-600" variant="outline" />
            <a href="{{ route('premia.index') }}" class="shrink-0 hover:text-brand-600" wire:navigate>{{ __('Merkapuntos') }}</a>
            <flux:icon.chevron-right class="size-3.5 shrink-0 text-zinc-300 dark:text-zinc-600" variant="outline" />
            <a href="{{ route('vitrinas.show', $business) }}" class="min-w-0 truncate hover:text-brand-600" wire:navigate>{{ $business->name }}</a>
            <flux:icon.chevron-right class="size-3.5 shrink-0 text-zinc-300 dark:text-zinc-600" variant="outline" />
            <span class="min-w-0 truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ $reward->title }}</span>
        </nav>

        @if (session('error'))
            <div class="mb-6 rounded-xl bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_380px] xl:grid-cols-[minmax(0,1fr)_400px]">
            <div>
                <div x-data="{ active: 0 }" class="space-y-3">
                    <div class="relative aspect-[3/2] overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800">
                        @forelse ($gallery as $index => $image)
                            <img
                                x-show="active === {{ $index }}"
                                x-transition.opacity
                                src="{{ $image['url'] }}"
                                class="absolute inset-0 size-full object-cover"
                                alt="{{ $image['alt'] }}"
                                @if ($index === 0) loading="eager" @else loading="lazy" @endif
                                decoding="async"
                            >
                        @empty
                            <div class="flex h-full w-full items-center justify-center">
                                <flux:icon.gift class="size-16 text-zinc-300 dark:text-zinc-700" variant="outline" />
                            </div>
                        @endforelse

                        @if ($gallery->count() > 1)
                            <button
                                type="button"
                                x-on:click="active = (active - 1 + {{ $gallery->count() }}) % {{ $gallery->count() }}"
                                class="absolute left-3 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-700 shadow-sm backdrop-blur transition hover:bg-white dark:bg-zinc-900/90 dark:text-zinc-200"
                                aria-label="{{ __('Imagen anterior') }}"
                            >
                                <flux:icon.chevron-left class="size-5" variant="outline" />
                            </button>
                            <button
                                type="button"
                                x-on:click="active = (active + 1) % {{ $gallery->count() }}"
                                class="absolute right-3 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-700 shadow-sm backdrop-blur transition hover:bg-white dark:bg-zinc-900/90 dark:text-zinc-200"
                                aria-label="{{ __('Imagen siguiente') }}"
                            >
                                <flux:icon.chevron-right class="size-5" variant="outline" />
                            </button>
                            <span class="absolute bottom-3 right-3 rounded-full bg-zinc-950/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur" x-text="`${active + 1} / {{ $gallery->count() }}`"></span>
                        @endif
                    </div>

                    @if ($gallery->count() > 1)
                        <div class="flex gap-2 overflow-x-auto pb-1" aria-label="{{ __('Galería del premio') }}">
                            @foreach ($gallery as $index => $image)
                                <button type="button" x-on:click="active = {{ $index }}" class="relative size-20 shrink-0 overflow-hidden rounded-xl border-2 transition" :class="active === {{ $index }} ? 'border-brand-500' : 'border-transparent'" aria-label="{{ __('Ver imagen :number', ['number' => $index + 1]) }}">
                                    <img src="{{ $image['url'] }}" class="size-full object-cover" alt="">
                                    @if ($index === 0 && $reward->imageUrl())
                                        <span class="absolute inset-x-1 bottom-1 rounded bg-zinc-950/75 px-1 py-0.5 text-[0.6rem] font-bold text-white">{{ __('Promo') }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-3">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <h1 class="min-w-0 text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ $reward->title }}</h1>
                        <x-owner-edit-link
                            :business="$business"
                            :href="route('emprendedores.negocios.merkapuntos', $business).'#premio-'.$reward->id"
                            :label="__('Editar premio')"
                            compact
                        />
                    </div>
                    <p class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                        <span class="inline-flex items-center gap-1">
                            {{ $business->name }}
                            @if ($business->hasVerifiedBadge())
                                <flux:icon.check-badge class="size-4 text-brand-600 dark:text-brand-300" variant="solid" />
                            @endif
                        </span>
                        @if ($business->municipality)
                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                            <span class="inline-flex items-center gap-1">
                                <flux:icon.map-pin class="size-4 text-zinc-400" variant="outline" />
                                {{ $business->municipality->name }}
                            </span>
                        @endif
                    </p>

                    @if ($reward->description)
                        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $reward->description }}</p>
                    @endif

                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <div class="flex items-start gap-2">
                            <flux:icon.gift class="size-4 shrink-0 text-brand-500" variant="outline" />
                            <div>
                                <p class="text-[11px] text-zinc-400">{{ __('Disponibles') }}</p>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $available === null ? __('Sin límite') : $available }}</p>
                            </div>
                        </div>
                        @if ($reward->valid_until)
                            <div class="flex items-start gap-2">
                                <flux:icon.calendar-days class="size-4 shrink-0 text-brand-500" variant="outline" />
                                <div>
                                    <p class="text-[11px] text-zinc-400">{{ __('Vigente hasta') }}</p>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $reward->valid_until->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($policy)
                            <div class="col-span-2 flex items-start gap-2">
                                <flux:icon.circle-stack class="size-4 shrink-0 text-brand-500" variant="outline" />
                                <div>
                                    <p class="text-[11px] text-zinc-400">{{ __('Cómo acumular aquí') }}</p>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __(':points pt. por cada $:unit', ['points' => $policy->rule['points_per_unit'], 'unit' => number_format($policy->rule['unit_cents'] / 100, 0, ',', '.')]) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($reward->terms)
                        <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white">{{ __('Condiciones') }}</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $reward->terms }}</p>
                        </div>
                    @endif
                </section>

                {{--
                    El saldo queda junto al resumen del premio para que el
                    cliente compare de inmediato sus puntos con el costo.
                --}}
                <div class="relative overflow-hidden rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-white p-5 dark:border-rose-900/40 dark:from-rose-950/30 dark:via-zinc-900 dark:to-zinc-900">
                    <flux:icon.gift class="pointer-events-none absolute -right-3 -top-3 size-24 rotate-12 text-rose-100 dark:text-rose-900/40" variant="solid" />

                    <div class="relative">
                        @auth
                            <p class="flex items-center gap-1.5 text-sm font-semibold text-rose-600 dark:text-rose-300">
                                <flux:icon.star class="size-4" variant="solid" />
                                {{ __('Tus Merkapuntos') }}
                            </p>
                            <p class="mt-1 text-4xl font-black text-zinc-950 dark:text-white">{{ number_format($customerPoints ?? 0, 0, ',', '.') }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon.sparkles class="size-4 text-brand-500" variant="solid" />
                                {{ __('Este premio cuesta :points puntos', ['points' => $reward->points_cost]) }}
                            </p>
                            {{-- Pedido del usuario: enlace directo para ver el
                                resumen completo de Merkapuntos (saldo por cada
                                negocio, canjes activos, historial) — este
                                número de acá es solo el saldo en ESTE negocio. --}}
                            <flux:link :href="route('merkapuntos')" wire:navigate class="mt-1 inline-flex items-center gap-1 text-xs font-semibold">
                                {{ __('Ver todos mis Merkapuntos') }}
                                <flux:icon.arrow-right class="size-3" variant="outline" />
                            </flux:link>
                        @else
                            <p class="flex items-center gap-1.5 text-2xl font-bold text-brand-600 dark:text-brand-300">
                                <flux:icon.sparkles class="size-6" variant="solid" />
                                {{ __(':points Merkapuntos', ['points' => $reward->points_cost]) }}
                            </p>
                        @endauth

                        @if ($available === 0)
                            <flux:button class="mt-4 w-full" variant="ghost" disabled>{{ __('Agotado') }}</flux:button>
                        @else
                            <flux:button :href="route('premia.redeem', $reward)" class="mt-4 w-full" variant="primary" icon="gift">
                                {{ auth()->check() ? __('Canjear mis puntos') : __('Inicia sesión para canjear') }}
                            </flux:button>
                            <flux:text class="mt-2 text-center text-xs text-zinc-400">
                                {{ __('Se reservan tus puntos al confirmar; el negocio valida la entrega.') }}
                            </flux:text>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="mb-3 flex items-center gap-3">
                        <flux:avatar :src="$business->logoUrl()" :name="$business->name" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-zinc-900 dark:text-white">
                                {{ $business->name }}
                                @if ($business->hasVerifiedBadge())
                                    <flux:icon.check-badge class="inline size-4 text-brand-600 dark:text-brand-300" variant="solid" />
                                @endif
                            </p>
                            @if ($business->category)
                                <p class="truncate text-xs text-zinc-500">{{ $business->category->name }}</p>
                            @endif
                            @if ($business->municipality)
                                <p class="flex items-center gap-1 truncate text-xs text-zinc-500">
                                    <flux:icon.map-pin class="size-3.5 shrink-0" variant="outline" />
                                    {{ $business->municipality->name }}
                                </p>
                            @endif
                        </div>
                        <flux:icon.chevron-right class="size-4 shrink-0 text-zinc-300" variant="outline" />
                    </a>

                    <div class="flex items-center gap-2">
                        <flux:button :href="route('vitrinas.show', $business)" variant="ghost" class="flex-1" wire:navigate>
                            {{ __('Ver perfil del negocio') }}
                        </flux:button>
                        <livewire:favorite-button :favoritable="$business" :compact="true" :key="'favorite-business-'.$business->id" />
                        <div x-data="{ shareSupported: typeof navigator !== 'undefined' && !! navigator.share }">
                            <button
                                type="button"
                                x-show="shareSupported"
                                x-on:click="navigator.share({ title: @js($reward->title), text: @js($reward->title.' · '.$business->name), url: @js(route('premia.show', $reward)) }).catch(() => {})"
                                class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition hover:text-brand-600 dark:bg-zinc-800 dark:text-zinc-300"
                                aria-label="{{ __('Compartir') }}"
                            >
                                <flux:icon.share class="size-4" variant="outline" />
                            </button>
                            <button
                                type="button"
                                x-show="! shareSupported"
                                x-cloak
                                x-on:click="navigator.clipboard.writeText(@js(route('premia.show', $reward))); $flux.toast(@js(__('Enlace copiado')))"
                                class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition hover:text-brand-600 dark:bg-zinc-800 dark:text-zinc-300"
                                aria-label="{{ __('Copiar enlace') }}"
                            >
                                <flux:icon.share class="size-4" variant="outline" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl bg-amber-50 px-3 py-2.5 dark:bg-amber-500/10">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                            <flux:icon.trophy class="size-4" variant="solid" />
                            {{ __('Este negocio participa en Merkapuntos') }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-amber-900/75 dark:text-amber-200/80">
                            {{ __('Acumula puntos con tus compras y canjéalos por productos y beneficios exclusivos.') }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <div class="mt-10">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-zinc-950 dark:text-white">
                    <flux:icon.gift class="size-5 text-brand-600" variant="solid" />
                    {{ __('También te puede interesar') }}
                </h2>

                <div x-data="{ scroll(dir) { $refs.track.scrollBy({ left: dir * $refs.track.clientWidth * 0.8, behavior: 'smooth' }) } }" class="relative">
                    <div x-ref="track" class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ($related as $other)
                            <div class="w-48 shrink-0 snap-start sm:w-56">
                                @include('premia.partials.reward-card', ['reward' => $other])
                            </div>
                        @endforeach
                    </div>

                    @if ($related->count() > 2)
                        <button type="button" x-on:click="scroll(-1)" class="absolute -left-3 top-1/3 hidden size-9 items-center justify-center rounded-full bg-white text-zinc-700 shadow-md ring-1 ring-zinc-200 transition hover:bg-zinc-50 sm:flex dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700" aria-label="{{ __('Anterior') }}">
                            <flux:icon.chevron-left class="size-5" variant="outline" />
                        </button>
                        <button type="button" x-on:click="scroll(1)" class="absolute -right-3 top-1/3 hidden size-9 items-center justify-center rounded-full bg-white text-zinc-700 shadow-md ring-1 ring-zinc-200 transition hover:bg-zinc-50 sm:flex dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700" aria-label="{{ __('Siguiente') }}">
                            <flux:icon.chevron-right class="size-5" variant="outline" />
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts::cliente>
