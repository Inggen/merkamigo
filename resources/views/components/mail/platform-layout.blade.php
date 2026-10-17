@props([
    'preheader' => '',
])

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name', 'Merkamigo') }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f6f9; color:#102033; font-family:Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">{{ $preheader }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; background:#f3f6f9;">
        <tr>
            <td align="center" style="padding:34px 14px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; max-width:640px; background:#ffffff; border:1px solid #e2e8f0; border-radius:24px; box-shadow:0 12px 35px rgba(15, 23, 42, .08);">
                    <tr>
                        <td align="center" style="padding:32px 18px 22px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:12px; vertical-align:middle;">
                                        <img src="{{ asset('icons/icon-192.png') }}" width="60" height="60" alt="Merkamigo" style="display:block; width:60px; height:60px; border:0;">
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <div style="font-size:30px; line-height:34px; font-weight:800; letter-spacing:-1px; color:#c9251d;">Merka<span style="color:#102033;">migo</span></div>
                                        <div style="margin-top:4px; font-size:9px; line-height:13px; font-weight:700; letter-spacing:1px; color:#64748b;">CERCANÍA · COMUNIDAD · VISIBILIDAD</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 24px 34px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 30px;">
                            <div style="height:1px; background:#e2e8f0;"></div>
                            <p style="margin:24px 0 8px; text-align:center; font-size:16px; line-height:24px; font-weight:700; color:#102033;">Descubre lo local, conecta con tu comunidad. <span style="color:#dc2626;">♡</span></p>
                            <p style="margin:0; text-align:center; font-size:13px; line-height:20px; color:#94a3b8;">© {{ now()->year }} Merkamigo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
