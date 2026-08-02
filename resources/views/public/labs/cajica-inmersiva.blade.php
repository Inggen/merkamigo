<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => 'Demo inmersiva Cajicá',
            'description' => 'Prueba temporal de una plaza voxel estilo Minecraft para Merkamigo, inspirada en el parque principal de Cajicá.',
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

            #cajica-immersive-scene {
                width: 100%;
                height: 100%;
                cursor: grab;
            }

            #cajica-immersive-scene.is-locked {
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
            }

            .voxel-lab-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
            }

            .voxel-lab-brand-copy {
                flex: 1;
                min-width: 0;
            }

            .voxel-lab-brand svg {
                width: 36px;
                height: 36px;
                color: #d7352a;
            }

            .voxel-lab-brand strong {
                font-size: 1.1rem;
                font-weight: 700;
            }

            .voxel-lab-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.06);
                color: #fff;
                cursor: pointer;
                transition: background 160ms ease, transform 160ms ease, border-color 160ms ease;
            }

            .voxel-lab-toggle:hover {
                background: rgba(255, 255, 255, 0.12);
                transform: translateY(-1px);
            }

            .voxel-lab-toggle svg {
                width: 18px;
                height: 18px;
                color: currentColor;
            }

            .voxel-lab-panel {
                padding: 20px 20px 18px;
                transform-origin: top left;
                transition: opacity 180ms ease, transform 180ms ease, max-height 220ms ease, margin 220ms ease, padding 220ms ease;
                max-height: 900px;
                overflow: hidden;
            }

            .voxel-lab-panel.is-collapsed {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
                pointer-events: none;
                max-height: 0;
                margin-top: -6px;
                padding-top: 0;
                padding-bottom: 0;
            }

            .voxel-lab-panel h1 {
                margin: 0 0 8px;
                font-size: clamp(1.8rem, 3vw, 2.6rem);
                line-height: 1;
            }

            .voxel-lab-panel p {
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

                .voxel-lab-panel {
                    padding: 16px;
                }

                .voxel-lab-status {
                    left: 14px;
                    right: 14px;
                    transform: none;
                    border-radius: 18px;
                }
            }
        </style>
    </head>
    <body>
        <div class="voxel-lab-shell">
            <div class="voxel-lab-hud">
                <div class="voxel-lab-card voxel-lab-brand">
                    <x-app-logo-icon />
                    <div class="voxel-lab-brand-copy">
                        <strong>Merkamigo Labs</strong>
                        <div style="font-size: .88rem; color: rgba(255,255,255,.68);">Demo temporal inmersiva</div>
                    </div>
                    <button
                        id="cajica-panel-toggle"
                        type="button"
                        class="voxel-lab-toggle"
                        aria-controls="cajica-demo-panel"
                        aria-expanded="true"
                        aria-label="Ocultar instrucciones"
                        title="Mostrar u ocultar instrucciones"
                    >
                        <svg id="cajica-panel-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>

                <div id="cajica-demo-panel" class="voxel-lab-card voxel-lab-panel">
                    <h1>Parque principal de Cajicá</h1>
                    <p>Recorre una versión tipo Minecraft inspirada en el parque principal, su iglesia y sus jardines. Haz clic en la escena para capturar el mouse y mira alrededor libremente.</p>

                    <div class="voxel-lab-controls">
                        <span><span class="voxel-lab-key">↑ ↓ ← →</span> Mover al personaje por el parque</span>
                        <span><span class="voxel-lab-key">Espacio</span> Salto suave con gravedad</span>
                        <span><span class="voxel-lab-key">Mouse</span> Girar la cámara perseguidora</span>
                        <span><span class="voxel-lab-key">Esc</span> Liberar el puntero</span>
                    </div>

                    <div class="voxel-lab-actions">
                        <a href="{{ route('buscar', ['municipio' => 'cajica']) }}" class="voxel-lab-button voxel-lab-button--ghost">Volver a la plaza web</a>
                        <button id="cajica-lock-trigger" type="button" class="voxel-lab-button voxel-lab-button--primary">Entrar en modo inmersivo</button>
                    </div>
                </div>
            </div>

            <div id="cajica-immersive-scene" aria-label="Escena inmersiva del parque principal de Cajicá"></div>
            <div class="voxel-lab-status">Prueba temporal: referencia voxel del parque. No representa la escala exacta del sitio real.</div>
        </div>

        <script>
            (() => {
                const toggle = document.getElementById('cajica-panel-toggle');
                const panel = document.getElementById('cajica-demo-panel');
                const icon = document.getElementById('cajica-panel-toggle-icon');

                if (!toggle || !panel || !icon) {
                    return;
                }

                const setExpanded = (expanded) => {
                    panel.classList.toggle('is-collapsed', !expanded);
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    toggle.setAttribute('aria-label', expanded ? 'Ocultar instrucciones' : 'Mostrar instrucciones');
                    icon.style.transform = expanded ? 'rotate(0deg)' : 'rotate(180deg)';
                };

                toggle.addEventListener('click', () => {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    setExpanded(!expanded);
                });

                setExpanded(true);
            })();
        </script>
        <script type="module" src="{{ asset('js/cajica-plaza-immersive.js') }}"></script>
    </body>
</html>
