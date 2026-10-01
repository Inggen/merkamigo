@php
    $topPlan = $plans->last();

    $money = fn (?int $priceCents) => filled($priceCents)
        ? '$'.number_format($priceCents / 100, 0, ',', '.').' COP'
        : __('$0 COP');

    // Solo para las filas numéricas de la tabla comparativa — los límites
    // vienen de `Plan::limit()` (real, editable desde Filament), esto solo
    // traduce el número a texto legible.
    $limitLabel = function (?\App\Domain\Billing\Models\Plan $plan, string $key) {
        $value = $plan?->limit($key);

        return match (true) {
            $plan === null => '—',
            $value === null => __('Ilimitado'),
            $value === 0 => __('No incluido'),
            default => (string) $value,
        };
    };

    $comparisonKeys = [
        'max_products' => __('Productos y servicios'),
        'max_storefronts' => __('Vitrinas (negocios) publicadas'),
        'max_members' => __('Colaboradores en el equipo'),
        'max_featured_days' => __('Días de destacado en la Plaza'),
    ];

    // Filas cualitativas: no vienen de un campo de `Plan`, reflejan los
    // candados reales del código (`Business::isOnPaidPlan()`/
    // `isOnTopPlan()`, ver `⚡copiloto.blade.php`, `⚡cobros-en-linea.blade.php`,
    // `⚡ventas.blade.php`, `⚡lives.blade.php`, `⚡metricas.blade.php`,
    // `Business::canUseAiChatbot()`) — si esos candados cambian, esta tabla
    // hay que actualizarla a mano.
    $qualitativeRows = [
        __('Copiloto de WhatsApp') => ['gratis' => false, 'emprendedor' => true, 'negocios' => true],
        __('Cobros en línea (Wompi propio)') => ['gratis' => false, 'emprendedor' => true, 'negocios' => true],
        __('Ver ventas pagadas en línea') => ['gratis' => false, 'emprendedor' => true, 'negocios' => true],
        __('Asistente IA (vitrina, descripciones, fotos, chatbot)') => ['gratis' => false, 'emprendedor' => false, 'negocios' => true],
        __('En vivo (Live Commerce)') => ['gratis' => false, 'emprendedor' => false, 'negocios' => true],
        __('Métricas avanzadas (90 días + exportar CSV)') => ['gratis' => false, 'emprendedor' => false, 'negocios' => true],
    ];

    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Planes y precios')],
        ]),
    ];

    $faqs = [
        ['pregunta' => __('¿Necesito tarjeta para empezar?'), 'respuesta' => __('No. El plan Gratis no pide tarjeta ni tiene fecha de vencimiento: crea tu vitrina y úsala el tiempo que quieras.')],
        ['pregunta' => __('¿Puedo cambiar de plan cuando quiera?'), 'respuesta' => __('Sí. Puedes subir o bajar de plan desde tu panel, cuando lo necesites.')],
        ['pregunta' => __('¿Qué pasa si cancelo un plan pago?'), 'respuesta' => __('Conservas los beneficios hasta el final del periodo que ya pagaste; después tu vitrina sigue activa en el plan Gratis.')],
        ['pregunta' => __('¿Qué pasa si supero el límite de productos de mi plan?'), 'respuesta' => __('Tus productos actuales siguen publicados. Para agregar más, mejora de plan cuando lo necesites.')],
    ];
@endphp

<x-layouts::public
    :title="__('Planes y precios')"
    :description="__('Conoce los planes de Merkamigo: una vitrina gratuita para empezar y los planes Emprendedor y Negocios para hacer crecer tu negocio local.')"
    :canonical="route('planes-y-precios')"
    :schema-graph="$schemaGraph"
>
    <div class="bg-gradient-to-b from-brand-50/70 to-transparent px-6 pt-14 pb-10 text-center dark:from-brand-500/10">
        <h1 class="mx-auto max-w-2xl text-3xl font-semibold tracking-tight text-carbon dark:text-white sm:text-4xl">
            {{ __('Un plan para cada etapa de tu negocio') }}
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-zinc-600 dark:text-zinc-300">
            {{ __('Empieza gratis con tu vitrina en la Plaza. Cuando quieras crecer, mejora de plan cuando lo necesites.') }}
        </p>
    </div>

    <div class="mx-auto -mt-2 max-w-6xl px-6 pb-16">
        {{-- Tarjetas de plan --}}
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
                @php $isTopPlan = $plan->id === $topPlan?->id; @endphp
                <div @class([
                    'flex flex-col rounded-2xl border bg-white p-7 shadow-sm dark:bg-zinc-900',
                    'border-2 border-brand-600 lg:-my-2 lg:py-9 lg:shadow-lg' => $isTopPlan,
                    'border-zinc-200 dark:border-zinc-700' => ! $isTopPlan,
                ])>
                    <div class="flex items-start justify-between gap-3">
                        <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                        @if ($isTopPlan)
                            <flux:badge color="red">{{ __('Recomendado') }}</flux:badge>
                        @endif
                    </div>

                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $plan->description }}</flux:text>

                    <div class="mt-5">
                        <span class="text-3xl font-semibold text-carbon dark:text-white">{{ $money($plan->price_cents) }}</span>
                        @if (! $plan->isFree())
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">/ {{ __('mes') }}</span>
                        @endif
                    </div>

                    @if ($plan->trial_days > 0)
                        <flux:text class="mt-1 text-sm text-brand-600 dark:text-brand-400">
                            {{ trans_choice('Incluye :count día de prueba|Incluye :count días de prueba', $plan->trial_days, ['count' => $plan->trial_days]) }}
                        </flux:text>
                    @endif

                    @if (! empty($plan->features))
                        <ul class="mt-6 space-y-2.5 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:icon.check-circle variant="outline" class="mt-0.5 size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-auto pt-7">
                        <flux:button
                            :href="route('emprendedores.bienvenida')"
                            wire:navigate
                            variant="{{ $isTopPlan ? 'primary' : 'outline' }}"
                            class="w-full"
                        >
                            {{ $plan->isFree() ? __('Crear mi vitrina gratis') : __('Comenzar ahora') }}
                        </flux:button>
                        @if (! $plan->isFree())
                            <flux:text class="mt-2 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Crea tu vitrina gratis y activa :plan cuando quieras desde tu panel.', ['plan' => $plan->name]) }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabla comparativa --}}
        <div class="mt-14">
            <flux:heading size="lg" class="text-center">{{ __('Compara los planes') }}</flux:heading>

            <div class="mt-6 overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-5 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Incluye') }}</th>
                            @foreach ($plans as $plan)
                                <th @class([
                                    'px-5 py-3 text-center font-medium whitespace-nowrap',
                                    'text-brand-700 dark:text-brand-300' => $plan->id === $topPlan?->id,
                                    'text-zinc-700 dark:text-zinc-200' => $plan->id !== $topPlan?->id,
                                ])>{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($comparisonKeys as $key => $label)
                            <tr>
                                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-200">{{ $label }}</td>
                                @foreach ($plans as $plan)
                                    <td @class([
                                        'px-5 py-3 text-center',
                                        'font-semibold text-zinc-900 dark:text-white' => $plan->id === $topPlan?->id,
                                        'text-zinc-600 dark:text-zinc-300' => $plan->id !== $topPlan?->id,
                                    ])>{{ $limitLabel($plan, $key) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        @foreach ($qualitativeRows as $label => $included)
                            <tr>
                                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-200">{{ $label }}</td>
                                @foreach ($plans as $plan)
                                    <td class="px-5 py-3 text-center">
                                        @if ($included[$plan->slug] ?? false)
                                            <flux:icon.check-circle variant="outline" class="mx-auto size-5 text-brand-600 dark:text-brand-400" />
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Servicios puntuales --}}
        @if ($destacados->isNotEmpty() || $vitrinaAsistida || $kitArrancaBonito || $asistenteIa)
            <div class="mt-16">
                <flux:heading size="lg" class="text-center">{{ __('Impulsos puntuales, sin suscripción') }}</flux:heading>
                <flux:subheading class="mx-auto mt-2 max-w-xl text-center">
                    {{ __('Pago único, disponible para cualquier negocio con vitrina en Merkamigo, sin importar el plan.') }}
                </flux:subheading>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($destacados->isNotEmpty())
                        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
                            <flux:heading size="base">{{ __('Destacar tu vitrina') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $destacados->first()->description }}</flux:text>
                            <ul class="mt-4 space-y-2 text-sm">
                                @foreach ($destacados as $destacado)
                                    <li class="flex items-center justify-between gap-3 text-zinc-700 dark:text-zinc-200">
                                        <span>{{ trans_choice(':count día|:count días', $destacado->payload['days'] ?? 0, ['count' => $destacado->payload['days'] ?? 0]) }}</span>
                                        <span class="font-semibold">{{ $money($destacado->price_cents) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($asistenteIa)
                        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
                            <flux:heading size="base">{{ $asistenteIa->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $asistenteIa->description }}</flux:text>
                            <div class="mt-4 text-xl font-semibold text-carbon dark:text-white">{{ $money($asistenteIa->price_cents) }}</div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Ya incluido en el plan Negocios.') }}</flux:text>
                        </div>
                    @endif

                    @if ($vitrinaAsistida)
                        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
                            <flux:heading size="base">{{ $vitrinaAsistida->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $vitrinaAsistida->description }}</flux:text>
                            <div class="mt-4 text-xl font-semibold text-carbon dark:text-white">{{ $money($vitrinaAsistida->price_cents) }}</div>
                        </div>
                    @endif

                    @if ($kitArrancaBonito)
                        <div class="rounded-2xl border-2 border-brand-300 bg-brand-50/40 p-6 dark:border-brand-800 dark:bg-brand-500/5">
                            <flux:heading size="base">{{ $kitArrancaBonito->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $kitArrancaBonito->description }}</flux:text>
                            <div class="mt-4 text-xl font-semibold text-carbon dark:text-white">{{ $money($kitArrancaBonito->price_cents) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Preguntas frecuentes --}}
        <div class="mx-auto mt-16 max-w-2xl">
            <flux:heading size="lg" class="text-center">{{ __('Preguntas frecuentes') }}</flux:heading>

            <div class="mt-6 divide-y divide-zinc-200 dark:divide-zinc-700" x-data="{ open: null }">
                @foreach ($faqs as $index => $faq)
                    <div class="py-4">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 text-left font-medium"
                            x-on:click="open = open === {{ $index }} ? null : {{ $index }}"
                        >
                            {{ $faq['pregunta'] }}
                            <flux:icon.chevron-down class="size-4 shrink-0 transition-transform" x-bind:class="open === {{ $index }} ? 'rotate-180' : ''" variant="outline" />
                        </button>

                        <div x-show="open === {{ $index }}" x-cloak>
                            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $faq['respuesta'] }}</flux:text>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 text-center">
                <flux:link :href="route('preguntas-frecuentes')" wire:navigate>{{ __('Ver más preguntas frecuentes →') }}</flux:link>
            </div>
        </div>

        <x-cta.crear-vitrina />
    </div>
</x-layouts::public>
