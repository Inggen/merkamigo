<x-mail.platform-layout :preheader="__('Este correo confirma que el envío de Merkamigo está funcionando correctamente.')">
    <div style="text-align:center;">
        <div style="display:inline-block; padding:10px 14px; border-radius:999px; background:#fff1f2; color:#c9251d; font-size:13px; line-height:18px; font-weight:700;">
            {{ __('Prueba de correo completada') }}
        </div>

        <h1 style="margin:22px 0 10px; color:#102033; font-size:30px; line-height:36px; font-weight:800;">
            {{ __('¡El correo está funcionando!') }}
        </h1>

        <p style="margin:0 auto; max-width:510px; color:#64748b; font-size:17px; line-height:27px;">
            {{ __('Este mensaje se envió desde el módulo de mantenimiento usando la plantilla general de Merkamigo.') }}
        </p>
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px; width:100%; border-radius:16px; background:#fff7f7;">
        <tr>
            <td style="padding:20px 22px;">
                <p style="margin:0 0 8px; color:#102033; font-size:15px; line-height:22px; font-weight:700;">{{ __('Detalles del envío') }}</p>
                <p style="margin:0; color:#64748b; font-size:14px; line-height:22px;">
                    {{ __('Enviado por: :name', ['name' => $sentBy]) }}<br>
                    {{ __('Fecha: :date', ['date' => $sentAt->timezone(config('app.timezone'))->format('d/m/Y h:i a')]) }}
                </p>
            </td>
        </tr>
    </table>
</x-mail.platform-layout>
