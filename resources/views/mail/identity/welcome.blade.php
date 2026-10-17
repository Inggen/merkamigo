@php
    $steps = $isEntrepreneur
        ? [
            ['Crea tu vitrina', 'Agrega tu negocio y categoría.'],
            ['Sube tus productos', 'Incluye fotos, precios y descripciones.'],
            ['Comparte y vende', 'Usa tu enlace o QR en redes y WhatsApp.'],
        ]
        : [
            ['Explora negocios', 'Encuentra tiendas, servicios y productos.'],
            ['Guarda favoritos', 'Ten a la mano lo que más te gusta.'],
            ['Compra y apoya local', 'Conecta con emprendedores cerca de ti.'],
        ];
@endphp

<x-mail.platform-layout :preheader="$isEntrepreneur ? __('Tu vitrina está a un paso de empezar a vender.') : __('Descubre lo mejor de tu comunidad en minutos.')">
    <h1 style="margin:0; text-align:center; font-size:38px; line-height:46px; letter-spacing:-1.2px; color:#102033;">
        ¡Bienvenido, <span style="color:#d9232e;">{{ $name }}</span>!
    </h1>
    <p style="margin:8px 0 30px; text-align:center; font-size:22px; line-height:30px; color:#64748b;">
        {{ $isEntrepreneur ? __('Tu vitrina está a un paso de empezar a vender.') : __('Descubre lo mejor de tu comunidad en minutos.') }}
    </p>

    <h2 style="margin:0 0 18px; text-align:center; font-size:25px; line-height:32px; color:#102033;">
        {{ $isEntrepreneur ? __('Sigue estos 3 pasos') : __('Empieza así') }}
    </h2>

    @foreach ($steps as [$title, $description])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; margin-bottom:10px; background:#fff3f3; border-radius:14px;">
            <tr>
                <td width="76" align="center" style="padding:16px 8px 16px 18px;">
                    <div style="width:48px; height:48px; border-radius:50%; background:#ffe0e2; color:#d9232e; font-size:23px; line-height:48px; font-weight:800; text-align:center;">{{ $loop->iteration }}</div>
                </td>
                <td style="padding:16px 18px 16px 8px;">
                    <div style="font-size:18px; line-height:24px; font-weight:800; color:#102033;">{{ __($title) }}</div>
                    <div style="margin-top:2px; font-size:15px; line-height:22px; color:#64748b;">{{ __($description) }}</div>
                </td>
            </tr>
        </table>
    @endforeach

    <p style="margin:24px auto; max-width:520px; text-align:center; font-size:17px; line-height:26px; color:#64748b;">
        {{ $isEntrepreneur
            ? __('Entra a tu panel y completa lo básico. Luego podrás seguir descubriendo más herramientas dentro de Merkamigo.')
            : __('Entra y empieza a descubrir más opciones, promociones y emprendimientos dentro de Merkamigo.') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ $primaryUrl }}" style="display:inline-block; width:100%; max-width:340px; box-sizing:border-box; padding:16px 22px; border-radius:12px; background:#d9232e; color:#ffffff; font-size:18px; line-height:22px; font-weight:800; text-decoration:none;">
                    {{ $isEntrepreneur ? __('Completar mi vitrina') : __('Explorar Merkamigo') }} &nbsp;→
                </a>
                <div style="margin-top:16px;">
                    <a href="{{ $secondaryUrl }}" style="font-size:15px; color:#d9232e; text-decoration:underline;">
                        {{ $isEntrepreneur ? __('Ver mi panel') : __('Ver mi cuenta') }}
                    </a>
                </div>
            </td>
        </tr>
    </table>
</x-mail.platform-layout>
