@php($rewardPages = $featuredRewards->chunk(2)->values())

<section
    x-data="{
        active: 0,
        count: {{ $rewardPages->count() }},
        timer: null,
        next() { this.active = (this.active + 1) % this.count },
        previous() { this.active = (this.active - 1 + this.count) % this.count },
        start() {
            if (this.count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
            this.stop()
            this.timer = window.setInterval(() => this.next(), 5000)
        },
        stop() {
            if (this.timer) window.clearInterval(this.timer)
            this.timer = null
        },
    }"
    x-init="start()"
    x-on:mouseenter="stop()"
    x-on:mouseleave="start()"
    class="group relative overflow-hidden rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-white shadow-sm dark:border-rose-900/40 dark:from-rose-950/30 dark:via-zinc-900 dark:to-zinc-900"
    aria-label="{{ __('Recompensas destacadas') }}"
>
    <flux:icon.gift class="pointer-events-none absolute -right-3 -top-3 size-24 rotate-12 text-rose-100 dark:text-rose-900/40" variant="solid" />

    <div class="relative z-10 flex items-center justify-between gap-3 px-4 pb-3 pt-4">
        <div class="flex min-w-0 items-center gap-2 text-brand-600 dark:text-brand-300">
            <flux:icon.gift class="size-5 shrink-0" variant="solid" />
            <p class="text-sm font-bold leading-tight">{{ __('Acumula puntos y gana') }}</p>
        </div>
        <flux:link :href="route('premia.index')" wire:navigate class="shrink-0 text-xs">{{ __('Ver todas') }}</flux:link>
    </div>

    <div class="relative z-10 px-4">
        @foreach ($rewardPages as $pageIndex => $rewards)
            <div
                x-show="active === {{ $pageIndex }}"
                x-transition.opacity.duration.300ms
                @if ($pageIndex > 0) x-cloak @endif
                @class([
                    'grid gap-2.5',
                    'grid-cols-2' => $rewards->count() > 1,
                    'grid-cols-1' => $rewards->count() === 1,
                ])
            >
                @foreach ($rewards as $reward)
                    @php($photoUrl = $reward->imageUrl() ?? $reward->product?->primaryImage()?->url())
                    <a
                        href="{{ route('premia.show', $reward) }}"
                        wire:navigate
                        class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:border-brand-300 dark:border-zinc-700 dark:bg-zinc-950"
                        aria-label="{{ __('Ver recompensa :reward', ['reward' => $reward->title]) }}"
                    >
                        <div @class([
                            'overflow-hidden bg-zinc-100 dark:bg-zinc-800',
                            'aspect-square' => $rewards->count() > 1,
                            'aspect-[16/8]' => $rewards->count() === 1,
                        ])>
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" class="size-full object-cover transition duration-300 hover:scale-[1.03]" alt="{{ $reward->title }}" loading="lazy" decoding="async">
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-50 to-red-50 dark:from-brand-950 dark:to-zinc-900">
                                    <flux:icon.gift class="size-8 text-brand-300 dark:text-brand-700" variant="outline" />
                                </div>
                            @endif
                        </div>
                        <div class="space-y-1 p-2.5">
                            <p @class([
                                'line-clamp-2 text-sm font-semibold leading-5 text-zinc-950 dark:text-white',
                                'min-h-10' => $rewards->count() > 1,
                            ])>{{ $reward->title }}</p>
                            <p class="flex items-center gap-1 text-sm font-bold text-brand-600 dark:text-brand-300">
                                <flux:icon.gift class="size-4 shrink-0" variant="solid" />
                                {{ __(':points pts', ['points' => number_format($reward->points_cost, 0, ',', '.')]) }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach

        @if ($rewardPages->count() > 1)
            <button type="button" x-on:click="previous(); start()" class="absolute -left-1 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-zinc-900 opacity-0 shadow-md transition hover:bg-white focus-visible:opacity-100 group-hover:opacity-100" aria-label="{{ __('Recompensas anteriores') }}">
                <flux:icon.chevron-left class="size-5" />
            </button>
            <button type="button" x-on:click="next(); start()" class="absolute -right-1 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-zinc-900 opacity-0 shadow-md transition hover:bg-white focus-visible:opacity-100 group-hover:opacity-100" aria-label="{{ __('Siguientes recompensas') }}">
                <flux:icon.chevron-right class="size-5" />
            </button>
        @endif
    </div>

    @if ($rewardPages->count() > 1)
        <div class="relative z-10 flex justify-center gap-1.5 py-3" aria-label="{{ __('Seleccionar grupo de recompensas') }}">
            @foreach ($rewardPages as $pageIndex => $rewards)
                <button
                    type="button"
                    x-on:click="active = {{ $pageIndex }}; start()"
                    class="size-2 rounded-full transition"
                    x-bind:class="active === {{ $pageIndex }} ? 'bg-brand-600' : 'bg-zinc-300 dark:bg-zinc-700'"
                    aria-label="{{ __('Ver grupo :page', ['page' => $pageIndex + 1]) }}"
                ></button>
            @endforeach
        </div>
    @else
        <div class="h-4"></div>
    @endif
</section>
