{{ __('Correo de prueba de Merkamigo') }}

{{ __('¡El correo está funcionando!') }}

{{ __('Este mensaje se envió desde el módulo de mantenimiento usando la plantilla general de Merkamigo.') }}

{{ __('Enviado por: :name', ['name' => $sentBy]) }}
{{ __('Fecha: :date', ['date' => $sentAt->timezone(config('app.timezone'))->format('d/m/Y h:i a')]) }}

© {{ now()->year }} Merkamigo
