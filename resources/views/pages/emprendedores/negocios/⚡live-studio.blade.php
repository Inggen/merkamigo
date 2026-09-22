<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\EngageWithLiveStream;
use App\Domain\Social\Actions\ManageLiveIntroVideo;
use App\Domain\Social\Actions\ManageLivePoll;
use App\Domain\Social\Actions\ManageLiveStream;
use App\Domain\Social\Models\LiveStream;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Estudio Live')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    #[Locked]
    public int $liveStreamId;

    #[Locked]
    public string $liveStreamSlug;

    #[Locked]
    public int $lastReactionId = 0;

    public string $message = '';

    public string $pollQuestion = '';

    /** @var array<int, string> */
    public array $pollOptions = ['', ''];

    public $introVideo = null;

    public function mount(Business $business, LiveStream $liveStream): void
    {
        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');
        $this->authorize('update', $business);
        abort_unless($liveStream->business_id === $business->id, 404);
        abort_if($liveStream->status === LiveStream::FINALIZADO, 404);

        $this->businessId = $business->id;
        $this->liveStreamId = $liveStream->id;
        $this->liveStreamSlug = $liveStream->slug;
        $this->lastReactionId = (int) ($liveStream->reactions()->max('id') ?? 0);
    }

    public function boot(): void
    {
        if (isset($this->businessId)) {
            setPermissionsTeamId($this->businessId);
            Auth::user()?->unsetRelation('roles');
        }
    }

    #[Computed]
    public function live(): LiveStream
    {
        return LiveStream::with(['business.municipality', 'products.media', 'pinnedProduct.media', 'polls.votes', 'activePoll.votes'])
            ->where('business_id', $this->businessId)
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

    /** @return array<string, int> */
    #[Computed]
    public function reactionCounts(): array
    {
        $counts = $this->live->reactions()
            ->whereIn('type', array_keys(EngageWithLiveStream::REACTION_EMOJIS))
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return collect(array_keys(EngageWithLiveStream::REACTION_EMOJIS))
            ->mapWithKeys(fn (string $type): array => [$type => (int) ($counts[$type] ?? 0)])
            ->all();
    }

    public function refreshStudio(): void
    {
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
                $this->dispatch('studio-reaction', emoji: $emoji);
            }

            $this->lastReactionId = $reaction->id;
        }

        $this->dispatch('studio-poll-overlay', poll: $this->pollOverlay($this->live));

        unset($this->live, $this->messages, $this->reactionCounts);
    }

    public function showProduct(int $productId): void
    {
        abort_unless($this->live->isLive(), 422);
        $live = app(ManageLiveStream::class)->pinProduct($this->live, $productId, Auth::user());
        $this->dispatch('studio-product-overlay', product: $this->productOverlay($live));
        unset($this->live);
        Flux::toast(variant: 'success', text: __('El producto ya se muestra en el Live.'));
    }

    public function hideProduct(): void
    {
        abort_unless($this->live->isLive(), 422);
        app(ManageLiveStream::class)->pinProduct($this->live, null, Auth::user());
        $this->dispatch('studio-product-overlay', product: null);
        unset($this->live);
        Flux::toast(text: __('Producto ocultado del Live.'));
    }

    public function sendMessage(): void
    {
        try {
            app(EngageWithLiveStream::class)->message($this->live, $this->message, Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->reset('message');
        unset($this->messages);
    }

    public function sendReaction(string $type): void
    {
        $reaction = app(EngageWithLiveStream::class)->react($this->live, $type, request(), Auth::user());
        $this->lastReactionId = $reaction->id;
        $this->dispatch('studio-reaction', emoji: EngageWithLiveStream::REACTION_EMOJIS[$type]);
        unset($this->live, $this->reactionCounts);
    }

    public function addPollOption(): void
    {
        if (count($this->pollOptions) < 4) {
            $this->pollOptions[] = '';
        }
    }

    public function removePollOption(int $index): void
    {
        if (count($this->pollOptions) > 2) {
            unset($this->pollOptions[$index]);
            $this->pollOptions = array_values($this->pollOptions);
        }
    }

    public function savePoll(): void
    {
        validator([
            'pollQuestion' => $this->pollQuestion,
            'pollOptions' => $this->pollOptions,
        ], [
            'pollQuestion' => ['required', 'string', 'max:120'],
            'pollOptions' => ['required', 'array', 'min:2', 'max:4'],
            'pollOptions.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
        ])->validate();

        app(ManageLivePoll::class)->save($this->live, $this->pollQuestion, $this->pollOptions, Auth::user());
        $this->reset('pollQuestion');
        $this->pollOptions = ['', ''];
        unset($this->live);
        Flux::toast(variant: 'success', text: __('Encuesta guardada. Puedes mostrarla cuando quieras.'));
    }

    public function showPoll(int $pollId): void
    {
        $poll = $this->live->polls()->findOrFail($pollId);
        app(ManageLivePoll::class)->show($poll);
        unset($this->live);
        $this->dispatch('studio-poll-overlay', poll: $this->pollOverlay($this->live));
        Flux::toast(variant: 'success', text: __('Encuesta visible en la transmisión.'));
    }

    public function hidePoll(int $pollId): void
    {
        $poll = $this->live->polls()->findOrFail($pollId);
        app(ManageLivePoll::class)->hide($poll);
        unset($this->live);
        $this->dispatch('studio-poll-overlay', poll: null);
        Flux::toast(text: __('Encuesta oculta. Puedes volver a mostrarla cuando quieras.'));
    }

    public function saveIntroVideo(): void
    {
        validator(['introVideo' => $this->introVideo], [
            'introVideo' => ['required', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
        ])->validate();

        app(ManageLiveIntroVideo::class)->store($this->live, $this->introVideo, Auth::user());
        $this->reset('introVideo');
        unset($this->live);
        Flux::toast(variant: 'success', text: __('Video de inicio guardado y activado.'));
    }

    public function toggleIntroVideo(): void
    {
        $live = app(ManageLiveIntroVideo::class)->toggle($this->live, Auth::user());
        unset($this->live);
        $this->dispatch('live-intro-toggled', active: $live->intro_video_active);
    }

    public function finishBroadcast(): void
    {
        if ($this->live->isLive()) {
            app(ManageLiveStream::class)->end($this->live, null, Auth::user());
        }

        $this->redirectRoute('emprendedores.negocios.lives', $this->businessId, navigate: true);
    }

    /** @return array{name: string, price: string, availability: string, image: ?string}|null */
    private function productOverlay(LiveStream $live): ?array
    {
        $live->load('pinnedProduct.media');
        $product = $live->pinnedProduct;

        if (! $product) {
            return null;
        }

        $price = $product->hasActivePromo() ? $product->promo_price : $product->price;

        return [
            'name' => $product->name,
            'price' => $price ? '$'.number_format((float) $price, 0, ',', '.') : '',
            'availability' => $product->isSoldOut() ? __('Agotado') : __('Disponible'),
            'image' => $product->media->first()?->url(),
        ];
    }

    /** @return array{question: string, votesLabel: string, options: array<int, array{label: string, percentage: float}>}|null */
    private function pollOverlay(LiveStream $live): ?array
    {
        $live->load('activePoll.votes');
        $poll = $live->activePoll;

        if (! $poll) {
            return null;
        }

        return [
            'question' => $poll->question,
            'votesLabel' => trans_choice(':count voto|:count votos', $poll->votes->count(), ['count' => $poll->votes->count()]),
            'options' => collect($poll->results())
                ->map(fn (array $result): array => [
                    'label' => $result['label'],
                    'percentage' => (float) $result['percentage'],
                ])
                ->values()
                ->all(),
        ];
    }
}; ?>

@php
    $publicLiveUrl = route('live.show', $this->live);
    $publicLiveShareText = __('Mira :title en Merkamigo', ['title' => $this->live->title]);
    $studioReactionEmojis = EngageWithLiveStream::REACTION_EMOJIS;
    $initialProductOverlay = $this->live->pinnedProduct
        ? [
            'name' => $this->live->pinnedProduct->name,
            'price' => ($initialProductPrice = $this->live->pinnedProduct->hasActivePromo() ? $this->live->pinnedProduct->promo_price : $this->live->pinnedProduct->price)
                ? '$'.number_format((float) $initialProductPrice, 0, ',', '.')
                : '',
            'availability' => $this->live->pinnedProduct->isSoldOut() ? __('Agotado') : __('Disponible'),
            'image' => $this->live->pinnedProduct->media->first()?->url(),
        ]
        : null;
    $initialPollOverlay = $this->live->activePoll
        ? [
            'question' => $this->live->activePoll->question,
            'votesLabel' => trans_choice(':count voto|:count votos', $this->live->activePoll->votes->count(), ['count' => $this->live->activePoll->votes->count()]),
            'options' => collect($this->live->activePoll->results())->map(fn (array $result) => [
                'label' => $result['label'],
                'percentage' => (float) $result['percentage'],
            ])->values()->all(),
        ]
        : null;
@endphp

<section
    wire:poll.3s="refreshStudio"
    wire:ignore.self
    wire:key="live-studio-{{ $liveStreamId }}"
    x-on:studio-reaction.window="burstReaction($event.detail.emoji)"
    x-on:studio-product-overlay.window="setProductOverlay($event.detail.product)"
    x-on:studio-poll-overlay.window="setPollOverlay($event.detail.poll)"
    x-on:live-intro-toggled.window="setIntroVideoActive($event.detail.active)"
    class="w-full space-y-5"
    x-data="merkamigoLiveStudio({
        csrf: @js(csrf_token()),
        publishUrl: @js(route('emprendedores.negocios.lives.studio.publish', [$businessId, $liveStreamSlug])),
        stopUrl: @js(route('emprendedores.negocios.lives.studio.destroy', [$businessId, $liveStreamSlug])),
        returnUrl: @js(route('emprendedores.negocios.lives', $businessId)),
        product: @js($initialProductOverlay),
        poll: @js($initialPollOverlay),
        introVideoActive: @js($this->live->intro_video_active),
        isLive: @js($this->live->isLive()),
    })"
>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('emprendedores.negocios.lives', $businessId) }}" wire:navigate class="mb-2 inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left class="size-4" /> {{ __('Volver a transmisiones') }}
            </a>
            <flux:heading size="xl" class="!text-3xl !font-bold">{{ __('Estudio Live') }}</flux:heading>
            <flux:subheading>{{ $this->live->title }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <flux:button type="button" size="sm" variant="ghost" icon="document-duplicate" x-on:click="navigator.clipboard.writeText(@js($publicLiveUrl)); $flux.toast(@js(__('Enlace del Live copiado')))" aria-label="{{ __('Copiar enlace') }}">{{ __('Copiar enlace') }}</flux:button>
            <flux:button type="button" size="sm" variant="ghost" icon="share" x-on:click="navigator.share ? navigator.share({ title: @js($this->live->title), text: @js($publicLiveShareText), url: @js($publicLiveUrl) }) : (navigator.clipboard.writeText(@js($publicLiveUrl)), $flux.toast(@js(__('Enlace del Live copiado'))))">{{ __('Compartir') }}</flux:button>
            <flux:button :href="$publicLiveUrl" target="_blank" rel="noopener" size="sm" variant="primary" icon="arrow-top-right-on-square">{{ __('Abrir Live') }}</flux:button>
            @if ($this->live->isLive())
                <button type="button" x-on:click="if (confirm(@js(__('¿Deseas detener y finalizar esta transmisión?')))) { state === 'live' && session ? stopBroadcast() : $wire.finishBroadcast() }" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700"><flux:icon.stop class="size-4" />{{ __('Detener') }}</button>
            @endif
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold" :class="state === 'live' || @js($this->live->isLive()) ? 'bg-red-100 text-red-700' : 'bg-zinc-100 text-zinc-600'">
                <span class="size-2 rounded-full" :class="state === 'live' || @js($this->live->isLive()) ? 'animate-pulse bg-red-600' : 'bg-zinc-400'"></span>
                <span x-text="state === 'live' || @js($this->live->isLive()) ? @js(__('EN VIVO')) : (state === 'connecting' ? @js(__('Conectando')) : @js(__('Vista previa'))) "></span>
            </span>
        </div>
    </header>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(20rem,27rem)_minmax(24rem,1fr)]">
        <div class="relative mx-auto aspect-[9/16] w-full max-w-[27rem] overflow-hidden rounded-[2rem] bg-zinc-950 text-white shadow-2xl" x-data="{ showPollPreview: true }">
            <video wire:ignore x-ref="preview" muted autoplay playsinline class="size-full object-cover" :style="{ transform: cameraMirrored ? 'scaleX(-1)' : 'none', filter: cameraFilter() }"></video>
            <video wire:ignore x-ref="cameraSource" muted autoplay playsinline class="hidden"></video>
            <canvas x-ref="cameraCanvas" width="1280" height="720" x-cloak class="hidden"></canvas>

            @if ($this->live->introVideoUrl())
                <video wire:ignore x-ref="introSource" src="{{ $this->live->introVideoUrl() }}" x-show="introVideoActive" x-cloak class="pointer-events-none absolute inset-0 z-10 size-full object-cover" preload="auto" loop muted playsinline></video>
            @endif

            <div x-show="!stream" class="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 text-center">
                <span class="flex size-20 items-center justify-center rounded-full bg-white/10"><flux:icon.video-camera class="size-10" /></span>
                <div><p class="text-lg font-semibold">{{ __('Prepara tu cámara') }}</p><p class="mt-1 text-sm text-zinc-400">{{ __('Revisa cómo te verá tu comunidad antes de salir en vivo.') }}</p></div>
                <flux:button type="button" variant="primary" icon="video-camera" x-on:click="startPreview" x-bind:disabled="state === 'unsupported'">{{ __('Activar cámara y micrófono') }}</flux:button>
            </div>

            <div class="pointer-events-none absolute inset-x-0 top-0 z-20 bg-gradient-to-b from-black/80 to-transparent p-4 pb-16">
                <div class="flex items-center gap-2">
                    <span class="flex size-10 items-center justify-center overflow-hidden rounded-full border-2 border-white/70 bg-white">
                        @if ($this->live->business->logoUrl())<img src="{{ $this->live->business->logoUrl() }}" alt="{{ $this->live->business->name }}" class="size-full object-cover">@else<flux:icon.building-storefront class="size-5 text-zinc-500" />@endif
                    </span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ $this->live->business->name }}</span><span class="block truncate text-xs text-white/70">{{ $this->live->business->municipality?->name }}</span></span>
                    <span class="rounded-lg bg-red-600 px-2.5 py-1.5 text-[11px] font-bold">{{ $this->live->isLive() ? __('EN VIVO') : __('PREVIA') }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1.5 text-xs"><flux:icon.eye class="size-4" />{{ $this->live->currentViewersCount() }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-black/45 px-2.5 py-1.5 text-xs"><flux:icon.heart class="size-4" />{{ $this->live->reactions()->count() }}</span>
                </div>
            </div>

            @if ($this->live->activePoll)
                @php $studioPoll = $this->live->activePoll; @endphp
                <button type="button" x-show="stream" x-on:click="showPollPreview = ! showPollPreview" class="absolute right-3 top-20 z-40 inline-flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-2 text-xs font-semibold text-white shadow-lg backdrop-blur" :aria-label="showPollPreview ? @js(__('Ocultar encuesta')) : @js(__('Mostrar encuesta'))">
                    <flux:icon.eye-slash x-show="showPollPreview" class="size-4" />
                    <flux:icon.eye x-show="! showPollPreview" class="size-4" />
                    <span x-text="showPollPreview ? @js(__('Ocultar encuesta')) : @js(__('Mostrar encuesta'))"></span>
                </button>
                <div x-cloak x-show="stream && showPollPreview" x-transition class="pointer-events-none absolute inset-x-4 bottom-24 z-30 rounded-2xl bg-white/95 p-4 text-zinc-950 shadow-2xl backdrop-blur">
                    <div class="mb-3 text-center"><flux:icon.chart-bar class="mx-auto mb-2 size-7" /><p class="font-bold">{{ $studioPoll->question }}</p><p class="mt-1 text-xs text-zinc-500">{{ trans_choice(':count voto|:count votos', $studioPoll->votes->count(), ['count' => $studioPoll->votes->count()]) }}</p></div>
                    <div class="space-y-2">
                        @foreach ($studioPoll->results() as $result)
                            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                                <span class="absolute inset-y-0 left-0 bg-zinc-200" style="width: {{ $result['percentage'] }}%"></span>
                                <span class="relative flex justify-between gap-3"><span class="truncate font-medium">{{ $result['label'] }}</span><span class="font-semibold">{{ number_format($result['percentage'], 1, ',', '.') }}%</span></span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->live->introVideoUrl())
                <button type="button" wire:click="toggleIntroVideo" class="absolute left-3 top-20 z-40 inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold text-white shadow-lg backdrop-blur {{ $this->live->intro_video_active ? 'bg-brand-600' : 'bg-black/60' }}">
                    <flux:icon.play-pause class="size-4" />{{ $this->live->intro_video_active ? __('Volver a cámara') : __('Mostrar video inicial') }}
                </button>
            @endif

            <div class="pointer-events-none absolute bottom-16 right-3 z-40 h-44 w-12 overflow-visible" aria-hidden="true">
                <template x-for="reaction in floatingReactions" :key="reaction.id">
                    <span class="live-floating-reaction absolute bottom-0 right-1 text-3xl drop-shadow-lg" :style="`--reaction-x: ${reaction.x}px`" x-text="reaction.emoji"></span>
                </template>
            </div>
            <div class="absolute bottom-3 right-3 z-40" x-on:click.outside="sellerReactionsOpen = false">
                <div x-cloak x-show="sellerReactionsOpen" x-transition class="mb-2 flex flex-col items-center gap-1 rounded-full bg-black/65 p-1.5 shadow-xl backdrop-blur">
                    @foreach ($studioReactionEmojis as $reactionType => $emoji)
                        <button type="button" wire:click="sendReaction('{{ $reactionType }}')" x-on:click="sellerReactionsOpen = false" @disabled(! $this->live->isLive()) class="relative flex size-10 items-center justify-center rounded-full bg-white/15 text-xl transition hover:scale-110 hover:bg-white/25 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Enviar reacción :emoji', ['emoji' => $emoji]) }}">
                            {{ $emoji }}
                            @if ($this->reactionCounts[$reactionType] > 0)<span class="absolute -right-1 -top-1 flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">{{ $this->reactionCounts[$reactionType] }}</span>@endif
                        </button>
                    @endforeach
                </div>
                <button type="button" x-on:click="sellerReactionsOpen = ! sellerReactionsOpen" class="relative flex size-11 items-center justify-center rounded-full bg-black/65 text-xl text-white shadow-lg backdrop-blur transition hover:scale-105 hover:bg-brand-700" :aria-expanded="sellerReactionsOpen" aria-label="{{ __('Enviar una reacción') }}">
                    😊
                    @php $studioReactionTotal = array_sum($this->reactionCounts); @endphp
                    @if ($studioReactionTotal > 0)<span class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold">{{ $studioReactionTotal }}</span>@endif
                </button>
            </div>

            <div x-show="stream || @js($this->live->isLive())" class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-black via-black/75 to-transparent p-4 pt-28">
                @if ($this->live->pinnedProduct)
                    @php
                        $pinned = $this->live->pinnedProduct;
                        $pinnedPhoto = $pinned->media->first();
                        $pinnedPrice = $pinned->hasActivePromo() ? $pinned->promo_price : $pinned->price;
                    @endphp
                    <div class="mb-3 rounded-2xl bg-white p-3 text-zinc-950 shadow-2xl">
                        <div class="flex items-center gap-3">
                            <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-zinc-100">@if ($pinnedPhoto)<img src="{{ $pinnedPhoto->url() }}" alt="{{ $pinned->name }}" class="size-full object-cover">@else<flux:icon.photo class="m-4 size-6 text-zinc-400" />@endif</div>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $pinned->name }}</p>@if ($pinnedPrice)<p class="text-lg font-bold text-brand-600">${{ number_format((float) $pinnedPrice, 0, ',', '.') }}</p>@endif<p class="text-xs text-zinc-500">{{ $pinned->isSoldOut() ? __('Agotado') : __('Disponible') }}</p></div>
                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold text-zinc-600">{{ __('VISIBLE') }}</span>
                        </div>
                    </div>
                @endif
                <div class="flex items-center justify-center gap-3">
                    <button type="button" x-on:click="toggleMicrophone" class="pointer-events-auto flex size-12 items-center justify-center rounded-full" :class="microphoneEnabled ? 'bg-white/20' : 'bg-red-600'" :aria-label="microphoneEnabled ? @js(__('Silenciar micrófono')) : @js(__('Activar micrófono'))"><flux:icon.microphone class="size-6" /></button>
                    <button type="button" x-on:click="toggleCamera" class="pointer-events-auto flex size-12 items-center justify-center rounded-full" :class="cameraEnabled ? 'bg-white/20' : 'bg-red-600'" :aria-label="cameraEnabled ? @js(__('Apagar cámara')) : @js(__('Encender cámara'))"><flux:icon.video-camera class="size-6" /></button>
                    <button type="button" x-show="state !== 'live' && state !== 'connecting'" x-on:click="@js($this->live->isLive()) ? resumeBroadcast() : startBroadcast()" class="pointer-events-auto rounded-full bg-red-600 px-5 py-3 text-sm font-semibold hover:bg-red-700" x-text="@js($this->live->isLive()) ? @js(__('Reanudar transmisión')) : @js(__('Iniciar transmisión'))"></button>
                    <button type="button" x-show="state === 'live'" x-on:click="if (confirm(@js(__('¿Deseas detener y finalizar esta transmisión?')))) { stopBroadcast() }" class="pointer-events-auto rounded-full bg-white px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-zinc-100">{{ __('Detener') }}</button>
                </div>
            </div>
        </div>

        <aside class="entrepreneur-card overflow-hidden p-0" x-data="{ panel: 'products' }">
            <div class="grid grid-cols-4 border-b border-zinc-200 dark:border-zinc-700">
                <button type="button" x-on:click="panel = 'products'" class="px-3 py-3 text-sm font-medium" :class="panel === 'products' ? 'border-b-2 border-brand-600 text-brand-600' : 'text-zinc-500'">{{ __('Productos') }}</button>
                <button type="button" x-on:click="panel = 'chat'" class="px-3 py-3 text-sm font-medium" :class="panel === 'chat' ? 'border-b-2 border-brand-600 text-brand-600' : 'text-zinc-500'">{{ __('Chat') }} <span class="ms-1 rounded-full bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-600">{{ $this->messages->count() }}</span></button>
                <button type="button" x-on:click="panel = 'poll'" class="px-3 py-3 text-sm font-medium" :class="panel === 'poll' ? 'border-b-2 border-brand-600 text-brand-600' : 'text-zinc-500'">{{ __('Encuesta') }}</button>
                <button type="button" x-on:click="panel = 'devices'" class="px-3 py-3 text-sm font-medium" :class="panel === 'devices' ? 'border-b-2 border-brand-600 text-brand-600' : 'text-zinc-500'">{{ __('Configuración') }}</button>
            </div>

            <div x-show="panel === 'products'" class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
                <div><flux:heading>{{ __('Productos del Live') }}</flux:heading><flux:subheading>{{ __('Nada se muestra hasta que pulses Mostrar.') }}</flux:subheading></div>
                @if ($this->live->pinnedProduct)<flux:button type="button" variant="ghost" icon="eye-slash" class="w-full" wire:click="hideProduct">{{ __('Ocultar producto actual') }}</flux:button>@endif
                <div class="space-y-3">
                    @foreach ($this->live->products as $product)
                        @php $photo = $product->media->first(); @endphp
                        <div class="flex items-center gap-3 rounded-xl border p-3 {{ $this->live->pinned_product_id === $product->id ? 'border-brand-500 bg-brand-50 dark:bg-brand-950' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">@if ($photo)<img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="size-full object-cover">@endif</div>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $product->name }}</p>@if ($product->price)<p class="text-xs text-zinc-500">${{ number_format((float) $product->price, 0, ',', '.') }}</p>@endif</div>
                            @if ($this->live->pinned_product_id === $product->id)
                                <flux:badge color="red">{{ __('Visible') }}</flux:badge>
                            @else
                                <flux:button type="button" size="sm" variant="ghost" wire:click="showProduct({{ $product->id }})" :disabled="! $this->live->isLive()">{{ __('Mostrar') }}</flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @unless ($this->live->isLive())<p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ __('Inicia la transmisión para comenzar a mostrar productos.') }}</p>@endunless
            </div>

            <div x-show="panel === 'chat'" class="live-chat-surface flex h-[70vh] flex-col overflow-hidden">
                <div class="border-b border-black/5 bg-white/90 px-5 py-4 backdrop-blur dark:border-white/10 dark:bg-zinc-950/85"><flux:heading>{{ __('Chat en vivo') }}</flux:heading><flux:subheading>{{ trans_choice(':count mensaje|:count mensajes', $this->messages->count(), ['count' => $this->messages->count()]) }}</flux:subheading></div>
                <div class="flex-1 space-y-2.5 overflow-y-auto p-4">
                    @forelse ($this->messages as $chatMessage)
                        @php $ownMessage = $chatMessage->user_id === Auth::id(); @endphp
                        <div class="flex items-end gap-2 {{ $ownMessage ? 'justify-end' : 'justify-start' }}">
                            @unless ($ownMessage)<span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-950 text-xs font-semibold text-white shadow-sm dark:bg-brand-700">{{ str($chatMessage->user->name)->substr(0, 1)->upper() }}</span>@endunless
                            <div class="max-w-[82%] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm {{ $ownMessage ? 'rounded-br-sm bg-brand-600 text-white' : 'rounded-bl-sm bg-white text-zinc-800 dark:bg-zinc-900 dark:text-zinc-100' }}">
                                <div class="flex items-baseline gap-2"><span class="font-semibold {{ $ownMessage ? 'text-white' : 'text-brand-700 dark:text-brand-300' }}">{{ $ownMessage ? __('Tú') : $chatMessage->user->name }}</span><span class="text-[10px] {{ $ownMessage ? 'text-white/70' : 'text-zinc-400' }}">{{ $chatMessage->created_at->format('H:i') }}</span></div>
                                <p class="mt-0.5 break-words leading-5">{{ $chatMessage->body }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="mx-auto mt-6 max-w-sm rounded-2xl border border-black/5 bg-white/80 p-6 text-center text-sm text-zinc-500 shadow-sm backdrop-blur dark:border-white/10 dark:bg-zinc-950/75 dark:text-zinc-400"><flux:icon.chat-bubble-left-right class="mx-auto mb-2 size-7 text-brand-600" />{{ __('Los mensajes de tus espectadores aparecerán aquí.') }}</div>
                    @endforelse
                </div>
                <form wire:submit="sendMessage" class="flex items-center gap-2 border-t border-black/10 bg-zinc-950 p-3 dark:border-white/10">
                    <input wire:model="message" maxlength="280" placeholder="{{ __('Responder al chat…') }}" @disabled(! $this->live->isLive()) class="min-w-0 flex-1 rounded-full border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-50">
                    <button type="submit" @disabled(! $this->live->isLive()) wire:loading.attr="disabled" class="group flex size-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 via-brand-600 to-brand-900 text-white shadow-lg shadow-brand-900/30 transition hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:opacity-45 disabled:hover:scale-100" aria-label="{{ __('Enviar mensaje') }}">
                        <flux:icon.paper-airplane wire:loading.remove wire:target="sendMessage" class="size-5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        <flux:icon.arrow-path wire:loading wire:target="sendMessage" class="size-5 animate-spin" />
                    </button>
                </form>
                @error('body')<p class="bg-zinc-950 px-4 pb-3 text-sm text-red-300">{{ $message }}</p>@enderror
            </div>

            <div x-show="panel === 'poll'" class="max-h-[70vh] space-y-5 overflow-y-auto p-5">
                @php $savedPolls = $this->live->polls->sortByDesc('id')->values(); @endphp
                <div class="flex items-start justify-between gap-3"><div><flux:heading>{{ __('Encuestas del Live') }}</flux:heading><flux:subheading>{{ __('Guarda hasta 5 y decide cuál mostrar durante la transmisión.') }}</flux:subheading></div><flux:badge>{{ $savedPolls->count() }}/5</flux:badge></div>

                @if ($savedPolls->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($savedPolls as $poll)
                            <div wire:key="saved-poll-{{ $poll->id }}" class="space-y-3 rounded-2xl border p-4 {{ $poll->isOpen() ? 'border-brand-300 bg-brand-50 dark:border-brand-800 dark:bg-brand-950' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="font-semibold">{{ $poll->question }}</p><p class="mt-1 text-xs text-zinc-500">{{ trans_choice(':count opción|:count opciones', count($poll->options), ['count' => count($poll->options)]) }} · {{ trans_choice(':count voto|:count votos', $poll->votes->count(), ['count' => $poll->votes->count()]) }}</p></div>
                                    <flux:badge :color="$poll->isOpen() ? 'red' : 'zinc'">{{ $poll->isOpen() ? __('Visible') : __('Oculta') }}</flux:badge>
                                </div>
                                <div class="flex flex-wrap gap-1.5">@foreach ($poll->options as $option)<span class="rounded-full bg-white px-2.5 py-1 text-xs text-zinc-600 shadow-sm dark:bg-zinc-900 dark:text-zinc-300">{{ $option }}</span>@endforeach</div>
                                @if ($poll->isOpen())
                                    <flux:button type="button" variant="ghost" icon="eye-slash" class="w-full" wire:click="hidePoll({{ $poll->id }})">{{ __('Ocultar de la transmisión') }}</flux:button>
                                @else
                                    <flux:button type="button" variant="ghost" icon="eye" class="w-full" wire:click="showPoll({{ $poll->id }})" :disabled="! $this->live->isLive()">{{ __('Mostrar en la transmisión') }}</flux:button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($savedPolls->count() < 5)
                    <form wire:submit="savePoll" class="space-y-4 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                        <div><p class="text-sm font-semibold">{{ __('Crear encuesta') }}</p><p class="mt-1 text-xs text-zinc-500">{{ __('Puedes prepararla antes de iniciar el Live.') }}</p></div>
                        <flux:input wire:model="pollQuestion" :label="__('Pregunta')" maxlength="120" :placeholder="__('Ej: ¿Cuál producto te gusta más?')" />
                        <fieldset class="space-y-2"><legend class="mb-2 text-sm font-medium">{{ __('Opciones') }}</legend>
                            @foreach ($pollOptions as $index => $option)
                                <div class="flex gap-2" wire:key="poll-option-{{ $index }}"><flux:input wire:model="pollOptions.{{ $index }}" maxlength="80" :placeholder="__('Opción :number', ['number' => $index + 1])" class="min-w-0 flex-1" />@if (count($pollOptions) > 2)<flux:button type="button" variant="ghost" icon="trash" wire:click="removePollOption({{ $index }})"><span class="sr-only">{{ __('Eliminar opción') }}</span></flux:button>@endif</div>
                            @endforeach
                        </fieldset>
                        @error('pollOptions.*')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @if (count($pollOptions) < 4)<flux:button type="button" variant="ghost" icon="plus" class="w-full" wire:click="addPollOption">{{ __('Agregar opción') }}</flux:button>@endif
                        <flux:button type="submit" variant="primary" icon="plus" class="w-full">{{ __('Guardar encuesta') }}</flux:button>
                    </form>
                @else
                    <p class="rounded-xl bg-zinc-100 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Ya guardaste el máximo de 5 encuestas para este Live.') }}</p>
                @endif

                @unless ($this->live->isLive())<p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ __('Podrás mostrar una encuesta cuando inicies la transmisión.') }}</p>@endunless
            </div>

            <div x-show="panel === 'devices'" class="space-y-5 p-5">
                <div><flux:heading>{{ __('Configuración') }}</flux:heading><flux:subheading>{{ __('Ajusta la imagen, la cámara y el micrófono antes de iniciar.') }}</flux:subheading></div>
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <span><span class="block text-sm font-semibold">{{ __('Invertir cámara') }}</span><span class="mt-1 block text-xs text-zinc-500">{{ __('Refleja horizontalmente tu previsualización.') }}</span></span>
                    <input type="checkbox" x-model="cameraMirrored" class="size-5 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                </label>
                <div class="space-y-2">
                    <span class="text-sm font-medium">{{ __('Efectos de cámara') }}</span>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" x-on:click="applyCameraEffect('none')" class="flex items-center justify-center gap-1.5 rounded-xl border px-3 py-2.5 text-sm font-medium transition" :class="cameraEffect === 'none' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-950 dark:text-brand-300' : 'border-zinc-200 text-zinc-600 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300'">
                            <flux:icon.video-camera class="size-4" />{{ __('Ninguno') }}
                        </button>
                        <button type="button" x-on:click="applyCameraEffect('soft')" class="flex items-center justify-center gap-1.5 rounded-xl border px-3 py-2.5 text-sm font-medium transition" :class="cameraEffect === 'soft' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-950 dark:text-brand-300' : 'border-zinc-200 text-zinc-600 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300'">
                            <flux:icon.sparkles class="size-4" />{{ __('Suavizar') }}
                        </button>
                        <button type="button" x-on:click="applyCameraEffect('mono')" class="flex items-center justify-center gap-1.5 rounded-xl border px-3 py-2.5 text-sm font-medium transition" :class="cameraEffect === 'mono' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-950 dark:text-brand-300' : 'border-zinc-200 text-zinc-600 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300'">
                            <flux:icon.camera class="size-4" />{{ __('Blanco y negro') }}
                        </button>
                        <button type="button" x-on:click="applyCameraEffect('sepia')" class="flex items-center justify-center gap-1.5 rounded-xl border px-3 py-2.5 text-sm font-medium transition" :class="cameraEffect === 'sepia' ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-950 dark:text-brand-300' : 'border-zinc-200 text-zinc-600 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:text-zinc-300'">
                            <flux:icon.sun class="size-4" />{{ __('Sepia') }}
                        </button>
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('Se aplican también a tu transmisión. Cámbialos en cualquier momento, incluso estando en vivo.') }}</p>
                </div>
                <label class="block space-y-2"><span class="text-sm font-medium">{{ __('Cámara') }}</span><select x-model="cameraId" x-on:change="changeDevices" x-bind:disabled="state === 'live' || state === 'connecting'" class="w-full rounded-xl border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"><template x-for="camera in cameras" :key="camera.deviceId"><option :value="camera.deviceId" x-text="camera.label || @js(__('Cámara disponible'))"></option></template></select></label>
                <label class="block space-y-2"><span class="text-sm font-medium">{{ __('Micrófono') }}</span><select x-model="microphoneId" x-on:change="changeDevices" x-bind:disabled="state === 'live' || state === 'connecting'" class="w-full rounded-xl border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"><template x-for="microphone in microphones" :key="microphone.deviceId"><option :value="microphone.deviceId" x-text="microphone.label || @js(__('Micrófono disponible'))"></option></template></select></label>
                <div class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><p class="font-semibold">{{ __('Calidad recomendada') }}</p><p class="mt-1">{{ __('720p · 30 fps. Usa una conexión estable y buena iluminación.') }}</p></div>
                <div class="space-y-3 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                    <div><p class="text-sm font-semibold">{{ __('Video de inicio') }}</p><p class="mt-1 text-xs text-zinc-500">{{ __('Muéstralo mientras preparas la cámara y vuelve al Live en un toque.') }}</p></div>
                    @if ($this->live->introVideoUrl())
                        <video src="{{ $this->live->introVideoUrl() }}" class="aspect-video w-full rounded-xl bg-black object-cover" controls muted></video>
                        <flux:button type="button" variant="ghost" icon="play-pause" class="w-full" wire:click="toggleIntroVideo">{{ $this->live->intro_video_active ? __('Desactivar video inicial') : __('Activar video inicial') }}</flux:button>
                    @endif
                    <form wire:submit="saveIntroVideo" class="space-y-2">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 p-4 text-sm font-medium hover:border-brand-400 dark:border-zinc-700"><flux:icon.video-camera class="size-5" /><span>{{ $introVideo ? $introVideo->getClientOriginalName() : __('Seleccionar MP4, WEBM o MOV') }}</span><input type="file" wire:model="introVideo" accept="video/mp4,video/webm,video/quicktime" class="sr-only"></label>
                        @error('introVideo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @if ($introVideo)<flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">{{ __('Guardar video de inicio') }}</flux:button>@endif
                    </form>
                </div>
                <div x-show="message" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200" x-text="message"></div>
                <p class="text-xs leading-5 text-zinc-500">{{ __('Tus credenciales de transmisión se gestionan de forma segura y nunca se muestran en este estudio.') }}</p>
            </div>
        </aside>
    </div>
</section>
