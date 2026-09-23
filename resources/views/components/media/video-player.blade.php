@props([
    'src',
    'poster' => null,
    'autoplay' => false,
    'muted' => true,
    'loop' => false,
    'preload' => 'metadata',
    'fit' => 'contain',
    'aspect' => 'video',
    'brand' => true,
])

@php
    $aspectClass = match ($aspect) {
        'reel' => 'aspect-[9/16]',
        'feed' => 'aspect-[4/3]',
        'auto' => '',
        default => 'aspect-video',
    };
    $fitClass = $fit === 'cover' ? 'object-cover' : 'object-contain';
@endphp

<div
    x-data="merkamigoVideoPlayer({ muted: @js($muted), src: @js($src) })"
    x-on:mousemove="showControls"
    x-on:mouseleave="playing && (controlsVisible = false)"
    x-on:focusin="controlsVisible = true"
    x-on:keydown.space.prevent="togglePlayback"
    x-on:keydown.arrow-right.prevent="$refs.video.currentTime = Math.min(duration, $refs.video.currentTime + 5)"
    x-on:keydown.arrow-left.prevent="$refs.video.currentTime = Math.max(0, $refs.video.currentTime - 5)"
    tabindex="0"
    {{ $attributes->class(['group/video relative isolate w-full overflow-hidden rounded-2xl bg-black text-white shadow-sm outline-none ring-brand-500/70 focus-visible:ring-2', $aspectClass]) }}
>
    <video
        x-ref="video"
        @if ($poster) poster="{{ $poster }}" @endif
        @if ($autoplay) autoplay @endif
        @if ($muted) muted @endif
        @if ($loop) loop @endif
        preload="{{ $preload }}"
        playsinline
        class="absolute inset-0 size-full {{ $fitClass }}"
        x-on:click="togglePlayback"
        x-on:loadedmetadata="duration = $refs.video.duration || 0"
        x-on:durationchange="duration = $refs.video.duration || 0"
        x-on:timeupdate="currentTime = $refs.video.currentTime"
        x-on:play="playing = true; showControls()"
        x-on:pause="playing = false; controlsVisible = true"
        x-on:ended="playing = false; controlsVisible = true"
    ></video>

    @if ($brand)
        <div class="pointer-events-none absolute left-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-full bg-black/55 px-2.5 py-1 text-[11px] font-semibold tracking-wide text-white shadow-sm backdrop-blur-md">
            <span class="flex size-4 items-center justify-center rounded bg-brand-600 text-[9px] font-bold">M</span>
            Merkamigo
        </div>
    @endif

    <button
        type="button"
        x-show="!playing"
        x-transition.opacity
        x-on:click="togglePlayback"
        class="absolute left-1/2 top-1/2 z-10 flex size-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-brand-600/95 text-white shadow-xl backdrop-blur transition hover:scale-105 hover:bg-brand-500"
        aria-label="{{ __('Reproducir video') }}"
    >
        <flux:icon.play class="ms-1 size-7" />
    </button>

    <div
        x-show="controlsVisible"
        x-transition.opacity
        class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-black/95 via-black/60 to-transparent px-3 pb-3 pt-14 sm:px-4"
    >
        <input
            type="range"
            min="0"
            step="0.05"
            x-bind:max="duration || 0"
            x-bind:value="currentTime"
            x-on:input="seek"
            x-bind:style="`--video-progress: ${duration ? (currentTime / duration) * 100 : 0}%`"
            class="merkamigo-video-progress mb-2 w-full"
            aria-label="{{ __('Progreso del video') }}"
        >

        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" x-on:click="togglePlayback" class="video-control-button" x-bind:aria-label="playing ? @js(__('Pausar video')) : @js(__('Reproducir video'))">
                <flux:icon.play x-show="!playing" class="size-5" />
                <flux:icon.pause x-show="playing" x-cloak class="size-5" />
            </button>

            <span class="min-w-[5.5rem] text-xs font-medium tabular-nums text-white/90">
                <span x-text="formatTime(currentTime)"></span>
                <span class="text-white/50"> / </span>
                <span x-text="formatTime(duration)"></span>
            </span>

            <div class="ms-auto flex items-center gap-1 sm:gap-2">
                <button type="button" x-on:click="toggleMute" class="video-control-button" x-bind:aria-label="muted ? @js(__('Activar sonido')) : @js(__('Silenciar'))">
                    <flux:icon.speaker-x-mark x-show="muted" class="size-5" />
                    <flux:icon.speaker-wave x-show="!muted" x-cloak class="size-5" />
                </button>
                <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    x-bind:value="muted ? 0 : volume"
                    x-on:input="changeVolume"
                    class="hidden h-1 w-16 cursor-pointer accent-brand-500 sm:block"
                    aria-label="{{ __('Volumen') }}"
                >
                <button type="button" x-on:click="toggleFullscreen" class="video-control-button" x-bind:aria-label="fullscreen ? @js(__('Salir de pantalla completa')) : @js(__('Pantalla completa'))">
                    <flux:icon.arrows-pointing-out x-show="!fullscreen" class="size-5" />
                    <flux:icon.arrows-pointing-in x-show="fullscreen" x-cloak class="size-5" />
                </button>
            </div>
        </div>
    </div>
</div>
