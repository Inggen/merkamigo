                        <div class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">
                            @if ($product->hasActivePromo())
                                <span class="mr-2 text-lg font-normal text-zinc-400 line-through">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                                ${{ number_format((float) $product->promo_price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'exacto' && $product->price)
                                ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'desde' && $product->price)
                                {{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @elseif ($product->price_type === 'consultar')
                                {{ __('Consultar precio') }}
                            @endif

                            @if ($product->unit)
                                <span class="text-base font-normal text-zinc-500">/ {{ $product->unit }}</span>
                            @endif
                        </div>

                        @if ($product->variants->isNotEmpty())
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <h2 class="mb-3 text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Variantes') }}</h2>
                                <div class="space-y-2 text-sm">
                                    @foreach ($product->variants as $variant)
                                        <div class="flex items-center justify-between gap-4 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800/70">
                                            <span>{{ $variant->label }}</span>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                                @if ($variant->price)
                                                    ${{ number_format((float) $variant->price, 0, ',', '.') }}
                                                @elseif ($product->price)
                                                    ${{ number_format((float) $product->price, 0, ',', '.') }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @unless ($product->isSubscription())
                            <div class="space-y-3">
                                <div class="text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Cantidad') }}</div>
                                <div class="inline-flex items-center rounded-2xl border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
                                    <button type="button" x-on:click="quantity = Math.max(1, quantity - 1)" class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">-</button>
                                    <div class="min-w-12 text-center text-lg font-semibold text-zinc-950 dark:text-white" x-text="quantity"></div>
                                    <button type="button" x-on:click="quantity = quantity + 1" class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">+</button>
                                </div>

                                @if ($hasNumericUnitPrice)
                                    <div class="flex items-center justify-between text-sm" x-show="quantity > 1">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</span>
                                        <span class="font-semibold text-zinc-950 dark:text-white" x-text="'$' + formatPrice(unitPrice * quantity)"></span>
                                    </div>
                                @endif
                            </div>

                            @if ($canPayOnline)
                                <a
                                    x-bind:href="`{{ route('marketplace.checkout.create', $product) }}?cantidad=${quantity}&promotion={{ request()->integer('promotion') }}`"
                                    x-on:click.prevent="window.merkamigoOpenWompiCheckout($el.href)"
                                    class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-600 px-5 py-4 text-lg font-semibold text-white transition hover:bg-brand-700"
                                >
                                    <flux:icon.credit-card class="size-6" />
                                    {{ __('Comprar ahora') }}
                                </a>
                                <flux:text class="text-center text-xs text-zinc-400">
                                    {{ __('Pago en línea directo a :business, procesado por Wompi.', ['business' => $business->name]) }}
                                </flux:text>
                            @endif
                        @else
                            @include('vitrinas.partials.subscribe-form', ['business' => $business, 'product' => $product])
                        @endunless

                        @if (! $canPayOnline)
                            <a
                                href="{{ route('vitrinas.contact', ['business' => $business, 'context_type' => 'product', 'context_id' => $product->id]) }}"
                                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-600 px-5 py-4 text-lg font-semibold text-white transition hover:bg-brand-700"
                            >
                                <flux:icon.chat-bubble-left-right class="size-6" />
                                {{ $product->isSoldOut() ? __('Consultar disponibilidad') : ($isElegant && $business->contact_channel === 'whatsapp' ? __('Pedir por WhatsApp') : __('Contactar')) }}
                            </a>
                        @endif
