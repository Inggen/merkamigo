<?php

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Actions\EngageWithLiveStream;
use App\Domain\Social\Actions\ManageLivePoll;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Storefronts\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\View\View;

new #[Layout('layouts::cliente')] class extends Component
{
    #[Locked]
    public int $liveStreamId;

    #[Locked]
    public int $lastReactionId = 0;

    public string $message = '';

    public bool $reacted = false;

    public ?int $selectedProductId = null;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public ?int $pollVote = null;

    public ?int $visiblePollId = null;

    /** @var array<string, array{product_id: int, variant_id: int|null, quantity: int}> */
    public array $cart = [];

    public function mount(LiveStream $liveStream): void
    {
        abort_if($liveStream->status === LiveStream::BORRADOR && ! $liveStream->scheduled_at, 404);

        $this->liveStreamId = $liveStream->id;
        $this->lastReactionId = (int) ($liveStream->reactions()->max('id') ?? 0);
        $this->cart = session()->get("live_cart.{$liveStream->id}", []);
        $this->heartbeat();
        $this->reacted = $liveStream->reactions()
            ->where('visitor_hash', app(EngageWithLiveStream::class)->heartbeat($liveStream, request(), Auth::user()))
            ->where('type', 'heart')
            ->exists();
    }

    public function rendering(View $view): void
    {
        $live = $this->live;

        $view->layoutData([
            'title' => $live->title,
            'description' => $live->description ?: __('Transmisión en vivo de :business en Merkamigo.', ['business' => $live->business->name]),
            'image' => $live->coverUrl(),
            'canonical' => route('live.show', $live),
            'ogType' => 'video.other',
            'pageSchemaType' => $live->scheduled_at ? 'Event' : 'WebPage',
            'pageSchemaData' => array_filter([
                'startDate' => $live->scheduled_at?->toIso8601String(),
                'eventStatus' => match ($live->status) {
                    LiveStream::EN_VIVO => 'https://schema.org/EventInProgress',
                    LiveStream::FINALIZADO => 'https://schema.org/EventCompleted',
                    default => 'https://schema.org/EventScheduled',
                },
                'location' => [
                    '@type' => 'VirtualLocation',
                    'url' => route('live.show', $live),
                ],
            ]),
        ]);
    }

    #[Computed]
    public function live(): LiveStream
    {
        return LiveStream::with(['business.storefront', 'business.municipality', 'products.media', 'products.variants', 'pinnedProduct.media', 'pinnedProduct.variants', 'productEvents.product.media', 'activePromotion', 'activePoll.votes'])
            ->findOrFail($this->liveStreamId);
    }

    #[Computed]
    public function messages()
    {
        return $this->live->messages()
            ->where('status', 'publicado')
            ->with('user')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse();
    }

    #[Computed]
    public function presentedProducts()
    {
        $presentedIds = $this->live->productEvents
            ->where('action', 'featured')
            ->pluck('product_id')
            ->unique();

        return $this->live->products->whereIn('id', $presentedIds)->values();
    }

    public function heartbeat(): void
    {
        if ($this->live->isLive()) {
            app(EngageWithLiveStream::class)->heartbeat($this->live, request(), Auth::user());
        }

        $reactions = $this->live->reactions()
            ->where('id', '>', $this->lastReactionId)
            ->oldest('id')
            ->limit(12)
            ->get(['id', 'type']);

        foreach ($reactions as $reaction) {
            $emoji = $reaction->type === 'heart'
                ? '❤️'
                : (EngageWithLiveStream::REACTION_EMOJIS[$reaction->type] ?? null);

            if ($emoji) {
                $this->dispatch('live-reaction', emoji: $emoji);
            }

            $this->lastReactionId = $reaction->id;
        }

        unset($this->live, $this->messages, $this->presentedProducts);

        $activePollId = $this->live->activePoll?->id;

        if ($this->visiblePollId !== $activePollId) {
            $this->visiblePollId = $activePollId;
            $this->pollVote = null;
        }
    }

    public function toggleReaction(): void
    {
        $this->reacted = app(EngageWithLiveStream::class)->toggleReaction($this->live, request(), Auth::user());
        unset($this->live);
    }

    public function sendReaction(string $type): void
    {
        abort_unless(isset(EngageWithLiveStream::REACTION_EMOJIS[$type]), 422);
        $reaction = app(EngageWithLiveStream::class)->react($this->live, $type, request(), Auth::user());
        $this->lastReactionId = $reaction->id;
        $this->dispatch('live-reaction', emoji: EngageWithLiveStream::REACTION_EMOJIS[$type]);
        unset($this->live);
    }

    public function sendMessage(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        try {
            app(EngageWithLiveStream::class)->message($this->live, $this->message, Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->reset('message');
        unset($this->messages);
    }

    public function voteOnPoll(int $optionIndex): void
    {
        $poll = $this->live->activePoll;
        abort_unless($poll, 404);

        app(ManageLivePoll::class)->vote($poll, $optionIndex, request(), Auth::user());
        $this->pollVote = $optionIndex;
        unset($this->live);
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        return $this->selectedProductId
            ? $this->live->products->firstWhere('id', $this->selectedProductId)
            : null;
    }

    #[Computed]
    public function cartItems()
    {
        return collect($this->cart)->map(function (array $item) {
            $product = $this->live->products->firstWhere('id', $item['product_id']);

            if (! $product) {
                return null;
            }

            $variant = filled($item['variant_id']) ? $product->variants->firstWhere('id', $item['variant_id']) : null;
            $unitPrice = $variant?->price
                ?? ($product->hasActivePromo() && filled($product->promo_price) ? $product->promo_price : $product->price);

            return [
                'key' => $product->id.'-'.($variant?->id ?? 0),
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'unit_price' => (float) $unitPrice,
                'total' => (float) $unitPrice * $item['quantity'],
            ];
        })->filter()->values();
    }

    public function openProduct(int $productId): void
    {
        $product = $this->presentedProducts->firstWhere('id', $productId);
        abort_unless($product, 404);

        $this->selectedProductId = $product->id;
        $this->selectedVariantId = null;
        $this->quantity = 1;
        unset($this->selectedProduct);

        app(RegisterAnalyticsEvent::class)->handle($this->live->business, AnalyticsEvent::LIVE_PRODUCT_CLICK, $product, request());
        Flux::modal('live-product')->show();
    }

    public function addToCart(bool $openCart = false): void
    {
        $product = $this->selectedProduct;

        if (! $product || $product->isSoldOut()) {
            Flux::toast(variant: 'warning', text: __('Este producto ya no está disponible.'));

            return;
        }

        if ($this->selectedVariantId && ! $product->variants->contains('id', $this->selectedVariantId)) {
            $this->addError('selectedVariantId', __('Selecciona una variante válida.'));

            return;
        }

        $this->quantity = max(1, min(99, $this->quantity));
        $key = $product->id.'-'.($this->selectedVariantId ?? 0);
        $existingQuantity = $this->cart[$key]['quantity'] ?? 0;
        $this->cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $this->selectedVariantId,
            'quantity' => min(99, $existingQuantity + $this->quantity),
        ];
        session()->put("live_cart.{$this->liveStreamId}", $this->cart);
        unset($this->cartItems);

        app(RegisterAnalyticsEvent::class)->handle($this->live->business, AnalyticsEvent::LIVE_CART_ADD, $product, request());
        Flux::modal('live-product')->close();

        if ($openCart) {
            Flux::modal('live-cart')->show();
        } else {
            Flux::toast(variant: 'success', text: __('Producto agregado al carrito.'));
        }
    }

    public function changeCartQuantity(string $key, int $change): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        $this->cart[$key]['quantity'] = max(1, min(99, $this->cart[$key]['quantity'] + $change));
        session()->put("live_cart.{$this->liveStreamId}", $this->cart);
        unset($this->cartItems);
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
        session()->put("live_cart.{$this->liveStreamId}", $this->cart);
        unset($this->cartItems);
    }

    public function registerCheckout(): void
    {
        app(RegisterAnalyticsEvent::class)->handle($this->live->business, AnalyticsEvent::LIVE_CHECKOUT_STARTED, $this->live, request());
    }
}; ?>

<main
    wire:poll.3s="heartbeat"
    x-on:live-reaction.window="burstReaction($event.detail.emoji)"
    class="min-h-screen bg-zinc-950 text-white"
    x-data="{
        mobilePanel: null,
        mobilePollOpen: false,
        mobileChat: true,
        soundEnabled: false,
        floatingReactions: [],
        burstReaction(emoji) {
            const id = Date.now() + Math.random();
            this.floatingReactions.push({ id, emoji, x: Math.round(Math.random() * 34) - 17 });
            setTimeout(() => this.floatingReactions = this.floatingReactions.filter(item => item.id !== id), 1800);
        },
        toggleSound() {
            const video = this.$refs.liveFrame?.querySelector('video');
            if (! video) return;
            video.muted = ! video.muted;
            this.soundEnabled = ! video.muted;
        },
        async shareLive() {
            const shareData = {
                title: @js($this->live->title),
                text: @js(__('Mira :title en Merkamigo', ['title' => $this->live->title])),
                url: @js(route('live.show', $this->live)),
            };

            if (navigator.share) {
                try {
                    await navigator.share(shareData);
                } catch (error) {
                    if (error.name !== 'AbortError') throw error;
                }

                return;
            }

            await navigator.clipboard.writeText(shareData.url);
            $flux.toast(@js(__('Enlace copiado')));
        },
    }"
>
    <div class="mx-auto grid min-h-[100dvh] w-full max-w-7xl lg:min-h-[calc(100vh-4rem)] lg:grid-cols-[minmax(24rem,0.95fr)_minmax(22rem,0.7fr)]">
        <section class="fixed inset-0 z-[60] flex min-h-[100dvh] items-center justify-center overflow-hidden bg-black lg:relative lg:inset-auto lg:z-auto lg:min-h-[calc(100vh-4rem)]">
            <div x-ref="liveFrame" class="relative h-[100dvh] w-full overflow-hidden bg-zinc-900 shadow-2xl lg:h-[calc(100vh-6rem)] lg:max-h-[920px] lg:max-w-[34rem] lg:rounded-[2rem]">
                @if ($this->live->status === LiveStream::BORRADOR)
                    <div class="absolute inset-0 flex items-center justify-center bg-zinc-950">
                        @if ($this->live->coverUrl())<img src="{{ $this->live->coverUrl() }}" alt="{{ $this->live->title }}" class="size-full object-cover">@endif
                        <div class="absolute inset-0 bg-black/35"></div>
                        <div class="relative z-10 max-w-sm px-6 text-center"><p class="text-xs font-bold uppercase tracking-[.2em] text-white/75">{{ __('Live programado') }}</p><h2 class="mt-2 text-2xl font-bold">{{ $this->live->title }}</h2><p class="mt-2 text-sm text-white/80">{{ __('Comienza :date', ['date' => $this->live->scheduled_at->translatedFormat('d M, g:i a')]) }}</p></div>
                    </div>
                @elseif ($this->live->embedUrl())
                    <iframe src="{{ $this->live->embedUrl() }}" title="{{ $this->live->title }}" class="size-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                @else
                    <div
                        x-data="merkamigoLiveViewer({ whepUrl: @js($this->live->stream_origin === 'merkamigo' && $this->live->isLive() ? route('streaming.whep', $this->live) : ''), csrf: @js(csrf_token()) })"
                        class="relative size-full bg-black"
                    >
                        {{-- La señal ya llega compuesta desde el estudio: no se vuelve a invertir ni filtrar. --}}
                        <video wire:ignore x-ref="video" autoplay playsinline muted class="size-full object-cover"></video>

                        <div x-show="state === 'connecting'" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 text-center">
                            <span class="size-2.5 animate-pulse rounded-full bg-red-600"></span>
                            <p class="text-sm text-white/70">{{ __('Conectando a la transmisión…') }}</p>
                        </div>

                        <template x-if="state === 'offline' || state === 'unsupported'">
                            <div class="absolute inset-0 z-10">
                                <x-media.video-player :src="$this->live->playbackUrl()" :poster="$this->live->coverUrl()" aspect="auto" fit="cover" class="h-full rounded-none shadow-none" autoplay />
                            </div>
                        </template>
                    </div>
                @endif

                @if ($this->live->stream_origin !== 'merkamigo' && $this->live->intro_video_active && $this->live->introVideoUrl())
                    <video src="{{ $this->live->introVideoUrl() }}" class="pointer-events-none absolute inset-0 z-20 size-full object-cover" autoplay loop muted playsinline></video>
                @endif

                <div class="pointer-events-none absolute bottom-20 right-5 z-50 h-56 w-20 overflow-visible">
                    <template x-for="reaction in floatingReactions" :key="reaction.id">
                        <span class="live-floating-reaction absolute bottom-0 right-2 text-4xl drop-shadow-lg" :style="`--reaction-x: ${reaction.x}px`" x-text="reaction.emoji"></span>
                    </template>
                </div>

                <div class="pointer-events-none absolute inset-x-0 top-0 z-30 hidden bg-gradient-to-b from-black/80 to-transparent px-4 pb-16 pt-4 lg:block">
                    <div class="pointer-events-auto flex items-center gap-2">
                        <a href="{{ route('vitrinas.show', $this->live->business) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white/70 bg-white">
                                @if ($this->live->business->logoUrl())
                                    <img src="{{ $this->live->business->logoUrl() }}" alt="{{ $this->live->business->name }}" class="size-full object-cover">
                                @else
                                    <flux:icon.building-storefront class="size-5 text-zinc-500" />
                                @endif
                            </span>
                        </a>
                        <span class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold tracking-wide {{ $this->live->signal_status === 'reconnecting' ? 'bg-amber-500 text-zinc-950' : ($this->live->isLive() ? 'bg-red-600' : 'bg-zinc-700') }}">{{ $this->live->connectionStatusLabel() }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1.5 text-xs backdrop-blur"><flux:icon.eye class="size-4" />{{ $this->live->currentViewersCount() }}</span>
                        <button type="button" x-on:click="shareLive()" class="flex size-9 items-center justify-center rounded-full bg-black/45 backdrop-blur hover:bg-black/65" aria-label="{{ __('Compartir') }}"><flux:icon.share class="size-5" /></button>
                        <a href="{{ route('home') }}" wire:navigate class="flex size-9 items-center justify-center rounded-full bg-black/45 backdrop-blur hover:bg-black/65" aria-label="{{ __('Cerrar') }}"><flux:icon.x-mark class="size-5" /></a>
                    </div>
                </div>

                <div class="pointer-events-none absolute inset-x-0 top-0 z-30 bg-gradient-to-b from-black/80 to-transparent px-3 pb-16 pt-[max(0.75rem,env(safe-area-inset-top))] lg:hidden">
                    <div class="pointer-events-auto flex items-center gap-2">
                        <a href="{{ route('home') }}" wire:navigate class="flex size-10 items-center justify-center rounded-full bg-black/35 backdrop-blur" aria-label="{{ __('Volver') }}"><flux:icon.chevron-left class="size-6" /></a>
                        <button type="button" x-on:click="toggleSound" class="flex size-10 items-center justify-center rounded-full bg-black/35 backdrop-blur" :aria-label="soundEnabled ? @js(__('Silenciar')) : @js(__('Activar sonido'))">
                            <flux:icon.speaker-wave x-show="soundEnabled" class="size-6" />
                            <flux:icon.speaker-x-mark x-show="! soundEnabled" class="size-6" />
                        </button>
                        <a href="{{ route('vitrinas.show', $this->live->business) }}" wire:navigate class="ml-1 flex min-w-0 flex-1 items-center gap-2">
                            <span class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/70 bg-white">@if ($this->live->business->logoUrl())<img src="{{ $this->live->business->logoUrl() }}" alt="{{ $this->live->business->name }}" class="size-full object-cover">@else<flux:icon.building-storefront class="size-4 text-zinc-500" />@endif</span>
                            <span class="min-w-0"><span class="block truncate text-xs font-semibold">{{ $this->live->business->name }}</span><span class="inline-flex items-center gap-1 text-[11px] text-white/80"><flux:icon.eye class="size-3.5" />{{ $this->live->currentViewersCount() }}</span></span>
                        </a>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $this->live->signal_status === 'reconnecting' ? 'bg-amber-500 text-zinc-950' : ($this->live->isLive() ? 'bg-red-600' : 'bg-zinc-700') }}">{{ $this->live->connectionStatusLabel() }}</span>
                    </div>
                </div>

                <div class="absolute right-3 top-20 z-40 space-y-2 lg:hidden">
                    @foreach ($this->presentedProducts->take(3) as $railProduct)
                        @php $railPhoto = $railProduct->media->first(); @endphp
                        <button type="button" wire:click="openProduct({{ $railProduct->id }})" class="block w-[4.5rem] overflow-hidden rounded-xl bg-white text-zinc-950 shadow-xl" aria-label="{{ __('Ver :product', ['product' => $railProduct->name]) }}">
                            <span class="block aspect-square bg-zinc-100">@if ($railPhoto)<img src="{{ $railPhoto->url() }}" alt="{{ $railProduct->name }}" class="size-full object-cover">@else<flux:icon.photo class="m-5 size-7 text-zinc-400" />@endif</span>
                            <span class="block truncate bg-black/75 px-1 py-1 text-center text-[10px] font-semibold text-white">{{ __('Ver artículo') }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="absolute bottom-32 right-3 z-40 flex flex-col gap-2 lg:hidden">
                    <button type="button" x-on:click="mobilePanel = mobilePanel === 'products' ? null : 'products'; mobilePollOpen = false" class="relative flex size-11 items-center justify-center rounded-full bg-black/70 shadow-lg backdrop-blur" aria-label="{{ __('Ver productos') }}"><flux:icon.squares-2x2 class="size-6" /><span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-white text-[10px] font-bold text-zinc-950">{{ $this->presentedProducts->count() }}</span></button>
                    @if ($this->live->activePoll)<button type="button" x-on:click="mobilePollOpen = true; mobilePanel = null; sessionStorage.removeItem('live-poll-{{ $this->live->activePoll->id }}')" class="relative flex size-11 items-center justify-center rounded-full bg-black/70 shadow-lg backdrop-blur" aria-label="{{ __('Ver encuesta') }}"><flux:icon.chart-bar class="size-6" /><span class="absolute -right-0.5 -top-0.5 size-2.5 rounded-full bg-brand-500"></span></button>@endif
                    <button type="button" x-on:click="shareLive()" class="flex size-11 items-center justify-center rounded-full bg-black/70 shadow-lg backdrop-blur" aria-label="{{ __('Compartir') }}"><flux:icon.share class="size-6" /></button>
                </div>

                @if ($this->live->activePoll)
                    @php $viewerPoll = $this->live->activePoll; @endphp
                    <div class="absolute inset-x-4 top-1/2 z-40 hidden -translate-y-1/2 rounded-2xl bg-white/95 p-4 text-zinc-950 shadow-2xl backdrop-blur lg:block">
                        <div class="mb-3 text-center"><flux:icon.chart-bar class="mx-auto mb-2 size-7" /><h2 class="font-bold">{{ $viewerPoll->question }}</h2><p class="mt-1 text-xs text-zinc-500">{{ trans_choice(':count voto|:count votos', $viewerPoll->votes->count(), ['count' => $viewerPoll->votes->count()]) }}</p></div>
                        <div class="space-y-2">
                            @foreach ($viewerPoll->results() as $result)
                                <button type="button" wire:click="voteOnPoll({{ $result['index'] }})" class="relative block w-full overflow-hidden rounded-xl border px-3 py-2.5 text-left text-sm shadow-sm transition hover:border-brand-500 {{ $pollVote === $result['index'] ? 'border-brand-600 ring-2 ring-brand-200' : 'border-zinc-200' }}">
                                    <span class="absolute inset-y-0 left-0 bg-zinc-200" style="width: {{ $result['percentage'] }}%"></span>
                                    <span class="relative flex items-center justify-between gap-3"><span class="truncate font-medium">{{ $result['label'] }}</span><span class="shrink-0 font-semibold">{{ number_format($result['percentage'], 1, ',', '.') }}%</span></span>
                                </button>
                            @endforeach
                        </div>
                        @if ($pollVote !== null)<p class="mt-3 text-center text-xs font-medium text-brand-700">{{ __('Tu voto fue registrado. Puedes cambiarlo mientras la encuesta esté activa.') }}</p>@endif
                    </div>
                @endif

                <div class="pointer-events-none absolute inset-x-0 bottom-0 z-30 bg-gradient-to-t from-black via-black/80 to-transparent px-4 pb-4 pt-32">
                    @if ($this->live->pinnedProduct)
                        @php
                            $product = $this->live->pinnedProduct;
                            $photo = $product->media->first();
                            $salePrice = $product->hasActivePromo() ? $product->promo_price : $product->price;
                        @endphp
                        <div class="pointer-events-auto hidden rounded-2xl bg-white p-3 text-zinc-950 shadow-2xl lg:block">
                            <div class="flex gap-3">
                                <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-zinc-100">
                                    @if ($photo)<img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="size-full object-cover">@else<flux:icon.photo class="m-5 size-6 text-zinc-400" />@endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2"><p class="line-clamp-2 text-sm font-semibold">{{ $product->name }}</p>@if ($product->hasActivePromo())<span class="rounded-full bg-red-600 px-2 py-1 text-[10px] font-bold text-white">{{ __('OFERTA') }}</span>@endif</div>
                                    @if ($salePrice)<div class="mt-1 flex items-baseline gap-2"><span class="text-xl font-bold text-brand-600">${{ number_format((float) $salePrice, 0, ',', '.') }}</span>@if ($product->hasActivePromo())<span class="text-xs text-zinc-400 line-through">${{ number_format((float) $product->price, 0, ',', '.') }}</span>@endif</div>@endif
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $product->isSoldOut() ? __('Agotado') : __('Disponible') }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-[1fr_auto] gap-2">
                                <flux:button variant="primary" class="w-full" wire:click="openProduct({{ $product->id }})" :disabled="$product->isSoldOut()">{{ __('Comprar ahora') }}</flux:button>
                                <flux:modal.trigger name="live-cart"><flux:button variant="ghost" icon="shopping-cart" class="relative"><span class="sr-only">{{ __('Ver carrito') }}</span>@if (count($cart))<span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-brand-600 text-[10px] text-white">{{ collect($cart)->sum('quantity') }}</span>@endif</flux:button></flux:modal.trigger>
                            </div>
                        </div>
                    @endif

                    @if ($this->live->isLive())
                        <div x-show="mobilePanel === null && ! mobilePollOpen && mobileChat" x-transition class="pointer-events-auto mb-3 max-h-[32vh] space-y-2 overflow-y-auto pr-14 text-xs lg:hidden">
                            <button type="button" x-on:click="mobileChat = false" class="mb-1 flex size-7 items-center justify-center rounded-full bg-black/45" aria-label="{{ __('Ocultar chat') }}"><flux:icon.x-mark class="size-4" /></button>
                            @foreach ($this->messages->take(-6) as $chatMessage)
                                <p class="leading-5 drop-shadow"><span class="font-bold">{{ $chatMessage->user->name }}</span> <span class="text-white/90">{{ $chatMessage->body }}</span></p>
                            @endforeach
                        </div>
                        <form wire:submit="sendMessage" class="pointer-events-auto relative mt-3 flex items-center gap-2" x-data="{ reactionsOpen: false }">
                            <flux:modal.trigger name="live-cart"><button type="button" class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur lg:hidden" aria-label="{{ __('Ver carrito') }}"><flux:icon.shopping-cart class="size-6" />@if (count($cart))<span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-brand-600 text-[10px]">{{ collect($cart)->sum('quantity') }}</span>@endif</button></flux:modal.trigger>
                            <input wire:model="message" x-on:focus="mobileChat = true" maxlength="280" class="min-w-0 flex-1 rounded-full border border-white/40 bg-black/35 px-4 py-2.5 text-sm text-white placeholder:text-white/70 focus:border-white focus:outline-none" placeholder="{{ auth()->check() ? __('Escribe un mensaje…') : __('Ingresa para participar') }}">
                            <button type="submit" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white" aria-label="{{ __('Enviar') }}"><flux:icon.paper-airplane class="size-5" /></button>
                            <button type="button" wire:click="toggleReaction" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur" aria-label="{{ __('Me gusta') }}"><flux:icon.heart class="size-6 {{ $reacted ? 'fill-red-500 text-red-500' : '' }}" /></button>
                            <button type="button" x-on:click="reactionsOpen = ! reactionsOpen" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl backdrop-blur" aria-label="{{ __('Reacciones') }}">😊</button>
                            <div x-cloak x-show="reactionsOpen" x-transition x-on:click.outside="reactionsOpen = false" class="absolute bottom-12 right-0 flex gap-1 rounded-full bg-black/70 p-1.5 shadow-xl backdrop-blur">
                                @foreach (EngageWithLiveStream::REACTION_EMOJIS as $reactionType => $emoji)
                                    <button type="button" wire:click="sendReaction('{{ $reactionType }}')" x-on:click="reactionsOpen = false" class="flex size-10 items-center justify-center rounded-full text-2xl transition hover:scale-125 hover:bg-white/15" aria-label="{{ __('Reaccionar con :emoji', ['emoji' => $emoji]) }}">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </form>
                    @endif
                </div>

                <div x-cloak x-show="mobilePanel === 'products'" x-transition class="absolute inset-x-0 bottom-0 z-50 flex max-h-[72dvh] flex-col rounded-t-[1.75rem] bg-white text-zinc-950 shadow-2xl lg:hidden">
                    <button type="button" x-on:click="mobilePanel = null" class="absolute -top-5 left-1/2 flex size-10 -translate-x-1/2 items-center justify-center rounded-full bg-zinc-950 text-white shadow-lg" aria-label="{{ __('Cerrar productos') }}"><flux:icon.chevron-down class="size-6" /></button>
                    <div class="border-b border-zinc-200 px-5 pb-3 pt-8 text-center"><h2 class="text-xl font-medium">{{ __('Productos presentados') }}</h2><p class="mt-1 text-xs text-zinc-500">{{ trans_choice(':count disponible|:count disponibles', $this->presentedProducts->count(), ['count' => $this->presentedProducts->count()]) }}</p></div>
                    <div class="flex-1 divide-y divide-zinc-200 overflow-y-auto px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        @forelse ($this->presentedProducts as $sheetProduct)
                            @php $sheetPhoto = $sheetProduct->media->first(); $sheetPrice = $sheetProduct->hasActivePromo() ? $sheetProduct->promo_price : $sheetProduct->price; @endphp
                            <div class="flex items-center gap-4 py-4">
                                <button type="button" wire:click="openProduct({{ $sheetProduct->id }})" x-on:click="mobilePanel = null" class="size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100">@if ($sheetPhoto)<img src="{{ $sheetPhoto->url() }}" alt="{{ $sheetProduct->name }}" class="size-full object-cover">@else<flux:icon.photo class="m-6 size-8 text-zinc-400" />@endif</button>
                                <div class="min-w-0 flex-1"><p class="font-semibold">{{ $sheetProduct->name }}</p>@if ($sheetPrice)<div class="mt-2 flex items-baseline gap-2"><span class="font-bold">${{ number_format((float) $sheetPrice, 0, ',', '.') }}</span>@if ($sheetProduct->hasActivePromo())<span class="text-xs text-zinc-400 line-through">${{ number_format((float) $sheetProduct->price, 0, ',', '.') }}</span>@endif</div>@endif<button type="button" wire:click="openProduct({{ $sheetProduct->id }})" x-on:click="mobilePanel = null" class="mt-3 rounded-full bg-zinc-950 px-5 py-2 text-xs font-semibold text-white">{{ __('Ver artículo') }}</button></div>
                            </div>
                        @empty
                            <div class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Los productos aparecerán aquí cuando el anfitrión los presente.') }}</div>
                        @endforelse
                    </div>
                </div>

                @if ($this->live->activePoll)
                    @php $mobilePoll = $this->live->activePoll; @endphp
                    <div x-cloak x-init="if (! sessionStorage.getItem('live-poll-{{ $mobilePoll->id }}')) mobilePollOpen = true" x-show="mobilePollOpen" x-transition class="absolute inset-x-0 bottom-0 z-50 flex min-h-[56dvh] max-h-[76dvh] flex-col rounded-t-[1.75rem] bg-white text-zinc-950 shadow-2xl lg:hidden">
                        <button type="button" x-on:click="mobilePollOpen = false; sessionStorage.setItem('live-poll-{{ $mobilePoll->id }}', 'dismissed')" class="absolute -top-5 left-1/2 flex size-10 -translate-x-1/2 items-center justify-center rounded-full bg-zinc-950 text-white shadow-lg" aria-label="{{ __('Cerrar encuesta') }}"><flux:icon.chevron-down class="size-6" /></button>
                        <div class="overflow-y-auto px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-9">
                            <div class="mb-5 text-center"><flux:icon.chart-bar class="mx-auto mb-3 size-8" /><h2 class="text-xl font-bold">{{ $mobilePoll->question }}</h2><p class="mt-1 text-sm text-zinc-500">{{ trans_choice(':count persona ha votado|:count personas han votado', $mobilePoll->votes->count(), ['count' => $mobilePoll->votes->count()]) }}</p></div>
                            <div class="space-y-3">
                                @foreach ($mobilePoll->results() as $result)
                                    <button type="button" wire:click="voteOnPoll({{ $result['index'] }})" class="relative block w-full overflow-hidden rounded-xl border px-4 py-3 text-left text-sm shadow-sm {{ $pollVote === $result['index'] ? 'border-brand-600 ring-2 ring-brand-200' : 'border-zinc-200' }}">
                                        <span class="absolute inset-y-0 left-0 bg-zinc-200" style="width: {{ $result['percentage'] }}%"></span><span class="relative flex justify-between gap-3"><span class="font-medium">{{ $result['label'] }}</span><span class="font-bold">{{ number_format($result['percentage'], 1, ',', '.') }}%</span></span>
                                    </button>
                                @endforeach
                            </div>
                            @if ($pollVote !== null)<p class="mt-4 text-center text-xs font-medium text-brand-700">{{ __('Tu voto fue registrado. Puedes cambiarlo mientras la encuesta esté activa.') }}</p>@endif
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <aside class="hidden min-h-0 flex-col border-l border-white/10 bg-zinc-950 lg:flex">
            <div class="border-b border-white/10 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-400">{{ __('Merkamigo Live Shopping') }}</p><h1 class="mt-1 text-2xl font-bold">{{ $this->live->title }}</h1>@if ($this->live->description)<p class="mt-2 line-clamp-3 text-sm leading-6 text-zinc-400">{{ $this->live->description }}</p>@endif</div>
                    <livewire:follow-button :business="$this->live->business" compact :key="'live-follow-'.$this->live->business_id" />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold">{{ __('Chat en vivo') }}</h2><span class="text-xs text-zinc-500">{{ $this->live->messages()->where('status', 'publicado')->count() }} {{ __('mensajes') }}</span></div>
                <div class="space-y-4">
                    @forelse ($this->messages as $chatMessage)
                        <div class="flex gap-2.5"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-xs font-semibold">{{ str($chatMessage->user->name)->substr(0, 1)->upper() }}</span><p class="min-w-0 text-sm leading-5"><span class="font-semibold text-white">{{ $chatMessage->user->name }}</span> <span class="text-zinc-300">{{ $chatMessage->body }}</span></p></div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-700 p-6 text-center text-sm text-zinc-500">{{ __('Sé la primera persona en escribir.') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-white/10 p-5">
                <div class="flex items-center justify-between"><div><p class="text-sm font-semibold">{{ __('Productos presentados') }}</p><p class="text-xs text-zinc-500">{{ trans_choice(':count disponible|:count disponibles', $this->presentedProducts->count(), ['count' => $this->presentedProducts->count()]) }}</p></div><flux:modal.trigger name="live-cart"><flux:button variant="primary" size="sm" icon="shopping-cart">{{ collect($cart)->sum('quantity') ?: __('Carrito') }}</flux:button></flux:modal.trigger></div>
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                    @forelse ($this->presentedProducts as $liveProduct)
                        @php $productPhoto = $liveProduct->media->first(); @endphp
                        <button type="button" wire:click="openProduct({{ $liveProduct->id }})" class="w-24 shrink-0 text-left"><span class="block aspect-square overflow-hidden rounded-xl bg-zinc-800">@if ($productPhoto)<img src="{{ $productPhoto->url() }}" alt="{{ $liveProduct->name }}" class="size-full object-cover">@endif</span><span class="mt-1 block truncate text-xs font-medium">{{ $liveProduct->name }}</span></button>
                    @empty
                        <p class="text-xs text-zinc-500">{{ __('El anfitrión aún no ha presentado productos.') }}</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>

    @if (! $this->live->isLive() && $this->live->productEvents->where('action', 'featured')->isNotEmpty())
        <section class="mx-auto max-w-5xl px-4 py-8">
            <h2 class="text-lg font-semibold">{{ __('Productos presentados en este replay') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->live->productEvents->where('action', 'featured')->unique('product_id') as $event)
                    <button type="button" wire:click="openProduct({{ $event->product_id }})" class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 text-left hover:bg-white/10">
                        @if ($event->product->media->first())<img src="{{ $event->product->media->first()->url() }}" class="size-14 rounded-xl object-cover" alt="{{ $event->product->name }}">@endif
                        <span><span class="block text-sm font-semibold">{{ $event->product->name }}</span><span class="text-xs text-zinc-400">{{ gmdate('i:s', $event->elapsed_seconds) }} · {{ __('Ver momento') }}</span></span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <flux:modal name="live-product" class="w-full !max-w-2xl max-sm:!mt-auto max-sm:!rounded-b-none">
        @if ($this->selectedProduct)
            @php
                $selected = $this->selectedProduct;
                $selectedPhoto = $selected->media->first();
                $selectedVariant = $selectedVariantId ? $selected->variants->firstWhere('id', $selectedVariantId) : null;
                $selectedPrice = $selectedVariant?->price ?? ($selected->hasActivePromo() ? $selected->promo_price : $selected->price);
            @endphp
            <div class="space-y-5">
                <div class="flex gap-4">
                    <div class="size-32 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">@if ($selectedPhoto)<img src="{{ $selectedPhoto->url() }}" alt="{{ $selected->name }}" class="size-full object-cover">@endif</div>
                    <div class="min-w-0"><flux:heading size="lg">{{ $selected->name }}</flux:heading>@if ($selected->description)<p class="mt-1 line-clamp-3 text-sm text-zinc-500">{{ $selected->description }}</p>@endif<div class="mt-3 flex items-baseline gap-2"><span class="text-2xl font-bold text-brand-600">${{ number_format((float) $selectedPrice, 0, ',', '.') }}</span>@if ($selected->hasActivePromo())<span class="text-sm text-zinc-400 line-through">${{ number_format((float) $selected->price, 0, ',', '.') }}</span>@endif</div></div>
                </div>

                @if ($selected->variants->isNotEmpty())
                    <fieldset><legend class="mb-2 text-sm font-semibold">{{ __('Presentación') }}</legend><div class="flex flex-wrap gap-2">@foreach ($selected->variants as $variant)<label class="cursor-pointer"><input type="radio" wire:model.live="selectedVariantId" value="{{ $variant->id }}" class="peer sr-only"><span class="block rounded-full border border-zinc-300 px-4 py-2 text-sm peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 dark:border-zinc-700 dark:peer-checked:bg-brand-950">{{ $variant->label }}</span></label>@endforeach</div>@error('selectedVariantId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</fieldset>
                @endif

                <div class="flex items-center justify-between rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70"><div><p class="text-sm font-semibold">{{ __('Cantidad') }}</p><p class="text-xs text-zinc-500">{{ $selected->isSoldOut() ? __('Agotado') : __('Disponible') }}</p></div><div class="flex items-center rounded-full border border-zinc-300 dark:border-zinc-600"><button type="button" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})" class="flex size-10 items-center justify-center">−</button><span class="w-10 text-center font-semibold">{{ $quantity }}</span><button type="button" wire:click="$set('quantity', {{ min(99, $quantity + 1) }})" class="flex size-10 items-center justify-center">+</button></div></div>

                @if ($selected->hasActivePromo())<div class="rounded-2xl bg-brand-50 p-4 text-sm font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-200"><flux:icon.bolt class="me-2 inline size-5" />{{ __('Oferta especial disponible durante el Live.') }}@if ($selected->promo_ends_at) {{ __('Termina :time', ['time' => $selected->promo_ends_at->diffForHumans()]) }}@endif</div>@endif

                <div class="grid gap-2 sm:grid-cols-2"><flux:button variant="ghost" icon="shopping-cart" wire:click="addToCart(false)" :disabled="$selected->isSoldOut()">{{ __('Agregar al carrito') }}</flux:button><flux:button variant="primary" icon="bolt" wire:click="addToCart(true)" :disabled="$selected->isSoldOut()">{{ __('Comprar ahora') }}</flux:button></div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="live-cart" class="w-full !max-w-xl max-sm:!mt-auto max-sm:!rounded-b-none">
        <div class="space-y-5">
            <div><flux:heading size="lg">{{ __('Tu carrito del Live') }}</flux:heading><flux:subheading>{{ __('El video seguirá reproduciéndose mientras completas tu compra.') }}</flux:subheading></div>
            <div class="max-h-[50vh] space-y-3 overflow-y-auto">
                @forelse ($this->cartItems as $item)
                    @php $itemPhoto = $item['product']->media->first(); @endphp
                    <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 p-3 dark:border-zinc-700">@if ($itemPhoto)<img src="{{ $itemPhoto->url() }}" class="size-14 rounded-xl object-cover" alt="{{ $item['product']->name }}">@endif<div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $item['product']->name }}</p>@if ($item['variant'])<p class="text-xs text-zinc-500">{{ $item['variant']->label }}</p>@endif<p class="text-sm font-bold text-brand-600">${{ number_format($item['total'], 0, ',', '.') }}</p></div><div class="flex items-center gap-1"><button type="button" wire:click="changeCartQuantity('{{ $item['key'] }}', -1)" class="flex size-8 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">−</button><span class="w-6 text-center text-sm">{{ $item['quantity'] }}</span><button type="button" wire:click="changeCartQuantity('{{ $item['key'] }}', 1)" class="flex size-8 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">+</button><button type="button" wire:click="removeFromCart('{{ $item['key'] }}')" class="ms-1 text-zinc-400 hover:text-red-600" aria-label="{{ __('Eliminar') }}"><flux:icon.trash class="size-5" /></button></div></div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">{{ __('Tu carrito está vacío.') }}</div>
                @endforelse
            </div>
            @if ($this->cartItems->isNotEmpty())
                <div class="flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700"><span class="font-semibold">{{ __('Subtotal') }}</span><span class="text-2xl font-bold">${{ number_format($this->cartItems->sum('total'), 0, ',', '.') }}</span></div>
                <form method="POST" action="{{ route('marketplace.live.checkout', $this->live) }}" target="_blank" x-data x-on:submit.prevent="$wire.registerCheckout(); window.merkamigoOpenWompiCheckoutPost($event.target)">
                    @csrf
                    <flux:button type="submit" variant="primary" icon="lock-closed" class="w-full">
                        {{ __('Pagar con Wompi') }}
                    </flux:button>
                    <p class="mt-2 text-center text-xs text-zinc-500">{{ __('El pago se abre en una ventana segura sin salir del Live.') }}</p>
                </form>
            @endif
        </div>
    </flux:modal>
</main>
{{-- La experiencia Live Shopping termina aquí; toda la compra se mantiene en modales sobre el video. --}}
{{--
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('vitrinas.show', $this->live->business) }}" wire:navigate class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center overflow-hidden rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        @if ($this->live->business->logoUrl())
                            <img src="{{ $this->live->business->logoUrl() }}" alt="{{ $this->live->business->name }}" class="size-full object-cover">
                        @else
                            <flux:icon.building-storefront class="size-5 text-zinc-400" />
                        @endif
                    </div>
                    <div>
                        <flux:heading>{{ $this->live->business->name }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $this->live->business->municipality?->name }}</flux:text>
                    </div>
                </a>
                <livewire:follow-button :business="$this->live->business" compact :key="'live-follow-'.$this->live->business_id" />
            </div>

            <div class="flex items-center gap-2">
                @if ($this->live->isLive())
                    <flux:badge color="red">● {{ __('EN VIVO') }}</flux:badge>
                    <flux:badge color="zinc" icon="eye">{{ $this->live->currentViewersCount() }}</flux:badge>
                @else
                    <flux:badge color="zinc">{{ __('REPLAY') }}</flux:badge>
                @endif
                @if ($this->live->activePromotion)
                    <flux:badge color="amber" icon="megaphone">{{ __('Patrocinado') }}</flux:badge>
                @endif
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="share"
                    x-on:click="shareLive()"
                >
                    {{ __('Compartir') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
                <section class="overflow-hidden rounded-2xl bg-black shadow-sm">
                    @if ($this->live->embedUrl())
                        <iframe
                            src="{{ $this->live->embedUrl() }}"
                            title="{{ $this->live->title }}"
                            class="aspect-video w-full"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @else
                        <x-media.video-player :src="$this->live->playbackUrl()" autoplay />
                    @endif
                </section>

                @if (count($this->live->destinationsList()) > 1)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('También disponible en:') }}</span>
                        @foreach ($this->live->destinationsList() as $destination)
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="arrow-top-right-on-square"
                                :href="$destination['url']"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ LiveStream::providerLabel($destination['provider']) }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <flux:heading size="xl">{{ $this->live->title }}</flux:heading>
                            @if ($this->live->description)
                                <flux:text class="mt-2 whitespace-pre-line">{{ $this->live->description }}</flux:text>
                            @endif
                        </div>
                        <flux:button wire:click="toggleReaction" :variant="$reacted ? 'primary' : 'ghost'" icon="heart">
                            {{ $this->live->reactions()->count() }}
                        </flux:button>
                    </div>
                </section>

                @if ($this->live->products->isNotEmpty())
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:heading size="lg" class="mb-4">{{ __('Productos de esta transmisión') }}</flux:heading>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($this->live->products as $product)
                                @php $photo = $product->media->first(); @endphp
                                <a href="{{ route('vitrinas.product', [$this->live->business, $product]) }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if ($photo)
                                            <img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="size-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                                        @if ($product->price)
                                            <p class="text-sm font-semibold text-brand-600">${{ number_format((float) $product->price, 0, ',', '.') }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-5">
                @if ($this->live->pinnedProduct)
                    @php $product = $this->live->pinnedProduct; $photo = $product->media->first(); @endphp
                    <section class="rounded-2xl border-2 border-brand-200 bg-white p-4 shadow-sm dark:border-brand-900 dark:bg-zinc-900">
                        <flux:badge color="red" class="mb-3">{{ $this->live->isLive() ? __('Producto fijado') : __('Disponible en el replay') }}</flux:badge>
                        @if ($photo)
                            <img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="aspect-video w-full rounded-xl object-cover">
                        @endif
                        <flux:heading class="mt-3">{{ $product->name }}</flux:heading>
                        @if ($product->price)
                            <p class="mt-1 text-xl font-bold text-brand-600">${{ number_format((float) $product->price, 0, ',', '.') }}</p>
                        @endif

                        @if ($this->live->business->hasWompiConnected() && $product->price && ! $product->isSoldOut())
                            <flux:button
                                variant="primary"
                                class="mt-4 w-full"
                                :href="route('marketplace.checkout.create', [
                                    'product' => $product,
                                    'live' => $this->live->id,
                                    'promotion' => $this->live->activePromotion?->id,
                                ])"
                                target="_blank"
                            >
                                {{ __('Comprar ahora') }}
                            </flux:button>
                            <flux:text class="mt-2 text-center text-xs text-zinc-500">{{ __('El pago seguro se abre aparte para que el Live siga reproduciéndose.') }}</flux:text>
                        @else
                            <flux:button variant="primary" class="mt-4 w-full" :href="route('vitrinas.product', [$this->live->business, $product])" wire:navigate>
                                {{ __('Ver producto') }}
                            </flux:button>
                        @endif
                    </section>
                @endif

                <section class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
                        <flux:heading>{{ __('Chat en vivo') }}</flux:heading>
                    </div>
                    <div class="max-h-80 space-y-3 overflow-y-auto p-4">
                        @forelse ($this->messages as $chatMessage)
                            <div class="text-sm">
                                <span class="font-semibold">{{ $chatMessage->user->name }}</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $chatMessage->body }}</span>
                            </div>
                        @empty
                            <flux:text class="text-sm text-zinc-500">{{ __('Sé la primera persona en escribir.') }}</flux:text>
                        @endforelse
                    </div>

                    @if ($this->live->isLive())
                        <form wire:submit="sendMessage" class="space-y-2 border-t border-zinc-200 p-3 dark:border-zinc-800">
                            <flux:input wire:model="message" maxlength="280" :placeholder="auth()->check() ? __('Escribe un mensaje') : __('Ingresa para participar')" />
                            <flux:button type="submit" size="sm" variant="primary" class="w-full">{{ __('Enviar') }}</flux:button>
                        </form>
                    @else
                        <flux:text class="border-t border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800">{{ __('El chat terminó con la transmisión.') }}</flux:text>
                    @endif
                </section>
            </aside>
        </div>
</main>
--}}
