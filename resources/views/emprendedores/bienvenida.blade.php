@php
    $schemaGraph = [\App\Support\Seo\SchemaBuilder::breadcrumb([
        ['name' => __('Inicio'), 'url' => route('home')],
        ['name' => __('Crear vitrina')],
    ])];

    // Pedido del usuario (2026-10-14): banner (imagen, título y
    // descripción) editable desde Configuración del sitio en el admin.
    $siteSetting = \App\Domain\Platform\Models\SiteSetting::current();
    $bannerImage = $siteSetting->bienvenidaBannerUrl() ?? asset('images/emprendedores/bienvenida-hero.jpg');
    $bannerTitle = $siteSetting->bienvenida_banner_title;
    $bannerDescription = $siteSetting->bienvenida_banner_description ?: __('Crea tu vitrina digital en minutos, muestra lo que vendes y conecta con personas de tu comunidad.');
@endphp

<x-layouts::public :title="__('Crear una vitrina digital')" :description="__('Crea gratis tu vitrina digital en Merkamigo y conecta con personas de tu comunidad.')" :canonical="route('emprendedores.bienvenida')" :schema-graph="$schemaGraph">
    <div class="entrepreneur-welcome">
        <section class="welcome-hero" aria-labelledby="welcome-title"
            x-data="{ parallaxOffset: 0, reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)') }"
            x-init="parallaxOffset = reducedMotion.matches ? 0 : Math.min(60, Math.max(0, window.scrollY * 0.15))"
            x-on:scroll.window.passive="parallaxOffset = reducedMotion.matches ? 0 : Math.min(60, Math.max(0, window.scrollY * 0.15))">

            <img x-bind:style="`transform: translateY(${parallaxOffset}px)`" class="welcome-hero-image" src="{{ $bannerImage }}" alt="{{ __('Emprendedora en su pastelería con una vitrina digital') }}" fetchpriority="high">
            <div class="welcome-container welcome-hero-content">
                <p class="welcome-eyebrow">{{ __('Compra, vende y apoya lo local') }}</p>
                <h1 id="welcome-title">
                    @if ($bannerTitle)
                        {{ $bannerTitle }}
                    @else
                        {{ __('Tu negocio local,') }}<br><span>{{ __('más cerca de todos') }}</span>
                    @endif
                </h1>
                <p class="welcome-intro">{{ $bannerDescription }}</p>
                <a class="welcome-cta" href="{{ url('/emprendedores') }}" wire:navigate>{{ __('Crear mi vitrina gratis') }} <flux:icon.arrow-right class="size-5" /></a>
                <ul class="welcome-assurances">
                    @foreach (['Fácil de usar', 'Sin costos iniciales', 'Lista en 5 minutos'] as $assurance)
                        <li><span><flux:icon.check class="size-4" /></span>{{ __($assurance) }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="welcome-benefits" aria-label="{{ __('Beneficios para tu negocio') }}">
            <div class="welcome-container welcome-benefit-grid">
                @foreach ($benefits as $benefit)
                    <article class="welcome-benefit welcome-benefit-{{ $loop->index }}">
                        <div class="welcome-benefit-copy">
                            <span class="welcome-icon"><x-dynamic-component :component="'flux::icon.'.$benefit['icon']" class="size-8" variant="outline" /></span>
                            <h2>{{ __($benefit['title']) }}</h2>
                            <p>{{ __($benefit['description']) }}</p>
                        </div>
                        <div class="welcome-benefit-photo" style="background-image: url('{{ asset('images/emprendedores/bienvenida-beneficios.jpg') }}')" aria-hidden="true"></div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="welcome-tools" aria-labelledby="welcome-tools-title">
            <div class="welcome-container">
                <header class="welcome-tools-heading">
                    <p class="welcome-eyebrow">{{ __('Todo en un solo lugar') }}</p>
                    <h2 id="welcome-tools-title">{{ __('¿Qué puedes hacer en') }} <span>Merka</span>migo?</h2>
                    <p>{{ __('Herramientas simples y poderosas para mostrar tu negocio, conectar con clientes y hacer crecer tu comunidad.') }}</p>
                </header>
                <div class="welcome-tools-grid">
                    <div class="welcome-feature-list">
                        @foreach (array_slice($features, 0, 3) as $feature)
                            @include('emprendedores.partials.welcome-feature')
                        @endforeach
                    </div>
                    <div class="welcome-device-preview" aria-label="{{ __('Vista de las vitrinas de Merkamigo en computador y móvil') }}">
                        <div class="welcome-laptop">
                            <div class="welcome-laptop-screen">
                                <aside><strong><span>Merka</span>migo</strong><p>{{ __('Mi vitrina') }}</p><p>{{ __('Productos') }}</p><p>{{ __('Eventos') }}</p><p>{{ __('Mensajes') }}</p><p>{{ __('Estadísticas') }}</p></aside>
                                <div class="welcome-demo-store"><h3>{{ __('Mi vitrina') }}</h3><img src="{{ asset('images/emprendedores/bienvenida-hero.jpg') }}" alt="" loading="lazy"><strong>{{ __('Tu negocio') }}</strong><p>{{ __('Productos y servicios para tu comunidad') }}</p><div class="welcome-demo-stats"><span><b>128</b>{{ __('Visitas') }}</span><span><b>24</b>{{ __('Clientes') }}</span><span><b>12</b>{{ __('Mensajes') }}</span></div></div>
                            </div>
                        </div>
                        <div class="welcome-phone"><strong><span>Merka</span>migo</strong><div class="welcome-phone-search">{{ __('Busca negocios cerca…') }}</div><div class="welcome-phone-categories">☕ &nbsp; ♡ &nbsp; ✧</div><h3>{{ __('Negocios cerca de ti') }}</h3><img src="{{ asset('images/emprendedores/bienvenida-hero.jpg') }}" alt="" loading="lazy"><strong>{{ __('Tu negocio') }}</strong><p>{{ __('Hecho en tu comunidad') }}</p><div class="welcome-phone-stars">★★★★★</div><div class="welcome-phone-nav"><flux:icon.home class="size-4" /><flux:icon.magnifying-glass class="size-4" /><flux:icon.chat-bubble-left-right class="size-4" /><flux:icon.user class="size-4" /></div></div>
                    </div>
                    <div class="welcome-feature-list">
                        @foreach (array_slice($features, 3) as $feature)
                            @include('emprendedores.partials.welcome-feature')
                        @endforeach
                    </div>
                </div>
                <div class="welcome-bottom-cta"><a class="welcome-cta" href="{{ url('/emprendedores') }}" wire:navigate>{{ __('Crear mi vitrina gratis') }} <flux:icon.arrow-right class="size-5" /></a></div>
            </div>
        </section>
    </div>
</x-layouts::public>
