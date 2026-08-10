<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => "Plaza inmersiva — {$plaza->name}",
            'description' => "Escena inmersiva de {$municipio->name} armada desde los datos de la plaza, sin escena escrita a mano.",
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

            #generic-immersive-scene {
                width: 100%;
                height: 100%;
                cursor: grab;
            }

            #generic-immersive-scene.is-locked {
                cursor: none;
            }

            .voxel-lab-hud {
                position: absolute;
                top: 24px;
                left: 24px;
                z-index: 20;
                display: grid;
                gap: 14px;
                max-width: min(420px, calc(100vw - 48px));
            }

            .voxel-lab-card {
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(10, 18, 33, 0.72);
                backdrop-filter: blur(14px);
                border-radius: 22px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
                padding: 20px 20px 18px;
            }

            .voxel-lab-card h1 {
                margin: 0 0 8px;
                font-size: clamp(1.6rem, 3vw, 2.2rem);
                line-height: 1.1;
            }

            .voxel-lab-card p {
                margin: 0;
                color: rgba(255, 255, 255, 0.82);
                line-height: 1.5;
            }

            .voxel-lab-controls {
                display: grid;
                gap: 10px;
                margin-top: 16px;
                color: rgba(255, 255, 255, 0.88);
                font-size: 0.95rem;
            }

            .voxel-lab-controls span {
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }

            .voxel-lab-key {
                min-width: 34px;
                padding: 6px 10px;
                border-radius: 10px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                background: rgba(255, 255, 255, 0.06);
                text-align: center;
                font-size: 0.84rem;
                font-weight: 700;
            }

            .voxel-lab-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 18px;
            }

            .voxel-lab-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                border-radius: 14px;
                padding: 12px 16px;
                font-weight: 700;
                text-decoration: none;
                transition: transform 160ms ease, background 160ms ease, border-color 160ms ease;
            }

            .voxel-lab-button:hover {
                transform: translateY(-1px);
            }

            .voxel-lab-button--primary {
                background: #d7352a;
                color: #fff;
            }

            .voxel-lab-button--ghost {
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.04);
                color: #fff;
            }

            .voxel-lab-status {
                position: absolute;
                bottom: 20px;
                left: 50%;
                z-index: 20;
                transform: translateX(-50%);
                padding: 10px 14px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                background: rgba(10, 18, 33, 0.68);
                backdrop-filter: blur(10px);
                color: rgba(255, 255, 255, 0.88);
                font-size: 0.92rem;
                text-align: center;
            }

            @media (max-width: 768px) {
                .voxel-lab-hud {
                    top: 14px;
                    left: 14px;
                    max-width: calc(100vw - 28px);
                }

                .voxel-lab-status {
                    left: 14px;
                    right: 14px;
                    transform: none;
                    border-radius: 18px;
                }
            }

            /* En táctil el motor agrega stick/botones abajo (voxel-plaza-engine.js)
               — subir este aviso para que no quede debajo de esos controles. */
            @media (hover: none) and (pointer: coarse) {
                .voxel-lab-status {
                    bottom: 150px;
                }
            }
        </style>
    </head>
    <body>
        <div class="voxel-lab-shell">
            <div class="voxel-lab-hud" style="display:none;">
                <div class="voxel-lab-card">
                    <h1>{{ $plaza->name }}</h1>
                    <p>Escena armada desde los datos de la plaza — sin código escrito a mano para {{ $municipio->name }}. Haz clic para capturar el mouse y mira alrededor libremente.</p>

                    <div class="voxel-lab-controls">
                        <span><span class="voxel-lab-key">↑ ↓ ← →</span> Mover al personaje</span>
                        <span><span class="voxel-lab-key">Espacio</span> Salto suave con gravedad</span>
                        <span><span class="voxel-lab-key">Mouse</span> Girar la cámara perseguidora</span>
                        <span><span class="voxel-lab-key">Esc</span> Liberar el puntero</span>
                    </div>

                    <div class="voxel-lab-actions">
                        <a href="{{ route('buscar', ['municipio' => $municipio->slug]) }}" class="voxel-lab-button voxel-lab-button--ghost">Volver a la plaza web</a>
                        <button id="generic-lock-trigger" type="button" class="voxel-lab-button voxel-lab-button--primary">Entrar en modo inmersivo</button>
                    </div>
                </div>
            </div>

            <div id="generic-immersive-scene" aria-label="Escena inmersiva de {{ $plaza->name }}"></div>
            <div class="voxel-lab-status">With ♥️ by <a href="https://inggen.com" target="_blank">inggen.com</a></div>
        </div>

        <script>
            const genericBounds = @json($plaza->navigable_bounds);
            window.genericPlazaId = @json($plaza->id);
            window.genericPlazaBounds = genericBounds;
            window.genericPlazaPlane = {
                centerX: ((genericBounds?.minX ?? -50) + (genericBounds?.maxX ?? 50)) / 2,
                centerZ: ((genericBounds?.minZ ?? -50) + (genericBounds?.maxZ ?? 50)) / 2,
                width: Math.max(1, @json($plaza->reference_image_width) ?? ((genericBounds?.maxX ?? 50) - (genericBounds?.minX ?? -50))),
                depth: Math.max(1, @json($plaza->reference_image_height) ?? ((genericBounds?.maxZ ?? 50) - (genericBounds?.minZ ?? -50))),
            };
            window.genericPlazaSpawn = @json($plaza->spawn_point);
            window.genericPlazaReferenceImageUrl = @json($plaza->reference_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($plaza->reference_image_path) : null);
        </script>
        <script type="module" src="{{ asset('js/generic-plaza-immersive.js') }}"></script>
    </body>
</html>
