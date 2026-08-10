<div class="grid gap-4 xl:grid-cols-[14rem_minmax(0,1fr)_16rem]">
    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Opciones</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Administra las cajas que componen este objeto 3D.
        </p>

        <div class="mt-4">
            <x-filament::button wire:click="addBox" icon="heroicon-m-plus" size="sm">
                Agregar caja
            </x-filament::button>
        </div>

        <div class="mt-5 space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cajas del objeto</h4>

            <div class="max-h-72 space-y-2 overflow-auto pr-1">
                @foreach ($sceneData['boxes'] ?? [] as $box)
                    <button
                        type="button"
                        wire:click="selectBox({{ $box['index'] }})"
                        @class([
                            'w-full rounded-lg border px-3 py-2 text-left text-sm transition',
                            'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $selectedBoxIndex === $box['index'],
                            'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200' => $selectedBoxIndex !== $box['index'],
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $box['label'] }}</div>
                                <div class="mt-1 text-xs opacity-70">
                                    X {{ round($box['position']['x'], 2) }} · Z {{ round($box['position']['z'], 2) }}
                                </div>
                            </div>

                            <span class="text-xs uppercase tracking-wide opacity-60">
                                {{ $box['texture'] }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mt-5 space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tamaño máximo del objeto</h4>

            <div class="space-y-2">
                <label class="block text-xs text-gray-500 dark:text-gray-400">
                    Ancho máximo (m)
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="maxWidthForm" />
                    </x-filament::input.wrapper>
                </label>
                <label class="block text-xs text-gray-500 dark:text-gray-400">
                    Profundidad máxima (m)
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="maxDepthForm" />
                    </x-filament::input.wrapper>
                </label>
                <label class="block text-xs text-gray-500 dark:text-gray-400">
                    Alto máximo (m)
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="maxHeightForm" />
                    </x-filament::input.wrapper>
                </label>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">También se recalculan solos al mover o redimensionar cajas. El recuadro rojo del visor marca este límite.</p>
        </div>
    </div>

    <div class="fi-section min-w-0 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Editor de Objeto (3D)</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Scroll para acercar o alejar, clic derecho para rotar la cámara y arrastra una caja para moverla sobre X/Z.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    wire:click="undo"
                    @disabled(! $this->canUndo())
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 disabled:pointer-events-none disabled:opacity-30 dark:text-gray-400 dark:hover:text-primary-400"
                    title="Deshacer"
                >
                    <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="h-4 w-4" />
                </button>

                <button
                    type="button"
                    wire:click="redo"
                    @disabled(! $this->canRedo())
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 disabled:pointer-events-none disabled:opacity-30 dark:text-gray-400 dark:hover:text-primary-400"
                    title="Rehacer"
                >
                    <x-filament::icon icon="heroicon-m-arrow-uturn-right" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div
            wire:ignore
            x-data="{}"
            x-init="
                import('{{ asset('js/lib/voxel-plaza-engine.js') }}').then(async ({ THREE, createStandaloneVoxelTarget, createAxisLabels }) => {
                    const { OrbitControls } = await import('https://esm.sh/three@0.179.1/examples/jsm/controls/OrbitControls.js');

                    const container = document.getElementById(@js('object-template-editor-'.$template->id));
                    const sceneState = structuredClone(@js($sceneData));

                    if (! container || container.dataset.initialized === 'true') {
                        return;
                    }

                    container.dataset.initialized = 'true';

                    const scene = new THREE.Scene();
                    scene.background = new THREE.Color(0xcfe8ff);

                    const renderer = new THREE.WebGLRenderer({ antialias: true });
                    renderer.shadowMap.enabled = true;
                    container.appendChild(renderer.domElement);

                    const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 1000);
                    const controls = new OrbitControls(camera, renderer.domElement);
                    controls.enableDamping = true;

                    const target = createStandaloneVoxelTarget();
                    scene.add(target.world);

                    // Solo para este editor: referencia de cuadrícula y ejes
                    // X/Y/Z para ubicar cajas con precisión. La experiencia
                    // inmersiva real (VoxelPlazaEngine) nunca debe llevar
                    // estos helpers — son ayuda de edición, no parte de la
                    // escena jugable.
                    const gridHelper = new THREE.GridHelper(40, 40, 0x64748b, 0xcbd5e1);
                    gridHelper.position.y = 0.02;
                    scene.add(gridHelper);
                    scene.add(new THREE.AxesHelper(4));
                    scene.add(createAxisLabels(4));

                    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
                    const sun = new THREE.DirectionalLight(0xffffff, 0.9);
                    sun.position.set(8, 12, 8);
                    scene.add(sun);

                    const raycaster = new THREE.Raycaster();
                    const pointer = new THREE.Vector2();
                    const dragPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
                    const boxRoots = new Map();
                    let ground = null;
                    let selectionHelper = null;
                    let maxBoundsHelper = null;
                    let dragging = null;

                    function setPointer(event) {
                        const rect = renderer.domElement.getBoundingClientRect();
                        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
                    }

                    function boundsFromBoxes(boxes) {
                        if (! boxes.length) {
                            return { centerX: 0, centerZ: 0, width: 8, depth: 8, height: 4 };
                        }

                        let minX = Infinity;
                        let maxX = -Infinity;
                        let minZ = Infinity;
                        let maxZ = -Infinity;
                        let maxY = 0;

                        boxes.forEach((box) => {
                            minX = Math.min(minX, box.position.x - (box.size.x / 2));
                            maxX = Math.max(maxX, box.position.x + (box.size.x / 2));
                            minZ = Math.min(minZ, box.position.z - (box.size.z / 2));
                            maxZ = Math.max(maxZ, box.position.z + (box.size.z / 2));
                            maxY = Math.max(maxY, box.position.y + (box.size.y / 2));
                        });

                        return {
                            centerX: (minX + maxX) / 2,
                            centerZ: (minZ + maxZ) / 2,
                            width: Math.max(8, maxX - minX + 4),
                            depth: Math.max(8, maxZ - minZ + 4),
                            height: Math.max(4, maxY + 2),
                        };
                    }

                    function buildGround(bounds) {
                        if (ground) {
                            scene.remove(ground);
                            ground.geometry.dispose();
                        }

                        ground = target.addVoxelBox({
                            x: bounds.centerX,
                            y: -0.1,
                            z: bounds.centerZ,
                            w: bounds.width,
                            h: 0.2,
                            d: bounds.depth,
                            texture: 'grass',
                            castShadow: false,
                        });
                    }

                    function clearMeshes() {
                        boxRoots.forEach((mesh) => {
                            if (selectionHelper?.object === mesh) {
                                scene.remove(selectionHelper);
                                selectionHelper = null;
                            }

                            target.world.remove(mesh);
                        });

                        boxRoots.clear();
                    }

                    function highlight(mesh) {
                        if (selectionHelper) {
                            scene.remove(selectionHelper);
                        }

                        selectionHelper = new THREE.BoxHelper(mesh, 0xef4444);
                        scene.add(selectionHelper);
                    }

                    // Recuadro rojo del tamaño máximo reservado para el
                    // objeto (Ancho/Profundidad/Alto máximo del panel de
                    // Opciones) — huella centrada en el origen, apoyada en
                    // el suelo (y=0), igual que la convención de coordenadas
                    // de las cajas.
                    function drawMaxBoundsBox(maxSize) {
                        if (maxBoundsHelper) {
                            scene.remove(maxBoundsHelper);
                            maxBoundsHelper.geometry.dispose();
                            maxBoundsHelper.material.dispose();
                        }

                        const width = Math.max(0.01, maxSize?.width ?? 1);
                        const depth = Math.max(0.01, maxSize?.depth ?? 1);
                        const height = Math.max(0.01, maxSize?.height ?? 1);

                        const edges = new THREE.EdgesGeometry(new THREE.BoxGeometry(width, height, depth));
                        maxBoundsHelper = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xef4444 }));
                        maxBoundsHelper.position.set(0, height / 2, 0);
                        scene.add(maxBoundsHelper);
                    }

                    function renderDefinition(definition) {
                        clearMeshes();

                        const boxes = definition?.boxes ?? [];

                        boxes.forEach((box, index) => {
                            const mesh = target.addVoxelBox({
                                x: box.x,
                                y: box.y,
                                z: box.z,
                                w: box.w,
                                h: box.h,
                                d: box.d,
                                texture: box.texture,
                                rotationY: box.rotationY ?? 0,
                            });

                            mesh.userData = { index };
                            boxRoots.set(index, mesh);
                        });

                        const described = boxes.map((box) => ({
                            position: { x: box.x, y: box.y, z: box.z },
                            size: { x: box.w, y: box.h, z: box.d },
                        }));
                        const bounds = boundsFromBoxes(described);
                        buildGround(bounds);

                        if (! container.dataset.cameraReady) {
                            camera.position.set(bounds.centerX + bounds.width * 0.55, bounds.height * 0.9, bounds.centerZ + bounds.depth * 0.55);
                            controls.target.set(bounds.centerX, Math.max(0.8, bounds.height * 0.35), bounds.centerZ);
                            controls.update();
                            container.dataset.cameraReady = 'true';
                        }
                    }

                    function resizeRenderer() {
                        const width = Math.max(container.clientWidth, 320);
                        const height = Math.max(container.clientHeight, 720);
                        camera.aspect = width / height;
                        camera.updateProjectionMatrix();
                        renderer.setSize(width, height);
                    }

                    renderer.domElement.addEventListener('pointerdown', (event) => {
                        setPointer(event);
                        raycaster.setFromCamera(pointer, camera);
                        const hit = raycaster.intersectObjects(Array.from(boxRoots.values()), false)[0];

                        if (! hit) {
                            dragging = null;
                            $wire.clearSelectedBox();

                            return;
                        }

                        const mesh = hit.object;
                        const index = mesh.userData.index;
                        $wire.selectBox(index);
                        highlight(mesh);

                        if (event.button !== 0) {
                            return;
                        }

                        dragging = { mesh, index };
                        controls.enabled = false;
                    });

                    renderer.domElement.addEventListener('pointermove', (event) => {
                        if (! dragging) {
                            return;
                        }

                        setPointer(event);
                        raycaster.setFromCamera(pointer, camera);

                        const point = new THREE.Vector3();

                        if (! raycaster.ray.intersectPlane(dragPlane, point)) {
                            return;
                        }

                        dragging.mesh.position.x = point.x;
                        dragging.mesh.position.z = point.z;

                        if (selectionHelper) {
                            selectionHelper.update();
                        }
                    });

                    function stopDrag() {
                        if (! dragging) {
                            controls.enabled = true;

                            return;
                        }

                        $wire.updateBoxPosition(
                            dragging.index,
                            Number(dragging.mesh.position.x.toFixed(4)),
                            Number(dragging.mesh.position.z.toFixed(4)),
                        );

                        dragging = null;
                        controls.enabled = true;
                    }

                    renderer.domElement.addEventListener('pointerup', stopDrag);
                    renderer.domElement.addEventListener('pointerleave', stopDrag);

                    window.addEventListener('object-editor-select', (event) => {
                        const mesh = boxRoots.get(event.detail.index);

                        if (mesh) {
                            highlight(mesh);
                        }
                    });

                    window.addEventListener('object-editor-box-updated', (event) => {
                        const box = event.detail.box;

                        if (! box) {
                            return;
                        }

                        renderDefinition(event.detail.definition ?? sceneState.definition);
                        const mesh = boxRoots.get(box.index);

                        if (mesh) {
                            highlight(mesh);
                        }
                    });

                    window.addEventListener('object-editor-box-added', (event) => {
                        renderDefinition(event.detail.definition ?? sceneState.definition);
                        const mesh = boxRoots.get(event.detail.index);

                        if (mesh) {
                            highlight(mesh);
                        }
                    });

                    window.addEventListener('object-editor-box-removed', (event) => {
                        renderDefinition(event.detail.definition ?? sceneState.definition);
                    });

                    window.addEventListener('object-editor-definition-updated', (event) => {
                        sceneState.definition = event.detail.definition ?? sceneState.definition;
                        renderDefinition(sceneState.definition);
                    });

                    window.addEventListener('object-editor-reject', () => {
                        renderDefinition(sceneState.definition);
                    });

                    window.addEventListener('object-editor-max-size-updated', (event) => {
                        sceneState.maxSize = event.detail.maxSize ?? sceneState.maxSize;
                        drawMaxBoundsBox(sceneState.maxSize);
                    });

                    new ResizeObserver(() => resizeRenderer()).observe(container);

                    resizeRenderer();
                    renderDefinition(sceneState.definition);
                    drawMaxBoundsBox(sceneState.maxSize);

                    function animate() {
                        requestAnimationFrame(animate);
                        controls.update();

                        if (selectionHelper) {
                            selectionHelper.update();
                        }

                        renderer.render(scene, camera);
                    }

                    animate();
                });
            "
        >
            <div id="object-template-editor-{{ $template->id }}" style="width: 100%; min-height: 760px; border-radius: 0.75rem; overflow: hidden; background: #cfe8ff;"></div>
        </div>
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Propiedades</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Selecciona una caja desde la lista o haz clic sobre el visor para editarla.
        </p>

        @if ($selectedBoxIndex !== null)
            <div class="mt-4 space-y-4">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Caja</div>
                    <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $selectedBoxForm['label'] ?? 'Caja' }}</div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Posición (X, Y, Z)</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.position.x" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.position.y" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.position.z" /></x-filament::input.wrapper>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tamaño (Anc, Alt, Prof)</h4>
                        <button
                            type="button"
                            wire:click="toggleSizeLock"
                            @class([
                                'inline-flex h-8 w-8 items-center justify-center rounded-md transition',
                                'bg-primary-500/10 text-primary-600 hover:bg-primary-500/15 dark:text-primary-400' => $sizeLockEnabled,
                                'text-gray-400 hover:bg-white/5 hover:text-gray-200' => ! $sizeLockEnabled,
                            ])
                            title="{{ $sizeLockEnabled ? 'Desactivar proporción' : 'Activar proporción' }}"
                        >
                            <x-filament::icon :icon="$sizeLockEnabled ? 'heroicon-m-link' : 'heroicon-m-lock-open'" class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::input.wrapper><x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedBoxForm.size.x" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedBoxForm.size.y" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedBoxForm.size.z" /></x-filament::input.wrapper>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rotación (X, Y, Z)</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::input.wrapper><x-filament::input type="number" step="any" wire:model.live="selectedBoxForm.rotation.x" disabled /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.rotation.y" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="any" wire:model.live="selectedBoxForm.rotation.z" disabled /></x-filament::input.wrapper>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Textura</h4>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedBoxForm.texture">
                            @foreach ($textureOptions as $texture)
                                <option value="{{ $texture }}">{{ $texture }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <input
                        type="checkbox"
                        wire:model.live="selectedBoxForm.collidable"
                        class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                    >
                    <span class="space-y-1">
                        <span class="block text-sm font-medium text-gray-950 dark:text-white">Caja colisionable</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Si está activo, esta caja bloqueará el paso del personaje cuando se use en la escena.</span>
                    </span>
                </label>

                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="saveSelectedBox">
                        Guardar props
                    </x-filament::button>

                    <button
                        type="button"
                        wire:click="duplicateSelectedBox"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-white/5 dark:border-white/10 dark:text-gray-300"
                        title="Duplicar caja"
                    >
                        <x-filament::icon icon="heroicon-m-squares-plus" class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        wire:click="deleteSelectedBox"
                        wire:confirm="¿Eliminar esta caja del objeto?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 text-red-500 transition hover:bg-red-500/10 dark:border-red-500/20 dark:text-red-400"
                        title="Eliminar caja"
                    >
                        <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                    </button>
                </div>
            </div>
        @else
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                Todavía no hay una caja seleccionada.
            </div>
        @endif
    </div>
</div>
