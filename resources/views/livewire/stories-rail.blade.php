{{-- Raíz en un único <div> obligatoria (ver el comentario en favorite-button.blade.php): sin esto, cuando no hay estados activos el componente no renderiza ningún elemento y Livewire no tiene dónde anclar wire:id. --}}
<div>
    @if ($this->businessesWithStories->isNotEmpty())
        <div class="flex gap-4 overflow-x-auto pb-1">
            @foreach ($this->businessesWithStories as $entry)
                <button
                    type="button"
                    wire:click="open({{ $entry['id'] }})"
                    class="flex shrink-0 flex-col items-center gap-1"
                >
                    <span class="flex size-16 items-center justify-center rounded-full p-0.5 {{ $entry['allSeen'] ? 'bg-zinc-300 dark:bg-zinc-700' : 'bg-gradient-to-tr from-brand-500 to-amber-400' }}">
                        <span class="flex size-full items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white dark:border-zinc-900 dark:bg-zinc-950">
                            @if ($entry['logo'])
                                <img src="{{ $entry['logo'] }}" class="size-full object-cover" alt="{{ $entry['name'] }}" loading="lazy">
                            @else
                                <flux:icon.building-storefront class="size-6 text-zinc-400" variant="outline" />
                            @endif
                        </span>
                    </span>
                    <span class="max-w-16 truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $entry['name'] }}</span>
                </button>
            @endforeach
        </div>

        @if ($viewingBusinessId)
            @php
                $viewingStories = $this->businessesWithStories->firstWhere('id', $viewingBusinessId)['stories'];
                $story = $viewingStories->get($viewingIndex);
            @endphp

            @if ($story)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" wire:key="story-viewer-{{ $story->id }}">
                    <button type="button" wire:click="close" class="absolute top-4 right-4 text-white/80 hover:text-white">
                        <flux:icon.x-mark class="size-7" variant="outline" />
                    </button>

                    <div class="relative flex w-full max-w-sm flex-col overflow-hidden rounded-2xl bg-black">
                        <div class="flex gap-1 p-2">
                            @foreach ($viewingStories as $index => $s)
                                <div class="h-1 flex-1 rounded-full {{ $index <= $viewingIndex ? 'bg-white' : 'bg-white/30' }}"></div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2 px-3 pb-2 text-white">
                            <span class="text-sm font-semibold">{{ $story->business->name }}</span>
                            <span class="text-xs text-white/70">{{ $story->created_at->diffForHumans() }}</span>
                            @if ($story->activePromotion)
                                <span class="rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-semibold text-zinc-950">{{ __('Patrocinado') }}</span>
                            @endif
                        </div>

                        <div class="relative aspect-[9/16] w-full bg-zinc-900">
                            <img src="{{ $story->imageUrl() }}" class="size-full object-cover" alt="{{ $story->caption }}">

                            <button type="button" wire:click="previous" class="absolute inset-y-0 left-0 w-1/3" aria-label="{{ __('Anterior') }}"></button>
                            <button type="button" wire:click="next" class="absolute inset-y-0 right-0 w-1/3" aria-label="{{ __('Siguiente') }}"></button>
                        </div>

                        <div class="space-y-2 p-3 text-white">
                            @if ($story->caption)
                                <p class="text-sm">{{ $story->caption }}</p>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                @if ($story->activePromotion)
                                    <a href="{{ route('promotions.click', $story->activePromotion) }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white">
                                        <flux:icon.megaphone class="size-3.5" variant="outline" />
                                        {{ __('Ver destacado') }}
                                    </a>
                                @endif
                                @if ($story->product)
                                    <a href="{{ route('vitrinas.product', [$story->business, $story->product]) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-zinc-900">
                                        <flux:icon.cube class="size-3.5" variant="outline" />
                                        {{ __('Ver producto') }}
                                    </a>
                                @endif

                                @if ($story->business->whatsapp_number)
                                    <a href="{{ route('vitrinas.whatsapp', $story->business) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                        <flux:icon.chat-bubble-left-right class="size-3.5" variant="outline" />
                                        {{ __('WhatsApp') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif
</div>
