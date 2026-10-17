<div>
    <flux:modal.trigger name="create-story-{{ $this->businessId }}">
        @if (isset($slot) && trim((string) $slot) !== '')
            {{ $slot }}
        @else
            <flux:button type="button" variant="primary" icon="plus" class="!justify-center">
                {{ __('Crear nuevo estado') }}
            </flux:button>
        @endif
    </flux:modal.trigger>

    <flux:modal name="create-story-{{ $this->businessId }}" class="w-full !max-w-6xl">
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Crear nuevo estado') }}</flux:heading>
            <flux:subheading>{{ __('Comparte novedades, promociones o contenido del día. Durará 24 horas.') }}</flux:subheading>
        </div>

        <form wire:submit="save" class="grid overflow-hidden rounded-2xl border border-zinc-200 lg:grid-cols-2 dark:border-zinc-700">
            <div class="space-y-4 p-5 lg:border-e lg:border-zinc-200 dark:lg:border-zinc-700">
                <div class="flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">1</span>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Contenido del estado') }}</h2>
                </div>

                <flux:select wire:model.live="format" :label="__('Formato')">
                    <flux:select.option value="imagen">{{ __('Imagen') }}</flux:select.option>
                    <flux:select.option value="video">{{ __('Video') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="type" :label="__('Tipo')">
                    <flux:select.option value="promocion">{{ __('Promoción') }}</flux:select.option>
                    <flux:select.option value="producto">{{ __('Producto') }}</flux:select.option>
                    <flux:select.option value="servicio">{{ __('Servicio') }}</flux:select.option>
                </flux:select>

                @if ($format === 'video')
                    <x-forms.image-upload-field
                        wire:model="video"
                        accept="video/mp4,video/webm,video/quicktime"
                        :title="__('Video')"
                        hint="Selecciona un video MP4, WEBM o MOV (máx. 50 MB)."
                        :preview-url="$video?->temporaryUrl()"
                        :preview-alt="__('Vista previa del estado')"
                        remove-action="removeVideo"
                        :error="$errors->first('video')"
                        is-video
                    />
                @else
                    <x-forms.image-upload-field
                        wire:model="photo"
                        accept="image/*"
                        :title="__('Imagen')"
                        :preview-url="$photo?->temporaryUrl()"
                        :preview-alt="__('Vista previa del estado')"
                        remove-action="removePhoto"
                        :error="$errors->first('photo')"
                    />
                @endif

                <div>
                    <div class="flex items-center justify-between gap-2">
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200">{{ __('Texto (opcional)') }}</flux:text>
                        <flux:button type="button" size="sm" variant="ghost" icon="sparkles" wire:click="suggestWithAi" wire:loading.attr="disabled" class="shrink-0 !text-brand-600 hover:!bg-brand-50 dark:!text-brand-400 dark:hover:!bg-brand-950">
                            {{ __('Sugerir con IA') }}
                        </flux:button>
                    </div>
                    <flux:input wire:model.live.debounce.300ms="caption" maxlength="280" placeholder="{{ __('Ej: ¡Hoy 20% de descuento!') }}" class="mt-1 w-full" />
                    @error('caption')
                        <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>

                @if ($this->availableProducts->isNotEmpty())
                    <flux:select wire:model.live="product_id" :label="__('Vincular producto (opcional)')">
                        <flux:select.option value="">{{ __('Ninguno') }}</flux:select.option>
                        @foreach ($this->availableProducts as $product)
                            <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Asocia un producto de tu vitrina para que los usuarios puedan verlo.') }}</p>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled">
                        {{ __('Publicar estado') }}
                    </flux:button>
                </div>
            </div>

            @php
                $selectedProduct = $product_id ? $this->availableProducts->firstWhere('id', (int) $product_id) : null;
            @endphp
            <div class="bg-zinc-50 p-5 dark:bg-zinc-900/70">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">2</span>
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Vista previa') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Así se verá tu estado en la plataforma.') }}</p>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-[310px] rounded-[2.5rem] bg-zinc-950 p-2.5 shadow-2xl ring-1 ring-zinc-900/20">
                    <div class="relative aspect-[9/16] overflow-hidden rounded-[2rem] bg-zinc-800 text-white">
                        @if ($format === 'video' && $video)
                            <video src="{{ $video->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" autoplay muted loop playsinline></video>
                        @elseif ($format === 'imagen' && $photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" alt="{{ __('Vista previa del estado') }}">
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-zinc-700 to-zinc-900 px-6 text-center text-white/65">
                                <flux:icon :name="$format === 'video' ? 'video-camera' : 'photo'" class="size-12" />
                                <p class="text-sm">{{ $format === 'video' ? __('Selecciona un video para ver la vista previa.') : __('Selecciona una imagen para ver la vista previa.') }}</p>
                            </div>
                        @endif

                        <div class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/75 to-transparent px-3 pb-10 pt-3">
                            <div class="mb-3 h-1 rounded-full bg-white/90"></div>
                            <div class="flex items-center gap-2">
                                <div class="flex size-8 items-center justify-center rounded-full bg-white text-xs font-bold text-brand-600">M</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold">{{ $this->business->name }}</p>
                                    <p class="text-[10px] text-white/75">{{ __('hace un momento') }}</p>
                                </div>
                                <flux:icon.x-mark class="size-5" />
                            </div>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent px-4 pb-4 pt-20">
                            <p class="min-h-5 text-sm font-medium">{{ $caption ?: __('Tu texto aparecerá aquí…') }}</p>
                            @if ($selectedProduct)
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-zinc-900">
                                    <flux:icon.cube class="size-3" /> {{ $selectedProduct->name }}
                                </span>
                            @endif
                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex-1 rounded-full border border-white/70 px-3 py-1.5 text-xs text-white/75">{{ __('Responder…') }}</div>
                                <flux:icon.heart class="size-6" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
