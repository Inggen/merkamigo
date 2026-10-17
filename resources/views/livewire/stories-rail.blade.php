{{-- Raíz en un único <div> obligatoria (ver el comentario en favorite-button.blade.php): sin esto, cuando no hay estados activos el componente no renderiza ningún elemento y Livewire no tiene dónde anclar wire:id. --}}
<div>
    @if ($this->businessesWithStories->isNotEmpty() || $this->ownBusiness)
        <div class="flex gap-4 overflow-x-auto pb-1">
            {{--
                Pedido del usuario (2026-10-16): poder crear un estado
                desde Comunidad sin salir del feed, con la misma modal
                que en la zona de emprendedores — `CreateStoryModal` trae
                su propio botón disparador por defecto (ver `$slot` en su
                vista), acá se reemplaza por este círculo "+" al estilo
                Instagram para que encaje en el carrusel.
            --}}
            @if ($this->ownBusiness)
                <livewire:create-story-modal :business="$this->ownBusiness" :key="'create-story-rail-'.$this->ownBusiness->id">
                    <button type="button" class="flex shrink-0 flex-col items-center gap-1">
                        <span class="relative flex size-16 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                            @if ($this->ownBusiness->logoUrl())
                                <img src="{{ $this->ownBusiness->logoUrl() }}" class="size-full object-cover opacity-60" alt="{{ $this->ownBusiness->name }}" loading="lazy">
                            @else
                                <flux:icon.building-storefront class="size-6 text-zinc-400" variant="outline" />
                            @endif
                            <span class="absolute inset-0 flex items-center justify-center bg-black/10">
                                <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-white shadow">
                                    <flux:icon.plus class="size-4" variant="solid" />
                                </span>
                            </span>
                        </span>
                        <span class="max-w-16 truncate text-xs text-zinc-600 dark:text-zinc-300">{{ __('Tu estado') }}</span>
                    </button>
                </livewire:create-story-modal>
            @endif

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

                    <div x-data="{ muted: false }" class="relative aspect-[9/16] w-full max-w-sm overflow-hidden rounded-2xl bg-zinc-900">
                        @if ($story->isVideo())
                            {{--
                                Pedido del usuario (2026-10-16): el estado debe
                                iniciar a volumen completo, y el botón de
                                silenciar no hacía nada — `x-bind:muted="muted"`
                                solo refleja el *atributo* HTML `muted`, pero el
                                audio real de un <video> lo controla su
                                *propiedad* `.muted`, y el navegador no las
                                mantiene sincronizadas después de que el
                                elemento ya existe en el DOM. Por eso acá se
                                controla la propiedad directamente en vez de
                                confiar en el binding declarativo o en el
                                atributo HTML `autoplay` (que solo autoriza el
                                navegador a reproducir solo si ya está
                                silenciado): se intenta reproducir con sonido
                                primero y, si el navegador lo bloquea por no
                                venir de un gesto directo del usuario (abrir
                                este visor es una petición al servidor de por
                                medio), recién ahí cae a silenciado.
                            --}}
                            <video
                                x-ref="video"
                                x-init="
                                    $el.muted = false;
                                    $el.play().catch(() => {
                                        muted = true;
                                        $el.muted = true;
                                        $el.play();
                                    });
                                "
                                src="{{ $story->imageUrl() }}"
                                class="absolute inset-0 size-full object-cover"
                                playsinline
                                x-on:ended="$wire.next()"
                                wire:key="story-video-{{ $story->id }}"
                            ></video>
                        @else
                            <img src="{{ $story->imageUrl() }}" class="absolute inset-0 size-full object-cover" alt="{{ $story->caption }}">
                        @endif

                        <button type="button" wire:click="previous" class="absolute inset-y-0 left-0 z-10 w-1/3" aria-label="{{ __('Anterior') }}"></button>
                        <button type="button" wire:click="next" class="absolute inset-y-0 right-0 z-10 w-1/3" aria-label="{{ __('Siguiente') }}"></button>

                        {{-- Mismo patrón de degradado que la vista previa de Estados (`⚡estados.blade.php`): texto superpuesto sobre el video, no en barras aparte. --}}
                        <div class="absolute inset-x-0 top-0 z-20 bg-gradient-to-b from-black/75 to-transparent px-3 pb-10 pt-3">
                            <div class="mb-3 flex gap-1">
                                @foreach ($viewingStories as $index => $s)
                                    <div class="h-1 flex-1 rounded-full {{ $index <= $viewingIndex ? 'bg-white' : 'bg-white/30' }}"></div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-2 text-white">
                                <span class="text-sm font-semibold">{{ $story->business->name }}</span>
                                <span class="text-xs text-white/70">{{ $story->created_at->diffForHumans() }}</span>
                                @if ($story->activePromotion)
                                    <span class="rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-semibold text-zinc-950">{{ __('Patrocinado') }}</span>
                                @endif
                                @if ($story->isVideo())
                                    <button
                                        type="button"
                                        x-on:click="muted = ! muted; $refs.video.muted = muted"
                                        class="ms-auto flex size-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition hover:bg-white/25"
                                        x-bind:aria-label="muted ? @js(__('Activar sonido')) : @js(__('Silenciar'))"
                                    >
                                        <flux:icon.speaker-x-mark x-show="muted" class="size-4" variant="outline" />
                                        <flux:icon.speaker-wave x-show="!muted" x-cloak class="size-4" variant="outline" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 z-20 space-y-2 bg-gradient-to-t from-black/90 via-black/55 to-transparent px-4 pb-4 pt-16 text-white">
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
                                    <a href="{{ route('vitrinas.contact.internal', $story->business) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                        <flux:icon.chat-bubble-left-right class="size-3.5" variant="outline" />
                                        {{ __('Contactar') }}
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
