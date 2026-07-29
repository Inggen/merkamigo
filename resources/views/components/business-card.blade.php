@props(['business'])

<div class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
    <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="block">
        <div class="relative aspect-[4/3] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($business->storefront?->coverUrl())
                <img src="{{ $business->storefront->coverUrl() }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" alt="{{ __('Portada de :name', ['name' => $business->name]) }}">
            @elseif ($business->logoUrl())
                <img src="{{ $business->logoUrl() }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" alt="{{ $business->name }}">
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <flux:icon.building-storefront class="size-10 text-zinc-300 dark:text-zinc-600" variant="outline" />
                </div>
            @endif

            @if ($business->isFeatured())
                <flux:badge size="sm" color="amber" class="absolute top-2 left-2">{{ __('Destacado') }}</flux:badge>
            @endif
        </div>

        <div class="p-4">
            <div class="truncate font-semibold text-carbon dark:text-white">{{ $business->name }}</div>
            <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($business->category)
                    <span class="truncate">{{ $business->category->name }}</span>
                @endif
                @if ($business->zone)
                    <span>·</span>
                    <span class="truncate">{{ $business->zone }}</span>
                @endif
            </div>
        </div>
    </a>

    <div class="absolute top-3 right-3">
        <livewire:favorite-button :favoritable="$business" :compact="true" :key="'business-card-'.$business->id" />
    </div>
</div>
