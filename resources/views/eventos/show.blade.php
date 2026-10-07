@php
    $pageTitle = $event->title;
    $business = $event->business;
    $recommendations = $business?->publishedRecommendations() ?? collect();
    $ratedRecommendations = $recommendations->whereNotNull('rating');
    $averageRating = $ratedRecommendations->isNotEmpty() ? round($ratedRecommendations->avg('rating'), 1) : null;
    $categoryIcons = [
        'musica' => 'musical-note',
        'taller' => 'user-group',
        'mercado' => 'shopping-bag',
        'gastronomia' => 'sparkles',
        'arte' => 'paint-brush',
        'deporte' => 'trophy',
        'otro' => 'calendar-days',
    ];
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Eventos'), 'url' => route('eventos')],
            ['name' => $event->title],
        ]),
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="\Illuminate\Support\Str::limit(strip_tags($event->description ?? ''), 155)"
    :canonical="route('eventos.show', $event)"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Ruta de navegación') }}">
            <a href="{{ route('home') }}" class="rounded-full p-1 transition hover:bg-zinc-100 hover:text-brand-600 dark:hover:bg-zinc-800" wire:navigate aria-label="{{ __('Inicio') }}">
                <flux:icon.home class="size-4" variant="outline" />
            </a>
            <flux:icon.chevron-right class="size-3.5 text-zinc-300" />
            <a href="{{ route('eventos') }}" wire:navigate class="hover:text-brand-700">{{ __('Eventos') }}</a>
            @if ($event->municipality)
                <flux:icon.chevron-right class="size-3.5 text-zinc-300" />
                <span>{{ $event->municipality->name }}</span>
            @endif
            <flux:icon.chevron-right class="size-3.5 text-zinc-300" />
            <span class="truncate font-semibold text-zinc-900 dark:text-white">{{ $event->title }}</span>
        </nav>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px] xl:grid-cols-[minmax(0,1fr)_400px]">
            <main class="space-y-4">
                <section class="relative min-h-[28rem] overflow-hidden rounded-2xl bg-zinc-950 shadow-sm md:min-h-[22rem]" aria-labelledby="event-title">
                    @if ($event->coverUrl())
                        <img src="{{ $event->coverUrl() }}" alt="{{ $event->title }}" class="absolute inset-0 size-full object-cover" loading="eager" decoding="async">
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-brand-950 via-rose-900 to-amber-700"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/55 to-black/10"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20"></div>

                    <div class="absolute inset-x-0 top-0 z-10 flex items-start justify-between gap-3 p-5 sm:p-7">
                        @if ($event->categoryLabel())
                            <span class="inline-flex items-center rounded-full border border-white/60 bg-black/25 px-4 py-2 text-xs font-semibold text-white backdrop-blur">
                                {{ $event->categoryLabel() }}
                            </span>
                        @else
                            <span></span>
                        @endif

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                x-data
                                x-on:click="navigator.share ? navigator.share({ title: @js($event->title), url: @js(route('eventos.show', $event)) }) : navigator.clipboard.writeText(@js(route('eventos.show', $event))).then(() => $flux.toast(@js(__('Enlace copiado'))))"
                                class="inline-flex size-10 items-center justify-center rounded-full bg-white/95 text-zinc-900 shadow transition hover:scale-105"
                                aria-label="{{ __('Compartir evento') }}"
                            >
                                <flux:icon.share class="size-5" variant="outline" />
                            </button>
                            @if ($business)
                                <x-owner-edit-link
                                    :business="$business"
                                    :href="route('emprendedores.negocios.eventos', $business).'#evento-'.$event->id"
                                    :label="__('Editar evento')"
                                    compact
                                    class="bg-white/95 text-zinc-900 shadow hover:scale-105"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 z-10 max-w-2xl p-6 text-white sm:p-8">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg">
                                <flux:icon :name="$categoryIcons[$event->category] ?? 'calendar-days'" class="size-6" variant="solid" />
                            </span>
                            <h1 id="event-title" class="text-3xl font-black tracking-tight sm:text-5xl">{{ $event->title }}</h1>
                        </div>
                        @if ($business)
                            <p class="mt-3 text-lg font-semibold text-white/95">{{ $business->name }}</p>
                        @endif
                        @if ($event->description)
                            <p class="mt-2 line-clamp-3 max-w-xl text-sm leading-6 text-white/85 sm:text-base">{{ $event->description }}</p>
                        @endif

                        <div class="mt-5 grid gap-2 text-sm font-medium sm:grid-cols-2">
                            <span class="inline-flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-brand-600"><flux:icon.calendar-days class="size-4" /></span>
                                {{ $event->starts_at->translatedFormat('D, j \d\e F \d\e Y') }}
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-brand-600"><flux:icon.clock class="size-4" /></span>
                                {{ $event->starts_at->format('g:i a') }}@if ($event->ends_at) – {{ $event->ends_at->format('g:i a') }}@endif
                            </span>
                            @if ($event->location_text || $event->municipality)
                                <span class="inline-flex items-center gap-2 sm:col-span-2">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-brand-600"><flux:icon.map-pin class="size-4" /></span>
                                    {{ collect([$event->location_text, $event->municipality?->name, $event->municipality?->department])->filter()->unique()->join(', ') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </section>

                @if ($business)
                    <section class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-900">
                        <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-4">
                            <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-100 bg-white dark:border-zinc-700 dark:bg-zinc-950">
                                @if ($business->logoUrl())
                                    <img src="{{ $business->logoUrl() }}" alt="{{ $business->name }}" class="size-full object-cover">
                                @else
                                    <flux:icon.building-storefront class="size-7 text-zinc-400" />
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-zinc-950 dark:text-white">{{ $business->name }}</h2>
                                <p class="text-sm text-zinc-500">{{ $business->municipality?->name }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    @if ($business->hasVerifiedBadge())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            <flux:icon.shield-check class="size-3.5" variant="solid" />
                                            {{ __('Negocio verificado') }}
                                        </span>
                                    @endif
                                    @if ($averageRating)
                                        <span class="font-semibold text-amber-500">★ {{ number_format($averageRating, 1) }}</span>
                                        <span class="text-zinc-400">({{ trans_choice(':count reseña|:count reseñas', $ratedRecommendations->count(), ['count' => $ratedRecommendations->count()]) }})</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <div class="flex shrink-0 items-center gap-2">
                            <livewire:follow-button :business="$business" compact :key="'follow-event-'.$event->id" />
                            <flux:button :href="route('vitrinas.show', $business)" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-up-right">{{ __('Ver vitrina') }}</flux:button>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <flux:icon.document-text class="size-5" variant="outline" />
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-zinc-950 dark:text-white">{{ __('Acerca del evento') }}</h2>
                            @if ($event->description)
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
                            @else
                                <p class="mt-2 text-sm text-zinc-500">{{ __('Consulta los datos principales y reserva tu cupo para participar.') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 border-t border-zinc-100 pt-5 sm:grid-cols-2 xl:grid-cols-4 dark:border-zinc-800">
                        @foreach ([
                            ['ticket', $event->categoryLabel() ?: __('Evento local'), __('Categoría')],
                            ['map-pin', $event->location_text ?: ($event->municipality?->name ?? __('Por confirmar')), __('Ubicación')],
                            ['users', $event->capacity ? trans_choice(':count cupo|:count cupos', $event->capacity, ['count' => $event->capacity]) : __('Sin límite definido'), __('Capacidad')],
                            ['banknotes', $event->isFree() ? __('Entrada gratuita') : '$'.number_format($event->price_cents / 100, 0, ',', '.').' COP', __('Valor por persona')],
                        ] as [$icon, $value, $label])
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                    <flux:icon :name="$icon" class="size-5" variant="outline" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $value }}</p>
                                    <p class="text-xs text-zinc-500">{{ $label }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="grid gap-4 sm:grid-cols-2">
                    @php($settings = $business?->eventSetting)
                    @if ($settings?->enabled && $settings->private_reservations_enabled)
                        <section class="rounded-2xl border border-brand-200 bg-brand-50/50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm dark:bg-zinc-900 dark:text-brand-300"><flux:icon.calendar-days class="size-5" /></span>
                                <div>
                                    <h2 class="font-bold text-zinc-950 dark:text-white">{{ __('¿Te gustó este lugar?') }}</h2>
                                    <p class="mt-1 text-sm leading-5 text-zinc-600 dark:text-zinc-300">{{ __('También puedes reservarlo para tu propio evento privado.') }}</p>
                                </div>
                            </div>
                            <flux:button :href="route('vitrinas.eventos.reservar', $business)" wire:navigate variant="ghost" class="mt-4 w-full border border-brand-200" icon="calendar-days">
                                {{ __('Reservar este espacio') }}
                            </flux:button>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 {{ ! ($settings?->enabled && $settings->private_reservations_enabled) ? 'sm:col-span-2' : '' }}">
                        <div class="flex items-start gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><flux:icon.heart class="size-5" /></span>
                            <div>
                                <h2 class="font-bold text-zinc-950 dark:text-white">{{ __('Comparte este evento') }}</h2>
                                <p class="text-sm text-zinc-500">{{ __('Invita a tus amigos y apoya lo local.') }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-data
                            x-on:click="navigator.share ? navigator.share({ title: @js($event->title), url: @js(route('eventos.show', $event)) }) : navigator.clipboard.writeText(@js(route('eventos.show', $event))).then(() => $flux.toast(@js(__('Enlace copiado'))))"
                            class="mt-4 inline-flex items-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-200"
                        >
                            <flux:icon.share class="size-4" />
                            {{ __('Compartir') }}
                        </button>
                    </section>
                </div>
            </main>

            <aside class="space-y-4 lg:sticky lg:top-24">
                @if ($event->status === \App\Domain\Events\Models\PublicEvent::FINALIZADO)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:heading size="lg" class="mb-2">{{ __('Este evento ya pasó') }}</flux:heading>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Sigue a este negocio para enterarte de sus próximos eventos.') }}</p>
                    </section>
                @else
                    <livewire:event-attendance-widget :event="$event" :key="'attendance-'.$event->id" />
                @endif

                @if ($business)
                    <a href="{{ route('vitrinas.contact.internal', $business) }}" wire:navigate class="group flex items-center gap-4 rounded-2xl bg-brand-600 p-6 text-white shadow-lg transition hover:bg-brand-700">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                            <flux:icon.chat-bubble-left-right class="size-7" variant="outline" />
                        </span>
                        <span>
                            <strong class="block text-xl">{{ __('Contactar') }}</strong>
                            <span class="mt-0.5 block text-base text-white/85">{{ __('Habla directamente con este negocio') }}</span>
                        </span>
                    </a>
                @endif
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-10">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <flux:heading size="lg">{{ __('Más eventos') }}{{ $event->municipality ? ' '.__('en :municipio', ['municipio' => $event->municipality->name]) : '' }}</flux:heading>
                    <flux:link :href="route('eventos')" wire:navigate>{{ __('Ver todos') }}</flux:link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($related as $other)
                        <a href="{{ route('eventos.show', $other) }}" wire:navigate class="flex items-center gap-4 rounded-2xl border border-zinc-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                @if ($other->coverUrl())
                                    <img src="{{ $other->coverUrl() }}" alt="" class="size-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $other->title }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $other->business?->name }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $other->starts_at->translatedFormat('D j M, g:i a') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts::cliente>
