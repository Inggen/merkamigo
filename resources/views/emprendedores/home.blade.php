<x-layouts::app :title="__('Inicio')">
    @php
        $canCreateStorefront = $storefrontQuota['can_create'] ?? true;
        $storefrontLimit = $storefrontQuota['limit'] ?? null;
        $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');
        $metrics = $primaryBusiness ? ($metricsByBusiness[$primaryBusiness->id] ?? null) : null;
        $canUsePaidFeatures = $primaryBusiness
            && ($primaryBusiness->isOnPaidPlan() || (auth()->user()?->canBypassPlanGates() ?? false));
        $canUseLive = $primaryBusiness
            && ($primaryBusiness->isOnTopPlan() || (auth()->user()?->canBypassPlanGates() ?? false));
        $contentLinks = $primaryBusiness ? [
            [__('Publicaciones'), 'rectangle-stack', route('emprendedores.negocios.publicaciones', $primaryBusiness)],
            [__('Estados'), 'bolt', route('emprendedores.negocios.estados', $primaryBusiness)],
            [__('Reels'), 'film', route('emprendedores.negocios.reels', $primaryBusiness)],
        ] : [];

        if ($canUseLive) {
            $contentLinks[] = [__('En vivo'), 'video-camera', route('emprendedores.negocios.lives', $primaryBusiness)];
        }

        $metricCards = $primaryBusiness ? [
            [
                'label' => __('Visitas esta semana'),
                'value' => $dashboard['views'],
                'change' => $dashboard['views_change'],
                'icon' => 'eye',
                'iconClass' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
                'surfaceClass' => 'from-rose-50/90 to-white dark:from-rose-950/30 dark:to-zinc-900',
            ],
            [
                'label' => __('Contactos esta semana'),
                'value' => $dashboard['contacts'],
                'change' => $dashboard['contacts_change'],
                'icon' => 'chat-bubble-left-right',
                'iconClass' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
                'surfaceClass' => 'from-violet-50/90 to-white dark:from-violet-950/30 dark:to-zinc-900',
            ],
            [
                'label' => __('Pedidos esta semana'),
                'value' => $dashboard['orders'],
                'change' => null,
                'icon' => 'shopping-bag',
                'iconClass' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
                'surfaceClass' => 'from-sky-50/90 to-white dark:from-sky-950/30 dark:to-zinc-900',
            ],
            [
                'label' => __('Puntos redimidos'),
                'value' => $dashboard['redemptions'],
                'change' => null,
                'icon' => 'star',
                'iconClass' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
                'surfaceClass' => 'from-amber-50/90 to-white dark:from-amber-950/30 dark:to-zinc-900',
            ],
        ] : [];

        $areas = $primaryBusiness ? [
            [
                'title' => __('Gestionar vitrina'),
                'description' => __('Personaliza tu vitrina y mantén tu negocio al día.'),
                'icon' => 'building-storefront',
                'accent' => 'rose',
                'links' => [
                    [__('Editar vitrina'), 'pencil-square', route('emprendedores.negocios.vitrina', $primaryBusiness)],
                    [__('Productos'), 'cube', route('emprendedores.negocios.productos', $primaryBusiness)],
                    [__('Mi stand en la plaza'), 'building-storefront', route('emprendedores.negocios.mi-stand', $primaryBusiness)],
                ],
            ],
            [
                'title' => __('Contenido y comunidad'),
                'description' => __('Publica y conecta con tus clientes.'),
                'icon' => 'camera',
                'accent' => 'violet',
                'links' => $contentLinks,
            ],
            [
                'title' => __('Ventas y cobros'),
                'description' => __('Gestiona tus ventas de forma simple.'),
                'icon' => 'shopping-cart',
                'accent' => 'emerald',
                'links' => [
                    [__('Ventas'), 'shopping-bag', route('emprendedores.negocios.ventas', $primaryBusiness)],
                    [__('Cobros en línea'), 'credit-card', route('emprendedores.negocios.cobros-en-linea', $primaryBusiness)],
                    [__('Pedidos'), 'clipboard-document-list', route('emprendedores.negocios.ventas', $primaryBusiness)],
                ],
            ],
            [
                'title' => __('Promoción y crecimiento'),
                'description' => __('Da a conocer tu negocio y llega a más personas.'),
                'icon' => 'chart-bar',
                'accent' => 'amber',
                'links' => [
                    [__('Métricas'), 'chart-bar', route('emprendedores.negocios.metricas', $primaryBusiness)],
                    [__('Promocionar'), 'megaphone', $canUsePaidFeatures ? route('emprendedores.negocios.copiloto', $primaryBusiness) : route('emprendedores.negocios.impulsar', $primaryBusiness)],
                    [__('Compartir y QR'), 'qr-code', route('emprendedores.negocios.compartir', $primaryBusiness)],
                ],
            ],
            [
                'title' => __('Fidelización'),
                'description' => __('Crea experiencias y premia a tus clientes.'),
                'icon' => 'star',
                'accent' => 'pink',
                'links' => [
                    [__('Merkapuntos'), 'sparkles', route('emprendedores.negocios.merkapuntos', $primaryBusiness)],
                    [__('Eventos'), 'calendar-days', route('emprendedores.negocios.eventos', $primaryBusiness)],
                    [__('Pasaporte de confianza'), 'shield-check', route('emprendedores.negocios.verificacion', $primaryBusiness)],
                ],
            ],
        ] : [];

        $areaStyles = [
            'rose' => ['card' => 'border-rose-100 bg-rose-50/70 dark:border-rose-500/15 dark:bg-rose-500/5', 'icon' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'],
            'violet' => ['card' => 'border-violet-100 bg-violet-50/70 dark:border-violet-500/15 dark:bg-violet-500/5', 'icon' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300'],
            'emerald' => ['card' => 'border-emerald-100 bg-emerald-50/70 dark:border-emerald-500/15 dark:bg-emerald-500/5', 'icon' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'amber' => ['card' => 'border-amber-100 bg-amber-50/70 dark:border-amber-500/15 dark:bg-amber-500/5', 'icon' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300'],
            'pink' => ['card' => 'border-pink-100 bg-pink-50/70 dark:border-pink-500/15 dark:bg-pink-500/5', 'icon' => 'bg-pink-100 text-pink-600 dark:bg-pink-500/15 dark:text-pink-300'],
        ];
    @endphp

    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-950 dark:text-white">
                    {{ __('Hola, :name', ['name' => $firstName]) }} 👋
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Gestiona, conecta y haz crecer tu negocio desde un solo lugar.') }}
                </p>
            </div>
            <div class="flex flex-col items-start gap-2 sm:items-end">
                @if ($canCreateStorefront)
                    <flux:button variant="primary" icon="plus" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                        {{ __('Crear una vitrina') }}
                    </flux:button>
                @endif
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ ucfirst(now()->translatedFormat('D, d \d\e M \d\e Y')) }}
                </p>
            </div>
        </header>

        @if ($businesses->isEmpty())
            <div class="entrepreneur-card overflow-hidden">
                <div class="grid min-h-80 place-items-center bg-gradient-to-br from-brand-50 via-white to-amber-50 p-8 text-center dark:from-brand-950/30 dark:via-zinc-900 dark:to-amber-950/20">
                    <div class="max-w-md">
                        <span class="mx-auto inline-flex size-16 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                            <flux:icon.building-storefront class="size-8" variant="outline" />
                        </span>
                        <h2 class="mt-5 text-2xl font-black">{{ __('Todavía no tienes ninguna vitrina') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ __('Crea tu vitrina en pocos minutos y empieza a mostrar tu negocio en Merkamigo.') }}</p>
                    </div>
                </div>
            </div>
        @else
            <section class="grid gap-4 xl:grid-cols-[1.55fr_1fr]">
                <article class="entrepreneur-card overflow-hidden p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="relative h-44 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 sm:h-auto sm:w-48 dark:bg-zinc-800">
                            @if ($primaryBusiness->storefront?->coverUrl())
                                <img src="{{ $primaryBusiness->storefront->coverUrl() }}" class="size-full object-cover" alt="{{ $primaryBusiness->storefront->cover_alt_text ?? $primaryBusiness->name }}">
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-100 to-amber-50 text-brand-600 dark:from-brand-950 dark:to-zinc-800 dark:text-brand-300">
                                    <flux:icon.building-storefront class="size-16" variant="outline" />
                                </div>
                            @endif

                            @if ($primaryBusiness->logoUrl())
                                <img src="{{ $primaryBusiness->logoUrl() }}" class="absolute bottom-3 left-3 size-16 rounded-full border-4 border-white bg-white object-cover shadow-md dark:border-zinc-800" alt="{{ $primaryBusiness->name }}">
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="truncate text-xl font-black">{{ $primaryBusiness->name }}</h2>
                                    <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $primaryBusiness->storefront?->description ?: __('Completa la información de tu vitrina para contarles a todos qué hace especial a tu negocio.') }}
                                    </p>
                                </div>
                                <flux:badge size="sm" :color="$primaryBusiness->isPublished() ? 'green' : ($primaryBusiness->isSuspended() ? 'red' : 'zinc')">
                                    {{ ucfirst($primaryBusiness->status) }}
                                </flux:badge>
                            </div>

                            @if ($primaryBusiness->isSuspended())
                                <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm dark:border-red-500/20 dark:bg-red-500/10">
                                    <p class="font-bold">{{ __('Tu vitrina fue suspendida.') }}</p>
                                    <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $primaryBusiness->suspension_reason }}</p>
                                    <a href="{{ route('soporte') }}" class="mt-2 inline-flex font-semibold text-brand-600 hover:underline" wire:navigate>{{ __('Ir a soporte') }}</a>
                                </div>
                            @elseif (! $primaryBusiness->isPublished() && ($missingByBusiness[$primaryBusiness->id] ?? []) !== [])
                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                                    <p class="font-bold">{{ __('Te falta para vender:') }}</p>
                                    <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ implode(' · ', $missingByBusiness[$primaryBusiness->id]) }}</p>
                                </div>
                            @elseif ($metrics)
                                <div class="mt-4 grid grid-cols-2 divide-x divide-zinc-200 rounded-xl bg-zinc-50 p-3 dark:divide-zinc-700 dark:bg-zinc-800/70">
                                    <div class="px-3">
                                        <p class="text-xl font-black">{{ number_format($metrics['total_views'], 0, ',', '.') }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Visitas esta semana') }}</p>
                                    </div>
                                    <div class="px-3">
                                        <p class="text-xl font-black">{{ number_format($metrics['total_whatsapp_clicks'], 0, ',', '.') }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Contactos esta semana') }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('emprendedores.negocios.vitrina', $primaryBusiness)" wire:navigate>{{ __('Editar vitrina') }}</flux:button>
                                <flux:button size="sm" variant="ghost" icon="cube" :href="route('emprendedores.negocios.productos', $primaryBusiness)" wire:navigate>{{ __('Productos') }}</flux:button>
                                @if ($primaryBusiness->isPublished() && $primaryBusiness->whatsapp_number)
                                    <flux:button size="sm" variant="ghost" icon="chat-bubble-left-right" href="https://wa.me/{{ preg_replace('/\D/', '', $primaryBusiness->whatsapp_number) }}" target="_blank">{{ __('WhatsApp') }}</flux:button>
                                @elseif ($primaryBusiness->isPublished())
                                    <flux:button size="sm" variant="ghost" icon="qr-code" :href="route('emprendedores.negocios.compartir', $primaryBusiness)" wire:navigate>{{ __('Compartir y QR') }}</flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>

                <aside class="relative isolate overflow-hidden rounded-2xl border border-brand-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-6 shadow-sm dark:border-brand-500/20 dark:from-brand-950/35 dark:via-zinc-900 dark:to-orange-950/20">
                    <div class="absolute -right-10 -top-12 -z-10 size-48 rounded-full bg-brand-200/40 blur-3xl dark:bg-brand-500/15"></div>
                    <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-900/20">
                        <flux:icon.rocket-launch class="size-6" variant="outline" />
                    </span>
                    <h2 class="mt-4 text-2xl font-black text-brand-600 dark:text-brand-300">{{ __('Impulsa tu negocio') }}</h2>
                    <p class="mt-2 max-w-sm text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Publica contenido, participa en la plaza y llega a más personas en Merkamigo.') }}</p>
                    <flux:button class="mt-5" variant="primary" icon="plus-circle" :href="route('emprendedores.negocios.publicaciones', $primaryBusiness)" wire:navigate>{{ __('Crear publicación') }}</flux:button>
                </aside>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('Resumen semanal') }}">
                @foreach ($metricCards as $card)
                    <article class="rounded-2xl border border-zinc-200 bg-gradient-to-br {{ $card['surfaceClass'] }} p-4 shadow-sm dark:border-white/10">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $card['iconClass'] }}">
                                <x-dynamic-component :component="'flux::icon.'.$card['icon']" class="size-6" variant="outline" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-2xl font-black">{{ number_format($card['value'], 0, ',', '.') }}</p>
                                    @if ($card['change'] !== null)
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-bold',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $card['change'] >= 0,
                                            'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' => $card['change'] < 0,
                                        ])>{{ $card['change'] > 0 ? '+' : '' }}{{ $card['change'] }}%</span>
                                    @endif
                                </div>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex h-6 items-end gap-1" aria-hidden="true">
                            @foreach ([35, 48, 42, 67, 54, 76, 64, 88] as $height)
                                <span class="flex-1 rounded-full bg-current opacity-15" style="height: {{ $height }}%"></span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" aria-label="{{ __('Herramientas del negocio') }}">
                @foreach ($areas as $area)
                    @php
                        $styles = $areaStyles[$area['accent']];
                    @endphp
                    <article class="flex min-h-72 flex-col rounded-2xl border p-4 {{ $styles['card'] }}">
                        <span class="inline-flex size-12 items-center justify-center rounded-2xl {{ $styles['icon'] }}">
                            <x-dynamic-component :component="'flux::icon.'.$area['icon']" class="size-6" variant="solid" />
                        </span>
                        <h2 class="mt-3 text-lg font-black leading-5">{{ $area['title'] }}</h2>
                        <p class="mt-2 min-h-10 text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $area['description'] }}</p>
                        <div class="mt-4 space-y-2">
                            @foreach ($area['links'] as [$label, $icon, $url])
                                <a href="{{ $url }}" wire:navigate class="flex min-h-9 items-center gap-2 rounded-xl border border-white/80 bg-white/85 px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm transition hover:-translate-y-0.5 hover:text-brand-600 hover:shadow-md dark:border-white/10 dark:bg-zinc-900/80 dark:text-zinc-200 dark:hover:text-brand-300">
                                    <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4 shrink-0" variant="outline" />
                                    <span class="truncate">{{ $label }}</span>
                                    <flux:icon.chevron-right class="ml-auto size-3.5 shrink-0 text-zinc-400" variant="mini" />
                                </a>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-4 xl:grid-cols-3">
                <article class="entrepreneur-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-white/10">
                        <h2 class="font-black">{{ __('Actividad reciente') }}</h2>
                        <a href="{{ route('clientes.actividad') }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-300">{{ __('Ver todo') }}</a>
                    </div>
                    <div class="p-3">
                        @forelse ($recentActivity as $activity)
                            @php
                                $activityIcon = match ($activity['type']) {
                                    'view' => 'eye',
                                    'post' => 'rectangle-stack',
                                    'order' => 'shopping-bag',
                                    default => 'bell',
                                };
                            @endphp
                            <div class="flex items-center gap-3 rounded-xl px-2 py-2.5">
                                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-dynamic-component :component="'flux::icon.'.$activityIcon" class="size-4" variant="outline" /></span>
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $activity['title'] }}</p><p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $activity['description'] }}</p></div>
                                <time class="shrink-0 text-[0.68rem] text-zinc-400">{{ $activity['occurred_at']->diffForHumans(short: true) }}</time>
                            </div>
                        @empty
                            <div class="grid min-h-48 place-items-center p-5 text-center"><div><flux:icon.bell class="mx-auto size-8 text-zinc-300" variant="outline" /><p class="mt-2 text-sm font-bold">{{ __('Tu actividad aparecerá aquí') }}</p><p class="mt-1 text-xs text-zinc-500">{{ __('Comparte tu vitrina para empezar a recibir visitas.') }}</p></div></div>
                        @endforelse
                    </div>
                </article>

                <article class="entrepreneur-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-white/10">
                        <h2 class="font-black">{{ __('Próximos eventos') }}</h2>
                        <a href="{{ route('emprendedores.negocios.eventos', $primaryBusiness) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-300">{{ __('Ver todos') }}</a>
                    </div>
                    <div class="p-4">
                        @forelse ($upcomingEvents as $event)
                            <a href="{{ route('emprendedores.negocios.eventos', $primaryBusiness) }}" wire:navigate class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-zinc-50 dark:hover:bg-white/5">
                                <span class="flex w-12 shrink-0 flex-col items-center rounded-xl bg-brand-50 p-2 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><span class="text-[0.65rem] font-bold uppercase">{{ $event->starts_at->translatedFormat('M') }}</span><span class="text-lg font-black">{{ $event->starts_at->format('d') }}</span></span>
                                <div class="min-w-0"><p class="truncate text-sm font-bold">{{ $event->title }}</p><p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $event->starts_at->translatedFormat('H:i') }} · {{ $event->location_text ?: __('Sin ubicación definida') }}</p></div>
                            </a>
                        @empty
                            <div class="grid min-h-44 place-items-center text-center">
                                <div>
                                    <span class="mx-auto inline-flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300"><flux:icon.calendar-days class="size-6" variant="outline" /></span>
                                    <p class="mt-3 text-sm font-bold">{{ __('Aún no tienes eventos') }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('Crea eventos para conectar con tus clientes.') }}</p>
                                    <flux:button class="mt-3" size="sm" variant="primary" icon="plus" :href="route('emprendedores.negocios.eventos', $primaryBusiness)" wire:navigate>{{ __('Crear evento') }}</flux:button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="entrepreneur-card overflow-hidden">
                    <div class="border-b border-zinc-100 px-5 py-4 dark:border-white/10"><h2 class="font-black">{{ __('Recomendaciones para crecer') }}</h2></div>
                    <div class="space-y-2 p-3">
                        @foreach ([
                            [__('Publica contenido regularmente'), __('Mantén tu vitrina activa y visible.'), 'megaphone', route('emprendedores.negocios.publicaciones', $primaryBusiness), 'text-amber-600 bg-amber-50 dark:bg-amber-500/10'],
                            [__('Completa tu información'), __('Un perfil completo genera más confianza.'), 'chart-bar', route('emprendedores.negocios.vitrina', $primaryBusiness), 'text-violet-600 bg-violet-50 dark:bg-violet-500/10'],
                            [__('Comparte tu vitrina'), __('Llega a más personas fuera de Merkamigo.'), 'share', route('emprendedores.negocios.compartir', $primaryBusiness), 'text-sky-600 bg-sky-50 dark:bg-sky-500/10'],
                            [__('Activa tu pasaporte de confianza'), __('Aumenta la confianza de tus clientes.'), 'shield-check', route('emprendedores.negocios.verificacion', $primaryBusiness), 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10'],
                        ] as [$title, $copy, $icon, $url, $iconClass])
                            <a href="{{ $url }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-100 p-2.5 transition hover:border-brand-200 hover:bg-brand-50/40 dark:border-white/10 dark:hover:border-brand-500/20 dark:hover:bg-brand-500/5">
                                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full {{ $iconClass }}"><x-dynamic-component :component="'flux::icon.'.$icon" class="size-4" variant="outline" /></span>
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $title }}</p><p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $copy }}</p></div>
                                <flux:icon.chevron-right class="size-4 shrink-0 text-zinc-400" variant="mini" />
                            </a>
                        @endforeach
                    </div>
                </article>
            </section>

            @if ($businesses->count() > 1)
                <section class="entrepreneur-card p-5">
                    <div class="mb-4"><h2 class="font-black">{{ __('Tus otras vitrinas') }}</h2></div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($businesses->skip(1) as $business)
                            <a href="{{ route('emprendedores.negocios.vitrina', $business) }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-brand-300 hover:shadow-sm dark:border-white/10 dark:hover:border-brand-500/30">
                                <flux:avatar :src="$business->logoUrl()" :name="$business->name" size="sm" />
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $business->name }}</p><p class="text-xs text-zinc-500">{{ ucfirst($business->status) }}</p></div>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" variant="mini" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                @unless ($canCreateStorefront)
                    @if ($storefrontLimit)
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Ya alcanzaste el máximo de :count vitrinas para tu plan actual.', ['count' => $storefrontLimit]) }}</p>
                    @endif
                @endunless
                @if ($canUsePaidFeatures)
                    <a href="{{ route('emprendedores.negocios.plan', $primaryBusiness) }}" wire:navigate class="font-semibold text-zinc-500 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-brand-300">{{ __('Administrar mi plan') }}</a>
                @endif
            </div>
        @endif
    </div>
</x-layouts::app>
