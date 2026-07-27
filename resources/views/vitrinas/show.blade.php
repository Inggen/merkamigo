<x-layouts::public :title="$business->name">
    <div x-data="{ tab: 'inicio' }">
        <div class="h-40 bg-zinc-100 sm:h-56 dark:bg-zinc-800">
            @if ($business->storefront?->coverUrl())
                <img src="{{ $business->storefront->coverUrl() }}" class="h-full w-full object-cover" alt="">
            @endif
        </div>

        <div class="mx-auto max-w-3xl px-6">
            <div class="-mt-10 flex items-end gap-4 sm:-mt-12">
                <div class="flex size-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-white shadow-sm sm:size-24 dark:border-zinc-900 dark:bg-zinc-900">
                    @if ($business->logoUrl())
                        <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}">
                    @else
                        <flux:icon.building-storefront class="size-8 text-zinc-400" variant="outline" />
                    @endif
                </div>

                <div class="flex flex-1 flex-wrap items-center justify-between gap-3 pb-2">
                    <div>
                        <flux:heading size="xl">{{ $business->name }}</flux:heading>
                        <div class="mt-1 flex flex-wrap gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($business->category)
                                <flux:badge size="sm">{{ $business->category->name }}</flux:badge>
                            @endif
                            @if ($business->municipality)
                                <flux:badge size="sm">{{ $business->municipality->name }}{{ $business->zone ? ' · '.$business->zone : '' }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <flux:button
                            x-on:click="navigator.clipboard.writeText(window.location.href); $flux.toast('{{ __('Enlace copiado') }}')"
                            variant="ghost"
                            icon="share"
                        >
                            {{ __('Compartir') }}
                        </flux:button>

                        @if ($business->whatsapp_number)
                            <flux:button
                                href="https://wa.me/{{ preg_replace('/\D/', '', $business->whatsapp_number) }}?text={{ urlencode(__('Hola :name, te escribo desde Merkamigo 👋', ['name' => $business->name])) }}"
                                target="_blank"
                                variant="primary"
                                icon="chat-bubble-left-right"
                            >
                                {{ __('WhatsApp') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-6 border-b border-zinc-200 text-sm font-medium dark:border-zinc-700">
                <button type="button" x-on:click="tab = 'inicio'" :class="tab === 'inicio' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Inicio') }}</button>
                <button type="button" x-on:click="tab = 'productos'" :class="tab === 'productos' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Productos') }}</button>
                <button type="button" x-on:click="tab = 'opiniones'" :class="tab === 'opiniones' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Opiniones') }}</button>
                <button type="button" x-on:click="tab = 'informacion'" :class="tab === 'informacion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500'" class="border-b-2 pb-3">{{ __('Información') }}</button>
            </div>

            <div class="py-6">
                <div x-show="tab === 'inicio'">
                    @if ($business->storefront?->description)
                        <flux:text class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $business->storefront->description }}</flux:text>
                    @endif

                    @if ($products->isNotEmpty())
                        <flux:subheading class="mt-6 mb-3">{{ __('Productos destacados') }}</flux:subheading>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ($products->take(6) as $product)
                                @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'productos'" x-cloak>
                    @if ($products->isEmpty())
                        <x-states.empty title="{{ __('Todavía no hay productos publicados') }}" />
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ($products as $product)
                                @include('vitrinas.partials.product-card', ['business' => $business, 'product' => $product])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'opiniones'" x-cloak>
                    <x-states.empty
                        title="{{ __('Todavía no hay opiniones') }}"
                        description="{{ __('Las recomendaciones llegan más adelante, cuando haya pedidos confirmados reales.') }}"
                    />
                </div>

                <div x-show="tab === 'informacion'" x-cloak class="space-y-4">
                    @if ($business->hours['note'] ?? null)
                        <div>
                            <flux:subheading>{{ __('Horario') }}</flux:subheading>
                            <flux:text>{{ $business->hours['note'] }}</flux:text>
                        </div>
                    @endif

                    @if ($business->address)
                        <div>
                            <flux:subheading>{{ __('Dirección') }}</flux:subheading>
                            <flux:text>{{ $business->address }}</flux:text>
                        </div>
                    @endif

                    @if (! empty(array_filter($business->social_links ?? [])))
                        <div>
                            <flux:subheading>{{ __('Redes sociales') }}</flux:subheading>
                            <div class="flex flex-col gap-1">
                                @foreach (array_filter($business->social_links ?? []) as $link)
                                    <a href="{{ $link }}" target="_blank" class="text-brand-600 hover:underline">{{ $link }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($business->payment_info)
                        <div>
                            <flux:subheading>{{ __('Información de pago') }}</flux:subheading>
                            <flux:text class="whitespace-pre-line">{{ $business->payment_info }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::public>
