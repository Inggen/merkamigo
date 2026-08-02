<footer class="mt-12 border-t border-zinc-200/80 bg-white/80 pt-10 text-zinc-700 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-zinc-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden">
            <div class="grid gap-10 py-10 lg:grid-cols-[1.15fr_1px_1fr_1fr_1fr] lg:py-12">
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <x-app-logo-icon class="h-12 w-auto" />
                        <x-brand-wordmark size="lg" />
                    </div>

                    <p class="max-w-xs text-sm leading-tight text-zinc-600 dark:text-zinc-300">
                        {{ __('Descubre lo local, conecta con tu comunidad.') }}
                    </p>

                    <div class="flex items-center gap-4">
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="inline-flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition hover:bg-brand-50 hover:text-brand-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" aria-label="{{ __('Facebook') }}">
                            <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true"><path d="M13.5 21v-7h2.3l.4-3h-2.7V9.1c0-.9.3-1.6 1.6-1.6H16V4.8c-.5-.1-1.3-.2-2.2-.2-2.2 0-3.8 1.3-3.8 4V11H7.8v3H10V21h3.5Z"/></svg>
                        </a>
                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="inline-flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition hover:bg-brand-50 hover:text-brand-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" aria-label="{{ __('Instagram') }}">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a href="{{ route('municipios') }}" wire:navigate class="inline-flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition hover:bg-brand-50 hover:text-brand-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" aria-label="{{ __('Municipios') }}">
                            <flux:icon.map-pin class="size-5" />
                        </a>
                    </div>
                </div>

                <div class="hidden w-px bg-zinc-200 lg:block dark:bg-zinc-800"></div>

                <div class="space-y-5">
                    <div class="flex items-center gap-3 text-zinc-950 dark:text-white">
                        <svg viewBox="0 0 24 24" class="size-6 text-brand-500" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m14.8 9.2-2 5.6-5.6 2 2-5.6 5.6-2Z"/></svg>
                        <h3 class="text-lg font-semibold">{{ __('Explora') }}</h3>
                    </div>

                    <nav class="space-y-5 text-sm text-zinc-600 dark:text-zinc-300">
                        <a href="{{ route('municipios') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <svg viewBox="0 0 24 24" class="size-6 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3.5 6.5 5-2 7 2 5-2v13l-5 2-7-2-5 2v-13Z"/><path d="M8.5 4.5v13"/><path d="M15.5 6.5v13"/></svg>
                            <span>{{ __('Municipios') }}</span>
                        </a>
                        <a href="{{ route('categorias') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <flux:icon.squares-2x2 class="size-6 text-zinc-400" />
                            <span>{{ __('Categorías') }}</span>
                        </a>
                        <a href="{{ route('como-funciona') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <flux:icon.question-mark-circle class="size-6 text-zinc-400" />
                            <span>{{ __('Cómo funciona') }}</span>
                        </a>
                    </nav>
                </div>

                <div class="space-y-5">
                    <div class="flex items-center gap-3 text-zinc-950 dark:text-white">
                        <flux:icon.chat-bubble-left-right class="size-6 text-brand-500" />
                        <h3 class="text-lg font-semibold">{{ __('Ayuda') }}</h3>
                    </div>

                    <nav class="space-y-5 text-sm text-zinc-600 dark:text-zinc-300">
                        <a href="{{ route('preguntas-frecuentes') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <svg viewBox="0 0 24 24" class="size-6 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 18.5c-2.8 0-5-2.2-5-5v-3c0-2.8 2.2-5 5-5h10c2.8 0 5 2.2 5 5v3c0 2.8-2.2 5-5 5H9l-4 3v-3H7Z"/></svg>
                            <span>{{ __('Preguntas frecuentes') }}</span>
                        </a>
                        <a href="{{ route('soporte') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <svg viewBox="0 0 24 24" class="size-6 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.2"/><path d="M18.1 5.9 14.3 9.7"/><path d="m9.7 14.3-3.8 3.8"/><path d="M18.1 18.1 14.3 14.3"/><path d="M9.7 9.7 5.9 5.9"/></svg>
                            <span>{{ __('Soporte') }}</span>
                        </a>
                    </nav>
                </div>

                <div class="space-y-5">
                    <div class="flex items-center gap-3 text-zinc-950 dark:text-white">
                        <flux:icon.shield-check class="size-6 text-brand-500" />
                        <h3 class="text-lg font-semibold">{{ __('Legal') }}</h3>
                    </div>

                    <nav class="space-y-5 text-sm text-zinc-600 dark:text-zinc-300">
                        <a href="{{ route('terminos') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <svg viewBox="0 0 24 24" class="size-6 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3.5h7l4 4v13H7a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z"/><path d="M14 3.5v4h4"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                            <span>{{ __('Términos') }}</span>
                        </a>
                        <a href="{{ route('privacidad') }}" wire:navigate class="flex items-center gap-4 transition hover:text-brand-600">
                            <flux:icon.lock-closed class="size-6 text-zinc-400" />
                            <span>{{ __('Privacidad') }}</span>
                        </a>
                        <a href="{{ route('sitemap') }}" class="flex items-center gap-4 transition hover:text-brand-600">
                            <svg viewBox="0 0 24 24" class="size-6 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="5" r="2.2"/><circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="18" r="2.2"/><circle cx="12" cy="12" r="2.2"/><path d="M12 7.2v2.6"/><path d="m10.5 13.5-2.9 2.2"/><path d="m13.5 13.5 2.9 2.2"/></svg>
                            <span>{{ __('Sitemap') }}</span>
                        </a>
                    </nav>
                </div>
            </div>

            <div class="border-t border-zinc-200 px-6 py-5 dark:border-zinc-800 lg:px-10">
                <div class="flex flex-col items-center justify-center gap-3 text-center text-sm text-zinc-500 dark:text-zinc-400 sm:flex-row sm:gap-6">
                    <div class="flex items-center gap-3">
                        <flux:icon.heart class="size-5 text-brand-500" />
                        <span>&copy; {{ now()->year }} {{ config('app.name', 'Merkamigo') }}</span>
                    </div>
                    <span class="hidden text-zinc-300 sm:inline dark:text-zinc-700">|</span>
                    <p>
                        {{ __('Hecho con') }}
                        <span class="font-semibold text-brand-500">{{ __('amor') }}</span>
                        {{ __('por lo local') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
