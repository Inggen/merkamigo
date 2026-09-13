{{--
    Tarjeta "¿No encuentras lo que necesitas?", repetida tal cual en Inicio
    y en la Plaza (buscar/categoría). El video de fondo es opcional y se
    configura desde el admin (SiteSettings); sin video, conserva el fondo
    plano original.
--}}
@php
    $videoUrl = \App\Domain\Platform\Models\SiteSetting::current()->pideloVideoUrl();
@endphp
<div @class([
    'relative mt-8 overflow-hidden rounded-xl border border-rose-100 dark:border-rose-900/40',
    'h-[300px]' => $videoUrl,
    'bg-rose-50/60 p-6 dark:bg-rose-950/20 sm:p-8' => ! $videoUrl,
])>
    @if ($videoUrl)
        <video
            class="absolute inset-0 h-full w-full object-cover"
            style="height:180%"
            src="{{ $videoUrl }}"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
        ></video>
        <div class="absolute inset-0 bg-gradient-to-r from-black/85 from-0% via-black/55 via-35% to-transparent to-60%"></div>
    @endif
    <div @class(['relative flex h-full flex-col justify-center p-6 sm:p-8' => $videoUrl])>
        <div class="max-w-2xl">
            <span class="flex size-11 items-center justify-center rounded-full bg-brand-600 text-white">
                <flux:icon.chat-bubble-left-right variant="outline" class="size-6" />
            </span>
            <flux:heading size="lg" @class(['mt-5', 'text-white' => $videoUrl])>{{ __('¿No encuentras lo que necesitas?') }}</flux:heading>
            <flux:text @class([
                'mt-3',
                'text-zinc-600 dark:text-zinc-300' => ! $videoUrl,
                'text-zinc-100' => $videoUrl,
            ])>
                {{ __('Publica tu solicitud y recibe propuestas de negocios cercanos listos para ayudar.') }}
            </flux:text>
            <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate class="mt-5 w-fit">
                {{ __('Publicar una solicitud') }}
            </flux:button>
        </div>
    </div>
</div>
