<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => 'Demo inmersiva Zipaquira',
            'description' => 'Prueba temporal de una plaza voxel estilo Minecraft para Merkamigo.',
        ])
        <style>
            body {
                margin: 0;
                overflow: hidden;
                background: #07111f;
                color: #fff;
                font-family: var(--font-sans, 'Instrument Sans', sans-serif);
            }

            .zipa-demo-shell {
                position: relative;
                width: 100vw;
                height: 100vh;
                background: radial-gradient(circle at top, rgba(33, 98, 196, 0.22), transparent 32%), #07111f;
            }

            #zipa-immersive-scene {
                width: 100%;
                height: 100%;
                cursor: grab;
            }

            #zipa-immersive-scene.is-locked {
                cursor: none;
            }

            .zipa-demo-hud {
                position: absolute;
                top: 24px;
                left: 24px;
                z-index: 20;
                display: grid;
                gap: 14px;
                max-width: min(420px, calc(100vw - 48px));
            }

            .zipa-demo-card {
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(10, 18, 33, 0.72);
                backdrop-filter: blur(14px);
                border-radius: 22px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
            }

            .zipa-demo-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
            }

            .zipa-demo-brand-copy {
                flex: 0 1 auto;
                min-width: 0;
            }

            .zipa-demo-brand-meta {
                margin-left: auto;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .zipa-demo-brand svg {
                width: 36px;
                height: 36px;
                color: #d7352a;
            }

            .zipa-demo-brand strong {
                font-size: 1.1rem;
                font-weight: 700;
            }

            .zipa-demo-toggle {
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

            .zipa-demo-toggle:hover {
                background: rgba(255, 255, 255, 0.12);
                transform: translateY(-1px);
            }

            .zipa-demo-toggle svg {
                width: 18px;
                height: 18px;
                color: currentColor;
            }

            .zipa-demo-panel {
                padding: 20px 20px 18px;
                transform-origin: top left;
                transition: opacity 180ms ease, transform 180ms ease, max-height 220ms ease, margin 220ms ease, padding 220ms ease;
                max-height: 900px;
                overflow: hidden;
            }

            .zipa-demo-panel.is-collapsed {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
                pointer-events: none;
                max-height: 0;
                margin-top: -6px;
                padding-top: 0;
                padding-bottom: 0;
            }

            .zipa-demo-panel h1 {
                margin: 0 0 8px;
                font-size: clamp(1.8rem, 3vw, 2.6rem);
                line-height: 1;
            }

            .zipa-demo-panel p {
                margin: 0;
                color: rgba(255, 255, 255, 0.82);
                line-height: 1.5;
            }

            .zipa-demo-controls {
                display: grid;
                gap: 10px;
                margin-top: 16px;
                color: rgba(255, 255, 255, 0.88);
                font-size: 0.95rem;
            }

            .zipa-demo-controls span {
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }

            .zipa-demo-key {
                min-width: 34px;
                padding: 6px 10px;
                border-radius: 10px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                background: rgba(255, 255, 255, 0.06);
                text-align: center;
                font-size: 0.84rem;
                font-weight: 700;
            }

            .zipa-demo-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 18px;
            }

            .zipa-demo-coordinates {
                padding: 10px 12px;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.04);
            }

            .zipa-demo-coordinates-label {
                display: block;
                margin-bottom: 4px;
                font-size: 0.72rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.64);
            }

            .zipa-demo-coordinates-value {
                font-family: var(--font-mono, 'JetBrains Mono', 'SFMono-Regular', monospace);
                font-size: 0.82rem;
                color: rgba(255, 255, 255, 0.92);
                white-space: nowrap;
            }

            .zipa-demo-loader {
                position: absolute;
                inset: 0;
                z-index: 40;
                display: flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at top, rgba(33, 98, 196, 0.18), transparent 34%), rgba(7, 17, 31, 0.88);
                backdrop-filter: blur(10px);
                transition: opacity 220ms ease, visibility 220ms ease;
            }

            .zipa-demo-loader.is-hidden {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }

            .zipa-demo-loader-card {
                display: grid;
                justify-items: center;
                gap: 12px;
                padding: 24px 28px;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 22px;
                background: rgba(10, 18, 33, 0.78);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
                text-align: center;
            }

            .zipa-demo-loader-spinner {
                width: 42px;
                height: 42px;
                border: 3px solid rgba(255, 255, 255, 0.14);
                border-top-color: #d7352a;
                border-radius: 999px;
                animation: zipa-spin 0.9s linear infinite;
            }

            .zipa-demo-loader-title {
                font-size: 1rem;
                font-weight: 700;
                color: #fff;
            }

            .zipa-demo-loader-copy {
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.72);
            }

            @keyframes zipa-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .zipa-demo-button {
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

            .zipa-demo-button:hover {
                transform: translateY(-1px);
            }

            .zipa-demo-button--primary {
                background: #d7352a;
                color: #fff;
            }

            .zipa-demo-button--ghost {
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.04);
                color: #fff;
            }

            .zipa-demo-status {
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
                .zipa-demo-hud {
                    top: 14px;
                    left: 14px;
                    max-width: calc(100vw - 28px);
                }

                .zipa-demo-panel {
                    padding: 16px;
                }

                .zipa-demo-brand {
                    align-items: flex-start;
                }

                .zipa-demo-brand-meta {
                    flex-direction: column;
                    align-items: stretch;
                }

                .zipa-demo-status {
                    left: 14px;
                    right: 14px;
                    transform: none;
                    border-radius: 18px;
                }
            }

            /* En táctil el motor agrega stick/botones abajo (voxel-plaza-engine.js)
               — subir este aviso para que no quede debajo de esos controles. */
            @media (hover: none) and (pointer: coarse) {
                .zipa-demo-status {
                    bottom: 150px;
                }
            }
        </style>
    </head>
    <body>
        <div class="zipa-demo-shell">
            <div class="zipa-demo-hud">
                <div class="zipa-demo-card zipa-demo-brand">
                    <x-app-logo-icon />
                    <div class="zipa-demo-brand-copy">
                        <strong>Merkamigo Labs</strong>
                        <div style="font-size: .88rem; color: rgba(255,255,255,.68);">Demo temporal inmersiva</div>
                    </div>
                    <div class="zipa-demo-brand-meta">
                        <div class="zipa-demo-coordinates">
                            <span class="zipa-demo-coordinates-label">Posición</span>
                            <div id="zipa-player-coordinates" class="zipa-demo-coordinates-value">X: 0.00 · Y: 0.00 · Z: 0.00</div>
                        </div>

                        <button
                            id="zipa-panel-toggle"
                            type="button"
                            class="zipa-demo-toggle"
                            aria-controls="zipa-demo-panel"
                            aria-expanded="false"
                            aria-label="Mostrar instrucciones"
                            title="Mostrar u ocultar instrucciones"
                        >
                            <svg id="zipa-panel-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="zipa-demo-panel" class="zipa-demo-card zipa-demo-panel is-collapsed">
                    <h1>Plaza voxel de Zipaquirá</h1>
                    <p>Recorre una versión tipo Minecraft inspirada en la plaza principal. Haz clic en la escena para capturar el mouse y mira alrededor libremente.</p>

                    <div class="zipa-demo-controls">
                        <span><span class="zipa-demo-key">↑ ↓ ← →</span> Mover al personaje por la plaza</span>
                        <span><span class="zipa-demo-key">Espacio</span> Salto suave con gravedad</span>
                        <span><span class="zipa-demo-key">Mouse</span> Girar la cámara perseguidora</span>
                        <span><span class="zipa-demo-key">Esc</span> Liberar el puntero</span>
                    </div>

                    <div class="zipa-demo-actions">
                        <a href="{{ route('buscar', ['municipio' => 'zipaquira']) }}" class="zipa-demo-button zipa-demo-button--ghost">Volver a la plaza web</a>
                        <button id="zipa-lock-trigger" type="button" class="zipa-demo-button zipa-demo-button--primary">Entrar en modo inmersivo</button>
                    </div>
                </div>
            </div>

            <div id="zipa-immersive-scene" aria-label="Escena inmersiva de la plaza de Zipaquirá"></div>
            <div id="zipa-loading-overlay" class="zipa-demo-loader" aria-live="polite">
                <div class="zipa-demo-loader-card">
                    <div class="zipa-demo-loader-spinner" aria-hidden="true"></div>
                    <div class="zipa-demo-loader-title">Cargando experiencia inmersiva</div>
                    <div class="zipa-demo-loader-copy">Preparando escenario, modelos y colisiones...</div>
                </div>
            </div>
            <div class="zipa-demo-status">Prueba temporal: referencia voxel de la plaza. No representa la escala exacta del sitio real.</div>
        </div>

        <script>
            window.zipaImmersiveBusinesses = @json($immersiveBusinesses ?? []);
            window.zipaImmersivePlazaId = @json($immersivePlazaId ?? null);

            (() => {
                const toggle = document.getElementById('zipa-panel-toggle');
                const panel = document.getElementById('zipa-demo-panel');
                const icon = document.getElementById('zipa-panel-toggle-icon');

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

                setExpanded(false);
            })();
        </script>
        <script type="module" src="{{ asset('js/zipa-plaza-immersive.js') }}"></script>
    </body>
</html>
