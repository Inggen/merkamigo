@props(['provider'])

@php
    $guides = [
        'youtube' => [
            'title' => __('Transmitir también en YouTube'),
            'notice' => __('La primera activación de YouTube Live puede tardar hasta 24 horas.'),
            'steps' => [
                __('Abre YouTube Studio y selecciona Crear → Emitir en directo.'),
                __('En la pestaña Transmisión, crea o selecciona tu emisión.'),
                __('Copia la URL de emisión RTMPS y la clave de transmisión.'),
                __('Pega ambos datos abajo y guarda el destino.'),
            ],
            'url' => 'https://studio.youtube.com/',
            'cta' => __('Abrir YouTube Studio'),
        ],
        'facebook' => [
            'title' => __('Transmitir también en Facebook'),
            'notice' => __('Debes poder transmitir en el perfil, página, grupo o evento seleccionado.'),
            'steps' => [
                __('Abre Facebook Live Producer y elige dónde publicar.'),
                __('Selecciona Software de streaming como fuente del video.'),
                __('Copia la URL del servidor y la clave de stream.'),
                __('Pega ambos datos abajo y guarda el destino.'),
            ],
            'url' => 'https://www.facebook.com/live/producer',
            'cta' => __('Abrir Live Producer'),
        ],
        'instagram' => [
            'title' => __('Transmitir también en Instagram'),
            'notice' => __('Instagram debe mostrar la opción para transmitir con software externo en tu cuenta.'),
            'steps' => [
                __('Abre Instagram en un computador y selecciona Crear → Video en vivo.'),
                __('Escribe el título, define la audiencia y continúa.'),
                __('Copia la URL de transmisión y la clave que muestra Instagram.'),
                __('Pega ambos datos abajo antes de iniciar el Live en Instagram.'),
            ],
            'url' => 'https://www.instagram.com/',
            'cta' => __('Abrir Instagram'),
        ],
        'tiktok' => [
            'title' => __('Transmitir también en TikTok'),
            'notice' => __('Necesitas acceso a TikTok LIVE y a la transmisión mediante software externo; la disponibilidad depende de la cuenta y la región.'),
            'steps' => [
                __('Abre el Centro LIVE de TikTok y verifica que tu acceso esté activo.'),
                __('Crea un LIVE y selecciona software de streaming si aparece disponible.'),
                __('Copia la URL del servidor y la clave de transmisión.'),
                __('Pega ambos datos abajo y guarda el destino.'),
            ],
            'url' => 'https://www.tiktok.com/live/center',
            'cta' => __('Abrir Centro LIVE'),
        ],
        'custom' => [
            'title' => __('Conectar otro servicio RTMP'),
            'notice' => __('El servicio debe aceptar una señal RTMP o RTMPS enviada desde un codificador externo.'),
            'steps' => [
                __('Abre el panel de transmisión del servicio de destino.'),
                __('Crea una emisión y busca sus datos de configuración manual.'),
                __('Copia la URL RTMP/RTMPS y la clave de transmisión.'),
                __('Pega ambos datos abajo y guarda el destino.'),
            ],
            'url' => null,
            'cta' => null,
        ],
    ];

    $guide = $guides[$provider] ?? $guides['custom'];
@endphp

<section class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="font-semibold">{{ $guide['title'] }}</p>
            <p class="mt-1 text-xs leading-5 text-blue-800 dark:text-blue-200">{{ $guide['notice'] }}</p>
        </div>
        @if ($guide['url'])
            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$guide['url']" target="_blank" rel="noopener noreferrer">
                {{ $guide['cta'] }}
            </flux:button>
        @endif
    </div>

    <ol class="mt-3 space-y-2">
        @foreach ($guide['steps'] as $index => $step)
            <li class="flex gap-2">
                <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[11px] font-semibold text-white">{{ $index + 1 }}</span>
                <span class="leading-5">{{ $step }}</span>
            </li>
        @endforeach
    </ol>
</section>
