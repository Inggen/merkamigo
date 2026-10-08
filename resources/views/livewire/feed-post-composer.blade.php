<div>
    @if ($this->businesses->isNotEmpty() && $this->selectedBusiness)
        <section
            data-feed-post-composer
            class="mb-5 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            x-data="{ panel: null }"
        >
            <form wire:submit="publish">
                <div class="p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <a href="{{ route('vitrinas.show', $this->selectedBusiness) }}" wire:navigate class="shrink-0">
                            <div class="flex size-12 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white shadow-md ring-1 ring-brand-100 dark:border-zinc-900 dark:bg-zinc-950 dark:ring-brand-500/30">
                                @if ($this->selectedBusiness->logoUrl())
                                    <img src="{{ $this->selectedBusiness->logoUrl() }}" class="size-full object-cover" alt="{{ $this->selectedBusiness->name }}">
                                @else
                                    <flux:icon.building-storefront class="size-5 text-zinc-400" variant="outline" />
                                @endif
                            </div>
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-400">{{ __('Comparte desde tu vitrina') }}</p>
                                    <p class="truncate text-sm font-bold text-zinc-950 dark:text-white">{{ $this->selectedBusiness->name }}</p>
                                </div>

                                @if ($this->businesses->count() > 1)
                                    <flux:select wire:model.live="businessId" size="sm" class="w-52" aria-label="{{ __('Elegir vitrina') }}">
                                        @foreach ($this->businesses as $business)
                                            <flux:select.option :value="$business->id">{{ $business->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif
                            </div>

                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-zinc-200 bg-white/90 px-4 py-3 shadow-sm transition focus-within:border-brand-300 focus-within:ring-4 focus-within:ring-brand-50 dark:border-zinc-700 dark:bg-zinc-950/60 dark:focus-within:border-brand-500/50 dark:focus-within:ring-brand-500/10">
                        <textarea
                            wire:model="body"
                            rows="2"
                            maxlength="5000"
                            placeholder="{{ __('Cuéntale a tu comunidad qué hay de nuevo…') }}"
                            class="block !min-h-14 max-h-40 w-full resize-y border-0 bg-transparent p-0 text-base leading-6 text-zinc-800 outline-none ring-0 placeholder:text-zinc-400 focus:ring-0 dark:text-zinc-100"
                        ></textarea>
                    </div>
                    @error('body')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($eventId && ($selectedEvent = $this->events->firstWhere('id', $eventId)))
                        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50/80 p-3 dark:border-brand-500/30 dark:bg-brand-500/10">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm dark:bg-zinc-900 dark:text-brand-300">
                                <flux:icon.calendar-days class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-zinc-900 dark:text-white">{{ $selectedEvent->title }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $selectedEvent->starts_at->translatedFormat('d M Y, g:i a') }}</p>
                            </div>
                            <button type="button" wire:click="removeEvent" class="flex size-8 items-center justify-center rounded-full text-zinc-400 hover:bg-white hover:text-red-600 dark:hover:bg-zinc-900" aria-label="{{ __('Quitar evento') }}"><flux:icon.x-mark class="size-4" /></button>
                        </div>
                    @endif
                    @error('eventId')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($rewardId && ($selectedReward = $this->rewards->firstWhere('id', $rewardId)))
                        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                            <div class="size-12 shrink-0 overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
                                @if ($selectedReward->imageUrl())
                                    <img src="{{ $selectedReward->imageUrl() }}" class="size-full object-cover" alt="{{ $selectedReward->title }}">
                                @else
                                    <span class="flex size-full items-center justify-center text-amber-600"><flux:icon.gift class="size-6" /></span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-zinc-900 dark:text-white">{{ $selectedReward->title }}</p>
                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ number_format($selectedReward->points_cost, 0, ',', '.') }} {{ __('Merkapuntos') }}</p>
                            </div>
                            <button type="button" wire:click="removeReward" class="flex size-8 items-center justify-center rounded-full text-zinc-400 hover:bg-white hover:text-red-600 dark:hover:bg-zinc-900" aria-label="{{ __('Quitar recompensa') }}"><flux:icon.x-mark class="size-4" /></button>
                        </div>
                    @endif
                    @error('rewardId')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($photos !== [])
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($photos as $index => $photo)
                                <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                    <img src="{{ $photo->temporaryUrl() }}" class="aspect-square size-full object-cover" alt="{{ __('Vista previa de la imagen') }}">
                                    <button
                                        type="button"
                                        wire:click="removePhoto({{ $index }})"
                                        class="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full bg-zinc-950/75 text-white transition hover:bg-red-600"
                                        aria-label="{{ __('Quitar imagen') }}"
                                    >
                                        <flux:icon.x-mark class="size-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @error('photos')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($video)
                        <div class="relative mt-4 overflow-hidden rounded-2xl bg-zinc-950">
                            <video src="{{ $video->temporaryUrl() }}" class="max-h-72 w-full object-contain" controls preload="metadata"></video>
                            <button type="button" wire:click="removeVideo" class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full bg-zinc-950/80 text-white shadow transition hover:bg-red-600" aria-label="{{ __('Quitar video') }}"><flux:icon.x-mark class="size-5" /></button>
                        </div>
                    @endif
                    @error('video')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    @if ($this->products->isNotEmpty())
                        <div x-show="panel === 'products'" x-cloak x-transition class="mt-4 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/70">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Productos relacionados') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->products as $product)
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 transition has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:has-[:checked]:bg-brand-500/10 dark:has-[:checked]:text-brand-300">
                                        <input type="checkbox" wire:model="productIds" value="{{ $product->id }}" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                                        <span>{{ $product->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div x-show="panel === 'emoji'" x-cloak x-transition class="mt-4 rounded-2xl border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-zinc-500">{{ __('Emoticones') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['😊', '🎉', '❤️', '🔥', '👏', '✨', '🚀', '🙌', '📍', '🛍️', '🎁', '📅'] as $emoji)
                                <button type="button" wire:click="appendEmoji('{{ $emoji }}')" class="flex size-10 items-center justify-center rounded-xl text-xl transition hover:scale-110 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Agregar :emoji', ['emoji' => $emoji]) }}">{{ $emoji }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="panel === 'media'" x-cloak x-transition class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/60 p-4 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm dark:bg-zinc-900"><flux:icon.photo class="size-5" /></span>
                            <span><strong class="block text-sm text-zinc-900 dark:text-white">{{ __('Imágenes') }}</strong><small class="text-zinc-500">JPG, PNG o WEBP</small></span>
                            <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-sky-300 bg-sky-50/60 p-4 transition hover:bg-sky-50 dark:border-sky-500/30 dark:bg-sky-500/10">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm dark:bg-zinc-900"><flux:icon.video-camera class="size-5" /></span>
                            <span><strong class="block text-sm text-zinc-900 dark:text-white">{{ __('Video') }}</strong><small class="text-zinc-500">MP4, WEBM o MOV</small></span>
                            <input type="file" wire:model="video" accept="video/mp4,video/webm,video/quicktime" class="sr-only">
                        </label>
                    </div>

                    <div x-show="panel === 'events'" x-cloak x-transition class="mt-4 rounded-2xl border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-zinc-500">{{ __('Elige un evento próximo') }}</p>
                        @forelse ($this->events as $event)
                            <button type="button" wire:click="selectEvent({{ $event->id }})" x-on:click="panel = null" class="flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition hover:bg-brand-50 dark:hover:bg-brand-500/10">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><flux:icon.calendar-days class="size-5" /></span>
                                <span class="min-w-0"><strong class="block truncate text-sm text-zinc-900 dark:text-white">{{ $event->title }}</strong><small class="text-zinc-500">{{ $event->starts_at->translatedFormat('d M Y, g:i a') }}</small></span>
                            </button>
                        @empty
                            <p class="p-3 text-sm text-zinc-500">{{ __('No tienes eventos próximos disponibles para publicar.') }}</p>
                        @endforelse
                    </div>

                    <div x-show="panel === 'rewards'" x-cloak x-transition class="mt-4 rounded-2xl border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-zinc-500">{{ __('Elige una recompensa activa') }}</p>
                        @forelse ($this->rewards as $reward)
                            <button type="button" wire:click="selectReward({{ $reward->id }})" x-on:click="panel = null" class="flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition hover:bg-amber-50 dark:hover:bg-amber-500/10">
                                <div class="size-10 shrink-0 overflow-hidden rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">@if ($reward->imageUrl())<img src="{{ $reward->imageUrl() }}" class="size-full object-cover" alt="">@else<span class="flex size-full items-center justify-center"><flux:icon.gift class="size-5" /></span>@endif</div>
                                <span class="min-w-0"><strong class="block truncate text-sm text-zinc-900 dark:text-white">{{ $reward->title }}</strong><small class="font-semibold text-amber-700 dark:text-amber-300">{{ number_format($reward->points_cost, 0, ',', '.') }} {{ __('Merkapuntos') }}</small></span>
                            </button>
                        @empty
                            <p class="p-3 text-sm text-zinc-500">{{ __('No tienes recompensas activas disponibles para publicar.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-5">
                    <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                        <button type="button" x-on:click="panel = panel === 'emoji' ? null : 'emoji'" x-bind:aria-expanded="(panel === 'emoji').toString()" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs font-semibold text-zinc-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 hover:shadow dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-brand-500/40 dark:hover:text-brand-300 sm:text-sm"><flux:icon.face-smile class="size-5 text-amber-500" /><span>{{ __('Emoticón') }}</span></button>
                        <button type="button" x-on:click="panel = panel === 'media' ? null : 'media'" x-bind:aria-expanded="(panel === 'media').toString()" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs font-semibold text-zinc-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 hover:shadow dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-brand-500/40 dark:hover:text-brand-300 sm:text-sm"><flux:icon.photo class="size-5 text-emerald-600" /><span>{{ __('Multimedia') }}</span></button>

                        @if ($this->products->isNotEmpty())
                            <button type="button" x-on:click="panel = panel === 'products' ? null : 'products'" x-bind:aria-expanded="(panel === 'products').toString()" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs font-semibold text-zinc-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 hover:shadow dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-brand-500/40 dark:hover:text-brand-300 sm:text-sm"><flux:icon.shopping-bag class="size-5 text-orange-600" /><span>{{ __('Producto') }}</span></button>
                        @endif
                        <button type="button" x-on:click="panel = panel === 'events' ? null : 'events'" x-bind:aria-expanded="(panel === 'events').toString()" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs font-semibold text-zinc-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 hover:shadow dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-brand-500/40 dark:hover:text-brand-300 sm:text-sm"><flux:icon.calendar-days class="size-5 text-sky-600" /><span>{{ __('Evento') }}</span></button>
                        <button type="button" x-on:click="panel = panel === 'rewards' ? null : 'rewards'" x-bind:aria-expanded="(panel === 'rewards').toString()" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs font-semibold text-zinc-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-700 hover:shadow dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-brand-500/40 dark:hover:text-brand-300 sm:text-sm"><flux:icon.gift class="size-5 text-fuchsia-600" /><span>{{ __('Recompensa') }}</span></button>
                    </div>

                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="publish,photos" class="!rounded-xl !px-5 !text-white shadow-md shadow-brand-600/20 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-600/25">
                        <span wire:loading.remove wire:target="publish">{{ __('Publicar') }}</span>
                        <span wire:loading wire:target="publish">{{ __('Publicando…') }}</span>
                    </flux:button>
                </div>
            </form>
        </section>
    @endif
</div>
