<div x-data="{ openJson: true }">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-4">
            <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-medium text-gray-950 dark:text-white">Fotos de referencia</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Sube 3 fotos del objeto físico: frontal, lateral y superior. La IA las usa para generar la geometría voxel.
                </p>

                <div class="mt-3 grid grid-cols-3 gap-3">
                    <x-voxel-ai-image-dropzone
                        model="frontImage"
                        label="Frontal"
                        :file="$frontImage"
                        :stored-url="$this->referenceImageUrl('front')"
                    />
                    <x-voxel-ai-image-dropzone
                        model="sideImage"
                        label="Lateral"
                        :file="$sideImage"
                        :stored-url="$this->referenceImageUrl('side')"
                    />
                    <x-voxel-ai-image-dropzone
                        model="topImage"
                        label="Superior"
                        :file="$topImage"
                        :stored-url="$this->referenceImageUrl('top')"
                    />
                </div>

                @if ($template->thumbnail_path)
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        La miniatura del catálogo también se envía como referencia adicional a la IA.
                    </p>
                @endif
            </div>

            <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-medium text-gray-950 dark:text-white">Configuración del recurso</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Estos campos se guardan automáticamente al salir de cada input.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="text-xs text-gray-500 dark:text-gray-400">
                        Ancho (m)
                        <input wire:model.blur="maxWidth" type="number" step="0.001" min="0.001" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" />
                    </label>
                    <label class="text-xs text-gray-500 dark:text-gray-400">
                        Profundidad (m)
                        <input wire:model.blur="maxDepth" type="number" step="0.001" min="0.001" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" />
                    </label>
                    <label class="text-xs text-gray-500 dark:text-gray-400">
                        Alto (m)
                        <input wire:model.blur="maxHeight" type="number" step="0.001" min="0.001" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" />
                    </label>
                    <label class="text-xs text-gray-500 dark:text-gray-400">
                        Cantidad máxima de bloques
                        <input wire:model.blur="maxBoxes" type="number" step="1" min="1" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" />
                    </label>
                </div>

                <div class="mt-4">
                    <label class="text-xs text-gray-500 dark:text-gray-400">
                        Colores permitidos
                        <input
                            type="text"
                            wire:model.blur="allowedColorsText"
                            class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800"
                            placeholder="#d7352a, #ffffff, #1f2937"
                        />
                    </label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Escribe los colores separados por coma.
                    </p>
                </div>
            </div>

            <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <label class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ $currentDefinition ? 'Instrucciones de refinamiento' : 'Instrucciones' }}
                </label>
                <textarea
                    wire:model="instructions"
                    rows="3"
                    class="fi-input mt-2 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800"
                    placeholder="Ej: caseta de madera con techo a dos aguas, letrero rojo al frente..."
                ></textarea>

                <div class="mt-3 flex gap-2">
                    <x-filament::button wire:click="generate" wire:loading.attr="disabled" wire:target="generate" color="gray">
                        <span wire:loading.remove wire:target="generate">{{ $currentDefinition ? 'Refinar' : 'Generar' }}</span>
                        <span wire:loading wire:target="generate">Generando…</span>
                    </x-filament::button>

                    <x-filament::button wire:click="save" wire:loading.attr="disabled" wire:target="save" color="primary">
                        Guardar
                    </x-filament::button>

                    @if ($canUndoLastRefinement)
                        <x-filament::button wire:click="undoLastRefinement" wire:loading.attr="disabled" wire:target="undoLastRefinement" color="gray" outlined>
                            Deshacer último refinamiento
                        </x-filament::button>
                    @endif
                </div>

                @if ($this->hasUnpublishedChanges())
                    <p class="mt-3 rounded-lg bg-amber-50 p-2 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                        Tienes un refinamiento sin guardar: la previsualización ya lo muestra, pero la plaza sigue usando la versión anterior hasta que hagas clic en "Guardar".
                    </p>
                @endif
            </div>

            <div class="fi-section max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-medium text-gray-950 dark:text-white">Bitácora</h3>

                @if ($instructionLog !== [])
                    <ul class="mt-2 space-y-1 text-xs">
                        @foreach (array_reverse($instructionLog) as $entry)
                            <li class="{{ $entry['role'] === 'sistema' ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100' }}">
                                <span class="font-mono text-gray-400">{{ $entry['at'] }}</span>
                                — {{ $entry['text'] }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-xs text-gray-400">Todavía no hay eventos registrados para este modelo.</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div
                class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900"
                wire:ignore
                x-data="{}"
                x-init="
                    import('{{ asset('js/lib/voxel-plaza-engine.js') }}').then(({ THREE, createStandaloneVoxelTarget, buildFromDefinition, createAxisLabels }) => {
                        const container = document.getElementById(@js($previewDomId));

                        if (! container || container.dataset.initialized === 'true') {
                            return;
                        }

                        container.dataset.initialized = 'true';

                        const scene = new THREE.Scene();
                        scene.background = new THREE.Color(0xcfe8ff);

                        const width = Math.max(container.clientWidth, 320);
                        const height = Math.max(container.clientHeight, 320);
                        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
                        camera.position.set(6, 5, 6);
                        camera.lookAt(0, 1, 0);

                        const renderer = new THREE.WebGLRenderer({ antialias: true });
                        renderer.setSize(width, height);
                        renderer.shadowMap.enabled = true;
                        container.appendChild(renderer.domElement);

                        scene.add(new THREE.AmbientLight(0xffffff, 0.7));
                        const sun = new THREE.DirectionalLight(0xffffff, 0.8);
                        sun.position.set(5, 10, 5);
                        scene.add(sun);

                        const target = createStandaloneVoxelTarget();
                        scene.add(target.world);

                        // Solo para esta previsualización: referencia de
                        // cuadrícula y ejes X/Y/Z. La experiencia inmersiva
                        // real (VoxelPlazaEngine) nunca debe llevar estos
                        // helpers — son ayuda de edición, no parte de la
                        // escena jugable.
                        const gridHelper = new THREE.GridHelper(40, 40, 0x64748b, 0xcbd5e1);
                        gridHelper.position.y = 0.02;
                        scene.add(gridHelper);
                        scene.add(new THREE.AxesHelper(4));
                        scene.add(createAxisLabels(4));

                        let ground = null;
                        let objectGroup = null;
                        let orbitRadius = 8;
                        let lookAtHeight = 1;
                        let zoomFactor = 1;

                        container.addEventListener('wheel', (event) => {
                            event.preventDefault();
                            const step = event.deltaY > 0 ? 1.1 : 1 / 1.1;
                            zoomFactor = Math.min(3, Math.max(0.25, zoomFactor * step));
                        }, { passive: false });

                        function boundingRadius(definition) {
                            const boxes = definition?.boxes ?? [];

                            if (boxes.length === 0) {
                                return { radius: 4, height: 1, maxHeight: 2 };
                            }

                            let maxHorizontal = 0;
                            let maxHeight = 0;

                            boxes.forEach((box) => {
                                const horizontal = Math.hypot(box.x ?? 0, box.z ?? 0) + Math.hypot(box.w ?? 0, box.d ?? 0) / 2;
                                const top = (box.y ?? 0) + (box.h ?? 0) / 2;
                                maxHorizontal = Math.max(maxHorizontal, horizontal);
                                maxHeight = Math.max(maxHeight, top);
                            });

                            return {
                                radius: Math.max(4, maxHorizontal * 2.2),
                                height: Math.max(0.5, maxHeight * 0.5),
                                maxHeight,
                            };
                        }

                        function renderDefinition(definition) {
                            if (objectGroup) {
                                target.world.remove(objectGroup);
                            }

                            if (ground) {
                                target.world.remove(ground);
                            }

                            objectGroup = definition
                                ? buildFromDefinition(target, { x: 0, z: 0, rotation: 0, definition })
                                : null;

                            const bounds = boundingRadius(definition);
                            orbitRadius = bounds.radius;
                            lookAtHeight = bounds.height;

                            const groundSize = Math.max(20, bounds.radius * 3);
                            ground = target.addVoxelBox({ x: 0, y: -0.1, z: 0, w: groundSize, h: 0.2, d: groundSize, texture: 'grass', castShadow: false });

                            $refs.boxCount.textContent = `${definition?.boxes?.length ?? 0} cubos`;
                        }

                        function resizeRenderer() {
                            const nextWidth = Math.max(container.clientWidth, 320);
                            const nextHeight = Math.max(container.clientHeight, 320);
                            camera.aspect = nextWidth / nextHeight;
                            camera.updateProjectionMatrix();
                            renderer.setSize(nextWidth, nextHeight);
                        }

                        renderDefinition(@js($currentDefinition));
                        resizeRenderer();

                        window.addEventListener(@js($previewEventName), (event) => {
                            renderDefinition(event.detail.definition ?? null);
                        });

                        new ResizeObserver(() => resizeRenderer()).observe(container);

                        let angle = 0;

                        function animate() {
                            requestAnimationFrame(animate);
                            angle += 0.005;
                            const effectiveRadius = orbitRadius * zoomFactor;
                            camera.position.x = Math.cos(angle) * effectiveRadius;
                            camera.position.z = Math.sin(angle) * effectiveRadius;
                            camera.position.y = Math.max(2, lookAtHeight * 1.6) * zoomFactor;
                            camera.lookAt(0, lookAtHeight, 0);
                            renderer.render(scene, camera);
                        }

                        animate();
                    })
                "
            >
                <div class="mb-2 flex items-center gap-2">
                    <h3 class="text-sm font-medium text-gray-950 dark:text-white">Previsualización</h3>
                    <span x-ref="boxCount" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ count($currentDefinition['boxes'] ?? []) }} cubos</span>
                </div>
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Scroll para acercar/alejar la cámara.</p>

                @if ($template->model_path)
                    <p class="mb-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                        Este objeto ya tiene un modelo GLB asignado. En la escena se seguirá usando el GLB por encima de esta definición voxel.
                    </p>
                @endif

                <div id="{{ $previewDomId }}" style="width: 100%; height: 480px; border-radius: 0.5rem; overflow: hidden; background: #cfe8ff;"></div>
            </div>

            <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 text-left"
                    x-on:click="openJson = ! openJson"
                >
                    <div>
                        <h3 class="text-sm font-medium text-gray-950 dark:text-white">JSON de la definición actual</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Esto es exactamente lo que generó la IA o el último borrador guardado en esta modal.
                        </p>
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-gray-400 transition-transform" :class="openJson ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-cloak x-show="openJson" x-transition>
                    @if ($currentDefinition)
                        <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-gray-950 p-3 text-xs text-gray-100">{{ json_encode($currentDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <p class="mt-4 text-xs text-gray-400">Todavía no hay ninguna definición generada.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
