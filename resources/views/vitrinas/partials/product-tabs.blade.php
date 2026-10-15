                <div x-ref="paymentTab" class="overflow-hidden rounded-xl  border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="product-tabs-nav flex flex-wrap gap-6 border-b border-zinc-200 px-5 pt-5 text-sm font-semibold dark:border-zinc-800 sm:px-6">
                        <button type="button" x-on:click="tab = 'descripcion'" :class="tab === 'descripcion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Descripción') }}</button>
                        <button type="button" x-on:click="tab = 'resenas'" :class="tab === 'resenas' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Reseñas') }}{{ $recommendationCount > 0 ? ' ('.$recommendationCount.')' : '' }}</button>
                        <button type="button" x-on:click="tab = 'pago'" :class="tab === 'pago' ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 dark:text-zinc-400'" class="border-b-2 pb-4 transition">{{ __('Medios de pago') }}</button>
                    </div>

                    <div class="product-tabs-body p-5 sm:p-6">
                        <div x-show="tab === 'descripcion'">
                            <div>
                                <p class="whitespace-pre-line text-base leading-8 text-zinc-600 dark:text-zinc-300">{{ $product->description ?: __('Este producto todavía no tiene descripción detallada.') }}</p>
                            </div>
                        </div>

                        <div x-show="tab === 'resenas'" x-cloak>
                            @if ($recommendations->isEmpty())
                                <x-states.empty class="product-tab-empty" title="{{ __('Todavía no hay reseñas') }}" description="{{ __('Sé el primero en dejar tu opinión sobre este negocio.') }}" />
                            @else
                                <div class="space-y-4">
                                    @foreach ($recommendations as $recommendation)
                                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-zinc-950 dark:text-white">{{ $recommendation->authorUser?->name ?? __('Cliente verificado') }}</span>
                                                @if ($recommendation->rating)
                                                    <div class="flex items-center gap-0.5 text-rose-500">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <flux:icon.star class="size-3.5" :variant="$i <= $recommendation->rating ? 'solid' : 'outline'" />
                                                        @endfor
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $recommendation->body }}</p>
                                            @if ($recommendation->business_response)
                                                <div class="mt-3 rounded-2xl bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                    <span class="font-semibold text-zinc-950 dark:text-white">{{ __('Respuesta del negocio:') }}</span>
                                                    {{ $recommendation->business_response }}
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div x-show="tab === 'pago'" x-cloak>
                            @if ($acceptedPaymentMethods->isNotEmpty() || filled($business->payment_info))
                                @if ($acceptedPaymentMethods->isNotEmpty())
                                    <div class="grid grid-cols-4 gap-3 md:grid-cols-3 lg:grid-cols-8">
                                        @foreach ($acceptedPaymentMethods as $method)
                                            <div class="flex min-w-0 items-center justify-center" title="{{ $method->name }}">
                                                @if ($method->logoUrl())
                                                    <img src="{{ $method->logoUrl() }}" alt="{{ $method->name }}" class="h-auto max-h-14 w-full max-w-24 rounded-lg object-contain">
                                                @else
                                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $method->name }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (filled($business->payment_info))
                                    <p class="whitespace-pre-line text-base leading-8 text-zinc-600 dark:text-zinc-300 {{ $acceptedPaymentMethods->isNotEmpty() ? 'mt-5' : '' }}">{{ $business->payment_info }}</p>
                                @endif
                            @else
                                <x-states.empty class="product-tab-empty" title="{{ __('Todavía no hay información de pago') }}" />
                            @endif
                        </div>
                    </div>
                </div>

