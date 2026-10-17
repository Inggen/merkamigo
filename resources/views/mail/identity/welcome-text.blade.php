¡Bienvenido, {{ $name }}!

{{ $isEntrepreneur ? 'Tu vitrina está a un paso de empezar a vender.' : 'Descubre lo mejor de tu comunidad en minutos.' }}

@if ($isEntrepreneur)
1. Crea tu vitrina: agrega tu negocio y categoría.
2. Sube tus productos: incluye fotos, precios y descripciones.
3. Comparte y vende: usa tu enlace o QR en redes y WhatsApp.
@else
1. Explora negocios: encuentra tiendas, servicios y productos.
2. Guarda favoritos: ten a la mano lo que más te gusta.
3. Compra y apoya local: conecta con emprendedores cerca de ti.
@endif

{{ $isEntrepreneur ? 'Completar mi vitrina' : 'Explorar Merkamigo' }}: {{ $primaryUrl }}
{{ $isEntrepreneur ? 'Ver mi panel' : 'Ver mi cuenta' }}: {{ $secondaryUrl }}

Descubre lo local, conecta con tu comunidad.
© {{ now()->year }} Merkamigo.
