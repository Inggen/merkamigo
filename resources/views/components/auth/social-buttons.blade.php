<div class="space-y-4">
    <div class="flex items-center gap-3 text-xs font-medium uppercase tracking-wider text-zinc-400">
        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
        <span>{{ __('o continúa con') }}</span>
        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ route('auth.social.redirect', 'google') }}" class="flex min-h-11 items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.32 2.98-7.41Z"/><path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.64-2.36l-3.25-2.54c-.9.6-2.05.96-3.39.96-2.6 0-4.81-1.76-5.6-4.12H3.05v2.62A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.4 13.94A6 6 0 0 1 6.09 12c0-.67.11-1.32.31-1.94V7.44H3.05A10 10 0 0 0 2 12c0 1.64.39 3.19 1.05 4.56l3.35-2.62Z"/><path fill="#EA4335" d="M12 5.94c1.47 0 2.79.5 3.82 1.5l2.89-2.89A9.67 9.67 0 0 0 12 2a10 10 0 0 0-8.95 5.44l3.35 2.62c.79-2.36 3-4.12 5.6-4.12Z"/></svg>
            {{ __('Google') }}
        </a>
        <a href="{{ route('auth.social.redirect', 'facebook') }}" class="flex min-h-11 items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5 fill-[#1877F2]"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
            {{ __('Facebook') }}
        </a>
    </div>
</div>
