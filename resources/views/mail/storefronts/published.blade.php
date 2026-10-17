<x-mail.platform-layout :preheader="__('Tu vitrina :business ya está publicada.', ['business' => $businessName])">
    <div style="margin:0 auto 20px; width:72px; height:72px; border-radius:50%; background:#fff0f1; color:#d9232e; font-size:36px; line-height:72px; text-align:center;">✓</div>

    <h1 style="margin:0; text-align:center; font-size:36px; line-height:44px; letter-spacing:-1px; color:#102033;">
        ¡Felicitaciones, <span style="color:#d9232e;">{{ $name }}</span>!
    </h1>
    <p style="margin:10px 0 8px; text-align:center; font-size:22px; line-height:30px; font-weight:700; color:#102033;">
        {{ __('Tu vitrina :business ya está publicada.', ['business' => $businessName]) }}
    </p>
    <p style="margin:0 auto 26px; max-width:520px; text-align:center; font-size:16px; line-height:25px; color:#64748b;">
        {{ __('Ahora tu comunidad puede encontrarte, conocer tus productos y ponerse en contacto contigo.') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ $storefrontUrl }}" style="display:inline-block; width:100%; max-width:320px; box-sizing:border-box; padding:16px 22px; border-radius:12px; background:#d9232e; color:#ffffff; font-size:18px; line-height:22px; font-weight:800; text-decoration:none;">
                    {{ __('Ver mi vitrina') }} &nbsp;→
                </a>
            </td>
        </tr>
    </table>

    <div style="height:1px; margin:34px 0 26px; background:#e2e8f0;"></div>

    <h2 style="margin:0; text-align:center; font-size:25px; line-height:32px; color:#102033;">{{ __('Impulsa tu negocio') }}</h2>
    <p style="margin:7px 0 20px; text-align:center; font-size:16px; line-height:24px; color:#64748b;">{{ __('Elige el plan que acompaña el siguiente paso de tu vitrina.') }}</p>

    @forelse ($plans as $plan)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; margin-bottom:10px; border:1px solid #fecdd3; border-radius:14px; background:#fffafa;">
            <tr>
                <td style="padding:17px 18px;">
                    <div style="font-size:18px; line-height:24px; font-weight:800; color:#102033;">{{ $plan->name }}</div>
                    @if ($plan->description)
                        <div style="margin-top:4px; font-size:14px; line-height:21px; color:#64748b;">{{ $plan->description }}</div>
                    @endif
                </td>
                <td align="right" style="padding:17px 18px; white-space:nowrap; vertical-align:middle; font-size:17px; font-weight:800; color:#d9232e;">
                    {{ $plan->isFree() ? __('Gratis') : '$'.number_format($plan->price_cents / 100, 0, ',', '.').' COP' }}
                    @unless ($plan->isFree())
                        <div style="margin-top:2px; font-size:11px; font-weight:600; color:#94a3b8;">{{ __($plan->billing_period) }}</div>
                    @endunless
                </td>
            </tr>
        </table>
    @empty
        <p style="margin:0; text-align:center; font-size:15px; line-height:23px; color:#64748b;">{{ __('Consulta las herramientas disponibles para llevar tu vitrina más lejos.') }}</p>
    @endforelse

    <div style="margin-top:22px; text-align:center;">
        <a href="{{ $plansUrl }}" style="display:inline-block; padding:14px 26px; border:1px solid #d9232e; border-radius:11px; color:#d9232e; font-size:16px; line-height:20px; font-weight:800; text-decoration:none;">{{ __('Conocer los planes') }} &nbsp;→</a>
    </div>
</x-mail.platform-layout>
