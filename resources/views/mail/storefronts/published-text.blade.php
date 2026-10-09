¡Felicitaciones, {{ $name }}!

Tu vitrina {{ $businessName }} ya está publicada.
Ahora tu comunidad puede encontrarte, conocer tus productos y ponerse en contacto contigo.

Ver mi vitrina: {{ $storefrontUrl }}

IMPULSA TU NEGOCIO
@forelse ($plans as $plan)
- {{ $plan->name }}: {{ $plan->isFree() ? 'Gratis' : '$'.number_format($plan->price_cents / 100, 0, ',', '.').' COP '.$plan->billing_period }}
@empty
Consulta las herramientas disponibles para llevar tu vitrina más lejos.
@endforelse

Conocer los planes: {{ $plansUrl }}

Descubre lo local, conecta con tu comunidad.
© {{ now()->year }} Merkamigo.
