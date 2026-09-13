{{--
    Tarjeta "Haz visible tu negocio en tu comunidad", repetida tal cual en
    Inicio y en la Plaza (buscar/categoría). El video de fondo es opcional
    y se configura desde el admin (SiteSettings); sin video, conserva el
    fondo plano con la imagen decorativa original.
--}}
@php
    $videoUrl = \App\Domain\Platform\Models\SiteSetting::current()->createVitrinaVideoUrl();
@endphp
<div @class([
    'relative mt-10 overflow-hidden rounded-xl border border-rose-100 dark:border-rose-900/40',
    'h-[300px]' => $videoUrl,
    'bg-rose-50/60 dark:bg-rose-950/20' => ! $videoUrl,
])>
    @if ($videoUrl)
        <video
            class="absolute inset-0 h-full w-full object-cover object-[52%_25%]"
            src="{{ $videoUrl }}"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
        ></video>
        <div class="absolute inset-0 bg-gradient-to-r from-black/85 from-0% via-black/55 via-35% to-transparent to-60%"></div>
    @endif
    <div @class([
        'relative flex flex-col items-center gap-6 p-8 sm:flex-row sm:justify-between sm:p-10',
        'h-full !flex-row !items-center !justify-start' => $videoUrl,
    ])>
        <div class="max-w-lg text-center sm:text-left">
            <flux:heading size="lg" @class(['text-white' => $videoUrl])>{{ __('Haz visible tu negocio en tu comunidad') }}</flux:heading>
            <flux:text @class([
                'mt-2',
                'text-zinc-600 dark:text-zinc-300' => ! $videoUrl,
                'text-zinc-100' => $videoUrl,
            ])>
                {{ __('Crea tu vitrina gratis y llega a más personas de tu zona que ya están comprando local.') }}
            </flux:text>
            <flux:button variant="primary" :href="route('emprendedores.bienvenida')" wire:navigate class="mt-4 w-fit">
                {{ __('Crear mi vitrina gratis') }}
            </flux:button>
        </div>
        @unless ($videoUrl)
            <img src="{{ asset('images/fondo-login-admin.svg') }}" alt="" class="hidden w-full shrink-0 opacity-50 sm:block" style="max-width: 700px" loading="lazy">
        @endunless
    </div>
</div>
