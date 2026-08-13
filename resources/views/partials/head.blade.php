@php
    $seoTitle = $title ?? config('app.name', 'Laravel');
    $pageTitle = filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel');
    $pageDescription = $description ?? __('Descubre lo local, conecta con tu comunidad. Merkamigo conecta emprendedores locales con compradores cercanos en Bogotá y Sabana Norte.');
    $pageImage = $image ?? \App\Domain\Platform\Models\SiteSetting::current()->defaultShareImageUrl() ?? asset('images/backgrounds/fondo-redes-merkamigo.png');
    $canonicalUrl = $canonical ?? url()->full();
    $robotsContent = $robots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    $pageSchemaType = $pageSchemaType ?? 'WebPage';
    $pageSchemaData = $pageSchemaData ?? [];
    $mainNavigationSchema = \App\Support\Seo\SchemaBuilder::siteNavigation([
        [
            'name' => __('Inicio'),
            'description' => __('Portada de Merkamigo con acceso a negocios, productos y municipios.'),
            'url' => route('home'),
        ],
        [
            'name' => __('Municipios'),
            'description' => __('Explora los municipios activos y sus plazas digitales.'),
            'url' => route('municipios'),
        ],
        [
            'name' => __('Categorías'),
            'description' => __('Navega por categorías de negocios, productos y servicios.'),
            'url' => route('categorias'),
        ],
        [
            'name' => __('Plaza'),
            'description' => __('Busca negocios, productos y servicios en la plaza pública de Merkamigo.'),
            'url' => route('buscar'),
        ],
        [
            'name' => __('Pídelo'),
            'description' => __('Publica o explora solicitudes para recibir propuestas de negocios cercanos.'),
            'url' => route('pidelo'),
        ],
        [
            'name' => __('Cómo funciona'),
            'description' => __('Conoce cómo comprar, vender y conectar dentro de Merkamigo.'),
            'url' => route('como-funciona'),
        ],
        [
            'name' => __('Soporte'),
            'description' => __('Encuentra ayuda, preguntas frecuentes y canales de soporte.'),
            'url' => route('soporte'),
        ],
        [
            'name' => __('Crear vitrina'),
            'description' => __('Crea tu vitrina digital para publicar tu negocio en tu comunidad.'),
            'url' => route('emprendedores.bienvenida'),
        ],
    ]);
    $schemaGraph = array_values(array_filter([
        \App\Support\Seo\SchemaBuilder::organization(),
        \App\Support\Seo\SchemaBuilder::website(),
        $mainNavigationSchema,
        \App\Support\Seo\SchemaBuilder::webPage($seoTitle, $pageDescription, $canonicalUrl, array_merge([
            'type' => $pageSchemaType,
            'image' => $pageImage,
        ], $pageSchemaData)),
        ...($schemaGraph ?? []),
    ]));
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="robots" content="{{ $robotsContent }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:image" content="{{ $pageImage }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $pageImage }}">
<meta name="application-name" content="{{ config('app.name', 'Laravel') }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
{{-- Google exige un favicon cuadrado, múltiplo de 48px, para mostrarlo en
     resultados de búsqueda — favicon.ico (32x32) y favicon.svg (392x414,
     no cuadrado) no cumplen esos requisitos por sí solos. Estos PNG
     cuadrados sí califican. --}}
<link rel="icon" href="{{ asset('icons/icon-512.png') }}" sizes="512x512" type="image/png">
<link rel="icon" href="{{ asset('icons/icon-192.png') }}" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="#D7352A">

<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $schemaGraph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
@stack('head')
