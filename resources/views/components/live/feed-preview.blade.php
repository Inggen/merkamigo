@props(['live'])

<a
    href="{{ route('live.show', $live) }}"
    wire:navigate
    data-live-feed-preview
    data-whep-url="{{ $live->stream_origin === 'merkamigo' && $live->isLive() ? route('streaming.whep', $live) : '' }}"
    x-data="merkamigoLiveViewer({ whepUrl: @js($live->stream_origin === 'merkamigo' && $live->isLive() ? route('streaming.whep', $live) : ''), csrf: @js(csrf_token()) })"
    class="group relative flex min-h-56 items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-950 via-red-950 to-zinc-900 text-white"
    aria-label="{{ __('Ver transmisión en vivo: :title', ['title' => $live->title]) }}"
>
    @if ($live->coverUrl())
        <img src="{{ $live->coverUrl() }}" alt="" class="absolute inset-0 size-full object-cover">
    @endif

    <video
        wire:ignore
        x-ref="video"
        autoplay
        playsinline
        muted
        class="absolute inset-0 size-full object-cover"
    ></video>

    <span class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/5 to-black/35"></span>

    <div class="absolute left-4 top-4 z-10 flex flex-wrap items-center gap-2">
        <span class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold">● {{ __('EN VIVO') }}</span>
        <span class="rounded-lg bg-black/55 px-3 py-1.5 text-xs backdrop-blur">{{ $live->currentViewersCount() }} {{ __('viendo') }}</span>
        @if ($live->activePromotion)
            <span class="rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-semibold text-zinc-950">{{ __('Patrocinado') }}</span>
        @endif
    </div>

    <div x-show="state !== 'live'" x-transition.opacity class="relative z-10 text-center">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-white/20 shadow-lg backdrop-blur transition group-hover:scale-105 group-hover:bg-white/30">
            <flux:icon.play variant="solid" class="size-8" />
        </span>
        <p class="mt-4 text-lg font-semibold" x-text="state === 'connecting' ? @js(__('Conectando vista previa…')) : @js(__('Entrar a la transmisión'))"></p>
    </div>

    <span x-show="state === 'live'" x-transition.opacity class="absolute bottom-4 right-4 z-10 inline-flex items-center gap-2 rounded-full bg-black/60 px-4 py-2 text-sm font-semibold backdrop-blur transition group-hover:bg-red-600">
        <flux:icon.play variant="solid" class="size-4" />
        {{ __('Ver Live') }}
    </span>
</a>
