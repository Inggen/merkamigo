<x-layouts::cliente :title="__('Mis solicitudes')">
    @php
        use App\Domain\Needs\Models\Need;

        $activeTab = request('tab', 'todas');

        $tabs = [
            'todas' => __('Todas'),
            'publicadas' => __('Publicadas'),
            'borradores' => __('Borradores'),
        ];

        $totalNeeds = $needs->count();
        $publishedNeeds = $needs->filter(fn ($need) => $need->isPublished())->count();
        $draftNeeds = $needs->filter(fn ($need) => $need->isDraft())->count();
        $totalOffers = (int) $needs->sum('offers_count');

        $filteredNeeds = match ($activeTab) {
            'publicadas' => $needs->filter(fn ($need) => $need->isPublished()),
            'borradores' => $needs->filter(fn ($need) => $need->isDraft()),
            default => $needs,
        };

        $statusLabel = fn (Need $need) => match ($need->status) {
            Need::BORRADOR => __('Borrador'),
            Need::PUBLICADA => __('Publicada'),
            Need::RECIBIENDO_OFERTAS => __('Recibiendo propuestas'),
            Need::SELECCIONADA => __('Seleccionada'),
            Need::CERRADA => __('Cerrada'),
            Need::VENCIDA => __('Vencida'),
            Need::CANCELADA => __('Cancelada'),
            default => $need->status,
        };

        $statusClasses = fn (Need $need) => match ($need->status) {
            Need::BORRADOR => 'bg-zinc-100 text-zinc-700',
            Need::PUBLICADA, Need::RECIBIENDO_OFERTAS => 'bg-emerald-100 text-emerald-700',
            Need::SELECCIONADA => 'bg-sky-100 text-sky-700',
            default => 'bg-rose-100 text-rose-700',
        };

        $cardIcon = fn (Need $need) => $need->category ? 'cake' : 'document-text';
        $cardIconShell = fn (Need $need) => $need->isDraft()
            ? 'bg-zinc-100 text-zinc-600'
            : 'bg-brand-50 text-brand-600';

        $summaryCards = [
            [
                'icon' => 'document-text',
                'shell' => 'bg-rose-50 text-rose-600',
                'value' => trans_choice(':count solicitud|:count solicitudes', $totalNeeds, ['count' => $totalNeeds]),
            ],
            [
                'icon' => 'check-circle',
                'shell' => 'bg-emerald-50 text-emerald-600',
                'value' => trans_choice(':count publicada|:count publicadas', $publishedNeeds, ['count' => $publishedNeeds]),
            ],
            [
                'icon' => 'chat-bubble-left-right',
                'shell' => 'bg-sky-50 text-sky-600',
                'value' => trans_choice(':count propuesta|:count propuestas', $totalOffers, ['count' => $totalOffers]),
            ],
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-6 py-10 sm:px-8 lg:px-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-5xl">
                    {{ __('Mis solicitudes') }}
                </h1>
                <p class="max-w-2xl text-lg leading-8 text-zinc-500 dark:text-zinc-400">
                    {{ __('Administra lo que estás buscando y revisa las propuestas recibidas.') }}
                </p>
            </div>

            <a
                href="{{ route('pidelo.nueva') }}"
                wire:navigate
                class="inline-flex items-center justify-center gap-3 rounded-2xl bg-brand-600 px-7 py-4 text-lg font-semibold text-white shadow-[0_18px_40px_rgba(220,38,38,0.22)] transition hover:bg-brand-700"
            >
                <flux:icon.plus class="size-6" variant="outline" />
                <span>{{ __('Nueva solicitud') }}</span>
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-[0_18px_60px_rgba(15,23,42,0.06)] dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-0 md:grid-cols-3">
                @foreach ($summaryCards as $card)
                    <div class="flex items-center gap-5 px-7 py-8 {{ ! $loop->last ? 'border-b border-zinc-200 md:border-b-0 md:border-r dark:border-zinc-800' : '' }}">
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $card['shell'] }}">
                            <flux:icon :name="$card['icon']" class="size-7" variant="outline" />
                        </span>
                        <div class="text-2xl font-medium tracking-tight text-zinc-700 dark:text-zinc-200">
                            {{ $card['value'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8 inline-flex w-full flex-wrap overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:w-auto">
            @foreach ($tabs as $tabKey => $tabLabel)
                @php
                    $tabCount = match ($tabKey) {
                        'publicadas' => $publishedNeeds,
                        'borradores' => $draftNeeds,
                        default => $totalNeeds,
                    };
                    $isActive = $activeTab === $tabKey;
                @endphp

                <a
                    href="{{ route('mis-solicitudes', ['tab' => $tabKey]) }}"
                    wire:navigate
                    class="inline-flex min-w-40 items-center justify-center gap-2 border-r border-zinc-200 px-6 py-4 text-xl font-medium transition last:border-r-0 dark:border-zinc-800 {{ $isActive ? 'bg-brand-50 text-brand-700' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}"
                >
                    <span>{{ $tabLabel }}</span>
                    <span class="{{ $isActive ? 'text-brand-600' : 'text-zinc-400 dark:text-zinc-500' }}">{{ $tabCount }}</span>
                </a>
            @endforeach
        </div>

        <section class="mt-6">
            <h2 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">
                {{ __('Tus solicitudes') }}
            </h2>

            @if ($filteredNeeds->isEmpty())
                <div class="mt-5 rounded-[28px] border border-zinc-200 bg-white px-8 py-12 text-center shadow-[0_18px_60px_rgba(15,23,42,0.06)] dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.document-text class="size-8" variant="outline" />
                    </div>
                    <h3 class="mt-5 text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ __('No hay solicitudes en esta vista') }}
                    </h3>
                    <p class="mx-auto mt-3 max-w-xl text-lg leading-8 text-zinc-500 dark:text-zinc-400">
                        {{ __('Cambia de pestaña o publica una nueva solicitud para empezar a recibir propuestas cercanas.') }}
                    </p>
                </div>
            @else
                <div class="mt-5 space-y-4">
                    @foreach ($filteredNeeds as $need)
                        @php
                            $title = filled($need->title) ? $need->title : __('Solicitud sin título');
                            $subtitle = $need->isDraft()
                                ? __('Aún no has completado esta solicitud.')
                                : ($need->category?->name ?: __('Sin categoría'));
                            $meta = $need->isDraft()
                                ? null
                                : ($need->published_at?->diffForHumans() ? __('Publicada :time', ['time' => $need->published_at->diffForHumans()]) : null);
                            $cta = $need->isDraft() ? __('Continuar borrador') : __('Ver solicitud');
                        @endphp

                        <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-[0_18px_60px_rgba(15,23,42,0.06)] dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                                <div class="flex min-w-0 items-start gap-5">
                                    <span class="flex size-20 shrink-0 items-center justify-center rounded-[24px] {{ $cardIconShell($need) }}">
                                        <flux:icon :name="$cardIcon($need)" class="size-10" variant="outline" />
                                    </span>

                                    <div class="min-w-0 space-y-1.5">
                                        <h3 class="truncate text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">
                                            {{ $title }}
                                        </h3>
                                        <p class="text-xl text-zinc-600 dark:text-zinc-300">{{ $subtitle }}</p>

                                        @if ($meta)
                                            <p class="text-lg text-zinc-400 dark:text-zinc-500">{{ $meta }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col gap-4 xl:min-w-[520px] xl:flex-row xl:items-center xl:justify-end">
                                    <div class="flex items-center gap-3 text-xl text-zinc-500 dark:text-zinc-400">
                                        <flux:icon.chat-bubble-left-right class="size-6" variant="outline" />
                                        <span>{{ trans_choice(':count propuesta|:count propuestas', $need->offers_count, ['count' => $need->offers_count]) }}</span>
                                    </div>

                                    <span class="inline-flex items-center justify-center rounded-full px-4 py-2 text-lg font-medium {{ $statusClasses($need) }}">
                                        {{ $statusLabel($need) }}
                                    </span>

                                    <a
                                        href="{{ route('mis-solicitudes.show', $need) }}"
                                        wire:navigate
                                        class="inline-flex min-w-64 items-center justify-center rounded-2xl border border-brand-300 px-6 py-3.5 text-xl font-medium text-brand-700 transition hover:bg-brand-50 dark:border-brand-700/60 dark:text-brand-300 dark:hover:bg-brand-500/10"
                                    >
                                        {{ $cta }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="mt-12 flex items-center justify-center gap-3 text-center text-xl text-zinc-500 dark:text-zinc-400">
            <flux:icon.bell class="size-6" variant="outline" />
            <span>{{ __('Te avisaremos cuando un negocio envíe una propuesta.') }}</span>
        </div>
    </div>
</x-layouts::cliente>
