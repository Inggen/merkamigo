<footer class="border-t border-zinc-200 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
    <div class="mx-auto flex max-w-5xl flex-col items-center gap-2 px-6">
        <span>&copy; {{ now()->year }} Merkamigo</span>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('municipios') }}" class="hover:text-brand-600" wire:navigate>{{ __('Municipios') }}</a>
            <a href="{{ route('categorias') }}" class="hover:text-brand-600" wire:navigate>{{ __('Categorías') }}</a>
            <a href="{{ route('como-funciona') }}" class="hover:text-brand-600" wire:navigate>{{ __('Cómo funciona') }}</a>
            <a href="{{ route('preguntas-frecuentes') }}" class="hover:text-brand-600" wire:navigate>{{ __('Preguntas frecuentes') }}</a>
            <a href="{{ route('soporte') }}" class="hover:text-brand-600" wire:navigate>{{ __('Soporte') }}</a>
            <a href="{{ route('terminos') }}" class="hover:text-brand-600" wire:navigate>{{ __('Términos') }}</a>
            <a href="{{ route('privacidad') }}" class="hover:text-brand-600" wire:navigate>{{ __('Privacidad') }}</a>
            <a href="{{ route('sitemap') }}" class="hover:text-brand-600">{{ __('Sitemap') }}</a>
        </div>
    </div>
</footer>
