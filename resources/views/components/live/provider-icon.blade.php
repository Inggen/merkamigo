@props(['provider'])

<span data-provider-icon="{{ $provider }}" {{ $attributes->class('flex size-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-900') }}>
    @switch($provider)
        @case('youtube')
            <svg viewBox="0 0 24 24" class="size-6 text-red-600" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4L15.8 12l-6.2 3.6Z"/></svg>
            @break
        @case('facebook')
            <svg viewBox="0 0 24 24" class="size-6 text-[#1877F2]" fill="currentColor" aria-hidden="true"><path d="M24 12a12 12 0 1 0-13.9 11.9v-8.4h-3V12h3V9.3c0-3 1.8-4.7 4.6-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12Z"/></svg>
            @break
        @case('instagram')
            <svg viewBox="0 0 24 24" class="size-6 text-fuchsia-600" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            @break
        @case('tiktok')
            <svg viewBox="0 0 24 24" class="size-6 text-zinc-950 dark:text-white" fill="currentColor" aria-hidden="true"><path d="M16.6 2c.4 2.4 1.8 3.8 4.2 4.1v3.5a10 10 0 0 1-4.2-1v7.2A6.2 6.2 0 1 1 11.2 9v3.6a2.7 2.7 0 1 0 1.8 2.6V2h3.6Z"/></svg>
            @break
        @default
            <flux:icon.globe-alt class="size-6 text-zinc-600" />
    @endswitch
    <span class="sr-only">{{ App\Domain\Social\Models\LiveStream::providerLabel($provider) }}</span>
</span>
