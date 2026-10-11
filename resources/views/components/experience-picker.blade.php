@php
    $experiences = [
        [
            'value' => 'cliente',
            'eyebrow' => __('Explorar y comprar'),
            'icon' => 'magnifying-glass',
            'title' => __('Quiero comprar o encontrar'),
            'description' => __('Descubre negocios, productos, eventos y recompensas cerca de ti.'),
            'features' => [__('Vitrinas locales'), __('Pídelo'), __('Merkapuntos')],
            'action' => __('Explorar negocios'),
        ],
        [
            'value' => 'emprendedor',
            'eyebrow' => __('Emprender y crecer'),
            'icon' => 'building-storefront',
            'title' => __('Quiero mostrar y hacer crecer mi negocio'),
            'description' => __('Crea tu vitrina, publica lo que ofreces y recibe contactos directos de nuevos clientes.'),
            'features' => [__('Productos y servicios'), __('Eventos y recompensas'), __('Contacto por WhatsApp')],
            'action' => __('Crear mi vitrina'),
        ],
    ];
@endphp

<div {{ $attributes->class('grid gap-5 md:grid-cols-2') }}>
    @foreach ($experiences as $experience)
        <form method="POST" action="{{ route('experience.update') }}" class="h-full">
            @csrf
            <input type="hidden" name="experience" value="{{ $experience['value'] }}">

            <button
                type="submit"
                class="group relative flex min-h-96 w-full overflow-hidden rounded-[2rem] border border-zinc-200 bg-white p-7 text-start shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-400 hover:shadow-[0_22px_60px_-24px_rgba(220,38,38,0.55)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 sm:p-9 dark:border-zinc-700 dark:bg-zinc-950 dark:hover:border-brand-400"
            >
                <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_85%_12%,rgba(239,68,68,0.16),transparent_40%)] opacity-0 transition duration-300 group-hover:opacity-100" aria-hidden="true"></span>
                <span class="pointer-events-none absolute inset-x-10 top-0 h-px bg-gradient-to-r from-transparent via-brand-400 to-transparent opacity-0 transition duration-300 group-hover:opacity-100" aria-hidden="true"></span>

                <span class="relative flex w-full flex-col items-start">
                    <span class="flex size-16 items-center justify-center rounded-2xl border border-brand-500/30 bg-brand-500/10 text-brand-600 transition duration-300 group-hover:border-brand-400 group-hover:bg-brand-500/15 group-hover:shadow-[0_0_28px_rgba(239,68,68,0.22)] dark:text-brand-400">
                        <flux:icon :name="$experience['icon']" class="size-8" variant="outline" />
                    </span>

                    <span class="mt-7 text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">
                        {{ $experience['eyebrow'] }}
                    </span>
                    <span class="mt-3 text-2xl font-black leading-tight text-zinc-950 sm:text-3xl dark:text-white">
                        {{ $experience['title'] }}
                    </span>
                    <span class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-400">
                        {{ $experience['description'] }}
                    </span>

                    <span class="mt-6 flex flex-wrap gap-2">
                        @foreach ($experience['features'] as $feature)
                            <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                                {{ $feature }}
                            </span>
                        @endforeach
                    </span>

                    <span class="mt-auto flex items-center gap-3 pt-8 text-base font-bold text-zinc-950 dark:text-white">
                        <span class="flex size-11 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition duration-300 group-hover:translate-x-1 group-hover:bg-brand-500">
                            <flux:icon.arrow-right class="size-5" />
                        </span>
                        {{ $experience['action'] }}
                    </span>
                </span>
            </button>
        </form>
    @endforeach
</div>
