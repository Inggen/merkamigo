{{-- Footer compartido delgado. La reserva del chatbot disminuye cuando
     el margen exterior del contenido ya deja espacio suficiente. --}}
<footer class="bg-brand-800 text-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-center gap-2 px-6 py-4 text-center text-sm sm:flex-row sm:justify-between sm:gap-4 sm:text-left md:pr-[clamp(1.5rem,calc(7.5rem-(100vw-80rem)/2),7.5rem)]">
        <p class="text-white/90">
            &copy; {{ now()->year }} {{ config('app.name', 'Merkamigo') }}
        </p>

        <p class="inline-flex items-center gap-1.5 text-white/90">
            {{ __('Hecho con') }}
            <flux:icon.heart class="size-4 text-rose-300" variant="solid" />
            {{ __('por') }}
            <a href="https://inggen.com" target="_blank" rel="noopener noreferrer" class="font-semibold underline decoration-white/40 underline-offset-4 transition hover:decoration-white">
                Inggen
            </a>
        </p>

        <nav aria-label="{{ __('Enlaces del pie de página') }}" class="flex flex-wrap items-center justify-center gap-2 text-white/90 sm:justify-end">
            <a href="{{ route('planes-y-precios') }}" wire:navigate class="transition hover:text-white hover:underline">{{ __('Planes y precios') }}</a>
            <span aria-hidden="true" class="text-white/40">|</span>
            <a href="{{ route('terminos') }}" wire:navigate class="transition hover:text-white hover:underline">{{ __('Términos') }}</a>
            <span class="text-white/40">|</span>
            <a href="{{ route('privacidad') }}" wire:navigate class="transition hover:text-white hover:underline">{{ __('Privacidad') }}</a>
            <span class="text-white/40">|</span>
            <a href="{{ route('sitemap') }}" class="transition hover:text-white hover:underline">{{ __('Sitemap') }}</a>
        </nav>
    </div>
</footer>
