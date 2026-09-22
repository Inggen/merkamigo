<aside class="feed-right-sidebar feed-sticky-sidebar space-y-4">
    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-3 flex items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('Negocios cerca de ti') }}</flux:heading>
            <flux:link :href="route('explorar')" wire:navigate class="shrink-0 text-xs">{{ __('Ver todos') }}</flux:link>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($nearbyBusinesses as $business)
                <div class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                    <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="size-12 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                            @if ($business->logoUrl())
                                <img src="{{ $business->logoUrl() }}" alt="{{ $business->name }}" class="size-full object-cover" loading="lazy">
                            @else
                                <div class="flex size-full items-center justify-center">
                                    <flux:icon.building-storefront class="size-5 text-zinc-400" variant="outline" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $business->name }}</p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $business->category?->name ?? $business->municipality?->name }}
                            </p>
                        </div>
                    </a>

                    <livewire:favorite-button :favoritable="$business" compact :key="'nearby-business-'.$business->id" />
                </div>
            @empty
                <p class="py-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pronto encontrarás negocios cerca de ti.') }}</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-3 flex items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('Reels para ti') }}</flux:heading>
            <flux:link :href="route('reels')" wire:navigate class="shrink-0 text-xs">{{ __('Ver todos') }}</flux:link>
        </div>

        <div class="space-y-3">
            @forelse ($reels as $reel)
                @php $video = $reel->media->first(); @endphp
                <a href="{{ route('reels') }}" wire:navigate class="group flex items-center gap-3">
                    <div class="relative size-16 shrink-0 overflow-hidden rounded-xl bg-zinc-950">
                        @if ($video)
                            <video src="{{ $video->url() }}" class="size-full object-cover" muted playsinline preload="metadata"></video>
                        @endif
                        <span class="absolute inset-0 flex items-center justify-center bg-black/15">
                            <flux:icon.play class="size-6 text-white drop-shadow" variant="solid" />
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $reel->business->name }}</p>
                        <p class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $reel->body ?: __('Ver reel') }}</p>
                    </div>
                </a>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Todavía no hay reels publicados.') }}</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl bg-gradient-to-br from-brand-50 to-red-50 p-5 text-center shadow-sm dark:from-brand-950/40 dark:to-red-950/30">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm dark:bg-zinc-900">
            <flux:icon.building-storefront class="size-6" variant="outline" />
        </div>
        <flux:heading size="lg" class="mt-3">{{ __('Haz crecer tu negocio') }}</flux:heading>
        <flux:text class="mt-1 text-sm">{{ __('Publica tus productos y conecta con clientes de tu comunidad.') }}</flux:text>
        <flux:button :href="route('emprendedores.bienvenida')" wire:navigate variant="primary" class="mt-4 w-full">
            {{ __('Crear vitrina') }}
        </flux:button>
    </section>
</aside>
