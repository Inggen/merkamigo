<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => "Plaza inmersiva — {$plaza->name}",
            'description' => "Escena inmersiva de {$municipio->name} armada desde los datos de la plaza, sin escena escrita a mano.",
            'image' => $municipio->coverUrl(),
        ])
        <style>
            body {
                margin: 0;
                overflow: hidden;
                background: #07111f;
                color: #fff;
                font-family: var(--font-sans, 'Instrument Sans', sans-serif);
            }

            .voxel-lab-shell {
                position: relative;
                width: 100vw;
                height: 100vh;
                background: radial-gradient(circle at top, rgba(215, 106, 61, 0.22), transparent 32%), #07111f;
            }

            /* Mismo header que el resto de la app (`cliente-nav.blade.php`:
               logo + acciones + cuenta), adaptado al fondo oscuro de la
               escena — flota encima del canvas sin taparlo (altura fija,
               el resto del layout no depende de ella). */
            .voxel-lab-header {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                z-index: 20;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 16px;
                background: linear-gradient(to bottom, rgba(7, 17, 31, 0.82), rgba(7, 17, 31, 0));
            }

            .voxel-lab-header-logo {
                display: flex;
                align-items: center;
                flex-shrink: 0;
            }

            .voxel-lab-header-logo svg {
                height: 34px;
                width: auto;
            }

            .voxel-lab-header-actions {
                margin-left: auto;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .voxel-lab-header-account {
                flex-shrink: 0;
                margin-left: 4px;
            }

            @media (max-width: 480px) {
                .voxel-lab-header { padding: 8px 12px; }
                .voxel-lab-header-logo svg { height: 28px; }
            }

            #generic-immersive-scene {
                width: 100%;
                height: 100%;
                cursor: grab;
            }

            #generic-immersive-scene.is-locked {
                cursor: none;
            }

            .voxel-lab-status {
                position: absolute;
                bottom: 20px;
                left: 50%;
                z-index: 20;
                transform: translateX(-50%);
                width: max-content;
                max-width: calc(100vw - 28px);
                padding: 6px 12px;
                /*border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                background: rgba(10, 18, 33, 0.20);
                backdrop-filter: blur(10px);*/
                color: rgba(255, 255, 255, 0.72);
                font-size: 0.7rem;
                text-align: center;
            }

            /* En táctil el motor agrega stick/botones abajo (voxel-plaza-engine.js)
               — subir este aviso para que no quede debajo de esos controles. */
            @media (hover: none) and (pointer: coarse) {
                .voxel-lab-status {
                    bottom: 10px;
                }
            }

            /* Aviso de "clic para mirar alrededor": sin esto, nada indica
               que un solo clic activa el mouse capturado (pointer lock,
               ver `bindInput()`) — sin esa captura, mover la cámara solo
               funciona arrastrando con el clic sostenido (modo de
               respaldo), que se siente como si "hubiera que mantener el
               clic apretado" para poder mirar alrededor. Un clic en
               cualquier parte de la escena ya activa la captura
               (`pointerLockTarget.addEventListener('click', ...)` en el
               motor); este botón solo hace ese primer clic obvio. Se
               oculta solo (transición) en cuanto la captura queda activa
               — `#generic-immersive-scene.is-locked` ya lo marca el motor. */
            #generic-lock-trigger {
                position: absolute;
                top: 74px;
                left: 50%;
                z-index: 15;
                transform: translateX(-50%);
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                background: rgba(10, 18, 33, 0.68);
                backdrop-filter: blur(10px);
                color: #fff;
                font-family: inherit;
                font-size: 0.8rem;
                cursor: pointer;
                transition: opacity 0.3s ease;
            }

            #generic-immersive-scene.is-locked ~ #generic-lock-trigger {
                opacity: 0;
                pointer-events: none;
            }

            /* En táctil no hay pointer lock que activar — mirar alrededor
               ya funciona arrastrando el dedo (ver `onTouchLookMove` en el
               motor), así que el aviso no aplica ahí. */
            @media (hover: none) and (pointer: coarse) {
                #generic-lock-trigger {
                    display: none;
                }
            }

            /* Preloader: HTML/CSS estático, visible desde el primer pintado
               sin esperar a que Three.js (CDN) ni el resto del módulo
               carguen — `immersive-preloader.js` solo le agrega la
               rotación de textos y lo oculta cuando la escena avisa que
               está lista. */
            .voxel-preloader {
                position: absolute;
                inset: 0;
                z-index: 30;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 18px;
                background: radial-gradient(circle at top, rgba(215, 106, 61, 0.22), transparent 32%), #07111f;
                transition: opacity 0.5s ease;
            }

            .voxel-preloader.is-hidden {
                opacity: 0;
                pointer-events: none;
            }

            .voxel-preloader__cube {
                width: 46px;
                height: 46px;
                background: linear-gradient(135deg, #e3342f, #d76a3d);
                border-radius: 8px;
                animation: voxel-preloader-spin 1.1s ease-in-out infinite;
            }

            @keyframes voxel-preloader-spin {
                0%, 100% {
                    transform: rotate(0deg) scale(1);
                }
                50% {
                    transform: rotate(180deg) scale(0.82);
                }
            }

            .voxel-preloader__text {
                min-height: 1.4em;
                margin: 0;
                padding: 0 20px;
                font-size: 0.95rem;
                letter-spacing: 0.02em;
                color: rgba(255, 255, 255, 0.86);
                text-align: center;
            }

            @media (prefers-reduced-motion: reduce) {
                .voxel-preloader__cube {
                    animation: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="voxel-lab-shell">
            <header class="voxel-lab-header">
                <a href="{{ route('home') }}" class="voxel-lab-header-logo" aria-label="{{ __('Ir al inicio de Merkamigo') }}" title="{{ __('Merkamigo') }}">
                    <x-app-logo-icon />
                </a>

                {{-- `stand-search-panel.js` (buscador/filtros) y
                     `display-settings-panel.js` (calidad gráfica) insertan
                     aquí sus botones — así viven en el mismo header en vez
                     de flotar sueltos por la pantalla. --}}
                <div class="voxel-lab-header-actions" id="generic-header-actions"></div>

                @auth
                    <flux:dropdown position="bottom" align="end" class="voxel-lab-header-account">
                        <flux:profile
                            :avatar="auth()->user()->avatarUrl()"
                            :initials="auth()->user()->initials()"
                            circle
                            :chevron="false"
                        />
                        <flux:menu>
                            <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Mi cuenta') }}</flux:menu.item>
                            <flux:menu.item :href="route('clientes.favoritos')" icon="heart" wire:navigate>{{ __('Favoritos') }}</flux:menu.item>
                            <flux:menu.separator />
                            <x-experience-switch-menu />
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Cerrar sesión') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @endauth
            </header>

            <div id="generic-immersive-scene" aria-label="Escena inmersiva de {{ $plaza->name }}"></div>
            <button type="button" id="generic-lock-trigger">🖱️ {{ __('Haz clic para mirar alrededor') }}</button>
            <div class="voxel-lab-status">With ♥️ by <a href="https://inggen.com" target="_blank">inggen.com</a></div>
            <div class="voxel-preloader" id="voxel-preloader" role="status" aria-live="polite">
                <span class="voxel-preloader__cube" aria-hidden="true"></span>
                <p class="voxel-preloader__text" id="voxel-preloader-text">{{ __('Cargando la plaza...') }}</p>
            </div>
        </div>

        <script>
            const genericBounds = @json($plaza->navigable_bounds);
            window.genericPlazaId = @json($plaza->id);
            window.genericMunicipalitySlug = @json($municipio->slug);
            window.genericPlazaBounds = genericBounds;
            window.genericPlazaPlane = {
                centerX: ((genericBounds?.minX ?? -50) + (genericBounds?.maxX ?? 50)) / 2,
                centerZ: ((genericBounds?.minZ ?? -50) + (genericBounds?.maxZ ?? 50)) / 2,
                width: Math.max(1, @json($plaza->reference_image_width) ?? ((genericBounds?.maxX ?? 50) - (genericBounds?.minX ?? -50))),
                depth: Math.max(1, @json($plaza->reference_image_height) ?? ((genericBounds?.maxZ ?? 50) - (genericBounds?.minZ ?? -50))),
            };
            window.genericPlazaSpawn = @json($plaza->spawn_point);
            // IMM-040: calidad adaptativa — perfil configurado por el admin
            // para esta plaza (Filament > Plazas > Calidad móvil/escritorio).
            window.genericPlazaQualityProfile = {
                mobile: @json($plaza->mobile_quality_profile),
                desktop: @json($plaza->desktop_quality_profile),
            };
            window.genericPlazaReferenceImageUrl = @json($plaza->reference_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($plaza->reference_image_path) : null);
        </script>
        {{-- `?v=` con la fecha de modificación evita que el navegador siga
             sirviendo una copia en caché de este script (y de los módulos
             que importa) después de un cambio — estos archivos viven en
             `public/js`, fuera del pipeline de Vite, así que no reciben
             el hash automático de los assets compilados. --}}
        <script type="module" src="{{ asset('js/generic-plaza-immersive.js') }}?v={{ filemtime(public_path('js/generic-plaza-immersive.js')) }}"></script>
    </body>
</html>
