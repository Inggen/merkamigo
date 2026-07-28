<a href="{{ route('vitrinas.show', $business) }}" class="block overflow-hidden rounded-xl border border-zinc-200 hover:border-brand-300 dark:border-zinc-700" wire:navigate>
    <div class="flex items-center gap-3 p-4">
        <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            @if ($business->logoUrl())
                <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}">
            @else
                <flux:icon.building-storefront class="size-5 text-zinc-400" variant="outline" />
            @endif
        </div>

        <div class="min-w-0">
            <div class="truncate font-medium">{{ $business->name }}</div>
            @if ($business->category)
                <div class="truncate text-sm text-zinc-500">{{ $business->category->name }}</div>
            @endif
        </div>
    </div>
</a>
