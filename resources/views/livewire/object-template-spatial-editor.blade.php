@php
    use Illuminate\Support\Str;
@endphp

<div class="grid gap-4 xl:grid-cols-[19rem_minmax(0,1fr)_16rem]">
    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Opciones</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Administra las cajas que componen este objeto 3D.
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <x-filament::button wire:click="addBox" icon="heroicon-m-plus" size="sm">
                Agregar caja
            </x-filament::button>

            @if (count($selectedForGrouping) >= 2)
                <x-filament::button wire:click="createGroup" icon="heroicon-m-squares-2x2" color="gray" size="sm">
                    Agrupar ({{ count($selectedForGrouping) }})
                </x-filament::button>
            @endif
        </div>

        <div class="mt-5 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cajas del objeto</h4>
                <span class="text-xs text-gray-400" title="Marca 2 o más cajas (o Shift+clic en el visor) para agruparlas">Marcar para agrupar</span>
            </div>

            <div class="max-h-72 space-y-2 overflow-auto pr-1">
                @foreach ($sceneData['boxes'] ?? [] as $box)
                    @php
                        $boxGroup = $box['groupId'] ? collect($sceneData['groups'] ?? [])->firstWhere('id', $box['groupId']) : null;
                    @endphp
                    <div
                        id="object-box-item-{{ $box['index'] }}"
                        wire:key="object-box-item-{{ $box['index'] }}"
                        @class([
                            'w-full rounded-lg border px-3 py-2 text-left text-sm transition',
                            'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $selectedBoxIndex === $box['index'],
                            'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200' => $selectedBoxIndex !== $box['index'],
                        ])
                    >
                        <div class="flex items-start gap-2">
                            <input
                                type="checkbox"
                                wire:click="setGroupingSelection({{ $box['index'] }}, $event.target.checked)"
                                @checked(in_array($box['index'], $selectedForGrouping, true))
                                class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                                title="Marcar para agrupar (o Shift+clic sobre la caja en el visor)"
                            >

                            <button
                                type="button"
                                class="min-w-0 flex-1 text-left"
                                wire:click="selectBox({{ $box['index'] }})"
                            >
                                <div class="font-medium">{{ $box['label'] }}</div>
                                <div class="mt-1 text-xs opacity-70">
                                    X {{ round($box['position']['x'], 2) }} · Z {{ round($box['position']['z'], 2) }}
                                </div>
                                @if ($boxGroup)
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-primary-500/10 px-1.5 py-0.5 text-[10px] font-medium text-primary-600 dark:text-primary-400">
                                        <x-filament::icon icon="heroicon-m-squares-2x2" class="h-3 w-3" />
                                        {{ $boxGroup['name'] }}
                                    </div>
                                @endif
                            </button>

                            <div class="flex shrink-0 items-center gap-1">
                                <span class="text-xs uppercase tracking-wide opacity-60">
                                    {{ $box['texture'] }}
                                </span>

                                <button
                                    type="button"
                                    wire:click="toggleBoxLock({{ $box['index'] }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400"
                                    title="{{ $box['locked'] ? 'Desbloquear caja en el visor 3D' : 'Bloquear caja en el visor 3D' }}"
                                >
                                    <x-filament::icon :icon="$box['locked'] ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open'" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if (! empty($sceneData['groups']))
            <div class="mt-5 space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Grupos</h4>

                <div class="space-y-2">
                    @foreach ($sceneData['groups'] as $group)
                        <div
                            wire:key="object-group-item-{{ $group['id'] }}"
                            @class([
                                'w-full rounded-lg border px-3 py-2 text-left text-sm transition',
                                'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $selectedGroupId === $group['id'],
                                'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200' => $selectedGroupId !== $group['id'],
                            ])
                        >
                            <div class="flex items-center justify-between gap-2">
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 text-left"
                                    wire:click="selectGroup('{{ $group['id'] }}')"
                                >
                                    <div class="flex items-center gap-1 font-medium">
                                        <x-filament::icon icon="heroicon-m-squares-2x2" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $group['name'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs opacity-70">{{ count($group['boxIndices']) }} {{ Str::plural('caja', count($group['boxIndices'])) }}</div>
                                </button>

                                <button
                                    type="button"
                                    wire:click="toggleGroupLock('{{ $group['id'] }}')"
                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400"
                                    title="{{ $group['locked'] ? 'Desbloquear todas las cajas del grupo' : 'Bloquear todas las cajas del grupo' }}"
                                >
                                    <x-filament::icon :icon="$group['locked'] ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open'" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5 space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tamaño máximo del objeto</h4>

            <div class="grid grid-cols-3 gap-2">
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

            <p class="text-xs text-gray-500 dark:text-gray-400">El recuadro rojo del visor marca este límite.</p>

            <x-filament::button wire:click="recalculateMaxSize" color="gray" icon="heroicon-m-arrows-pointing-in" size="sm" class="w-full">
                Ajustar al contenido
            </x-filament::button>
        </div>
    </div>

    <div class="fi-section min-w-0 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Editor de Objeto (3D)</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Selecciona una caja y usa el gizmo (Mover/Rotar/Escalar) para editarla. Shift+clic sobre varias cajas (o los checkboxes de la lista) las marca para agruparlas. Scroll para acercar o alejar, clic derecho para rotar la cámara. Haz clic sobre el visor y usa las flechas o WASD para desplazarte.
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
                    const { TransformControls } = await import('https://esm.sh/three@0.179.1/examples/jsm/controls/TransformControls.js');
                    const { applyTiling } = await import('{{ asset('js/lib/texture-tiling-utils.js') }}');

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
                    const boxRoots = new Map();
                    let ground = null;
                    let selectionHelper = null;
                    let maxBoundsHelper = null;
                    let selectedIndex = null;

                    // Pedido del usuario: agrupar cajas y aplicarles el
                    // gizmo (Mover/Rotar/Escalar) a TODAS juntas.
                    // `groupObject` es un `THREE.Group` temporal creado solo
                    // mientras un grupo está seleccionado — sus miembros se
                    // reparentan bajo él con `attach()` (conserva el
                    // transform mundial de cada uno) y vuelven a
                    // `target.world` al deseleccionar o reconstruir la
                    // escena.
                    let groupObject = null;
                    let selectedGroupId = null;
                    let selectedGroupBoxIndices = [];

                    // Gizmo de transformación (Mover/Rotar/Escalar) — mismo
                    // mecanismo que `PlazaSpatialEditor`, sin restricción de
                    // eje en ningún modo para una caja individual: a
                    // diferencia de los elementos de plaza, una caja no
                    // tiene límite de rotación en X/Z (pedido del usuario).
                    const transformControls = new TransformControls(camera, renderer.domElement);
                    scene.add(transformControls.getHelper());
                    transformControls.setRotationSnap(THREE.MathUtils.degToRad(45));

                    transformControls.addEventListener('objectChange', () => {
                        if (selectionHelper?.object === transformControls.object) {
                            selectionHelper.update();
                        }
                    });

                    transformControls.addEventListener('dragging-changed', (event) => {
                        controls.enabled = ! event.value;

                        // Solo al SOLTAR (value === false) se persiste — el
                        // arrastre en sí es puramente visual.
                        if (event.value || ! transformControls.object) {
                            return;
                        }

                        const mode = transformControls.getMode();

                        if (transformControls.object === groupObject) {
                            // Escala uniforme forzada (ver
                            // `applyScaleAxisRestriction()`): en modo
                            // Escalar sobre un grupo, x/y/z del gizmo
                            // siempre llegan iguales.
                            const scaleFactor = groupObject.scale.x;

                            const updates = selectedGroupBoxIndices.map((index) => {
                                const mesh = boxRoots.get(index);

                                if (! mesh) {
                                    return null;
                                }

                                const worldPos = mesh.getWorldPosition(new THREE.Vector3());
                                const worldQuat = mesh.getWorldQuaternion(new THREE.Quaternion());
                                const worldEuler = new THREE.Euler().setFromQuaternion(worldQuat, 'XYZ');

                                const update = {
                                    index,
                                    x: Number(worldPos.x.toFixed(4)),
                                    y: Number(worldPos.y.toFixed(4)),
                                    z: Number(worldPos.z.toFixed(4)),
                                    rotationX: Number(worldEuler.x.toFixed(4)),
                                    rotationY: Number(worldEuler.y.toFixed(4)),
                                    rotationZ: Number(worldEuler.z.toFixed(4)),
                                };

                                if (mode === 'scale') {
                                    const box = (sceneState.definition.boxes ?? [])[index] ?? {};
                                    update.w = (box.w ?? 1) * scaleFactor;
                                    update.h = (box.h ?? 1) * scaleFactor;
                                    update.d = (box.d ?? 1) * scaleFactor;
                                }

                                return update;
                            }).filter(Boolean);

                            // El wrapper vuelve a escala neutra: el próximo
                            // `renderDefinition()` (disparado por la
                            // respuesta de `updateGroupBoxes`) reconstruye
                            // cada caja ya con sus valores nuevos horneados,
                            // así que no debe arrastrar ninguna
                            // transformación propia de esta sesión a la
                            // siguiente.
                            groupObject.scale.set(1, 1, 1);

                            $wire.updateGroupBoxes(selectedGroupId, updates);

                            return;
                        }

                        const index = transformControls.object.userData.index;

                        if (mode === 'translate') {
                            const { x, y, z } = transformControls.object.position;
                            $wire.updateBoxPosition(index, Number(x.toFixed(4)), Number(y.toFixed(4)), Number(z.toFixed(4)));

                            return;
                        }

                        if (mode === 'rotate') {
                            const { x, y, z } = transformControls.object.rotation;
                            $wire.updateBoxRotation(index, x, y, z);

                            return;
                        }

                        // Escalar: la caja no tiene un multiplicador de
                        // escala separado, su tamaño ES w/h/d — el backend
                        // multiplica esas dimensiones por este factor y
                        // reconstruye la geometría con escala 1.
                        const { x, y, z } = transformControls.object.scale;
                        $wire.updateBoxScale(index, x, y, z);
                    });

                    const gizmoModeButtonActiveClasses = ['bg-red-600', 'text-white', 'border-red-600', 'dark:bg-red-600', 'dark:text-white'];
                    const gizmoModeButtonInactiveClasses = ['text-red-600', 'border-red-200', 'dark:text-red-400', 'dark:border-red-500/30'];

                    function setActiveGizmoModeButton(mode) {
                        ['translate', 'rotate', 'scale'].forEach((candidate) => {
                            const button = document.getElementById(`object-gizmo-mode-${candidate}`);

                            if (! button) {
                                return;
                            }

                            const isActive = candidate === mode;
                            button.classList.remove(...gizmoModeButtonActiveClasses, ...gizmoModeButtonInactiveClasses);
                            button.classList.add(...(isActive ? gizmoModeButtonActiveClasses : gizmoModeButtonInactiveClasses));
                        });
                    }

                    // Pedido del usuario: un grupo escala de forma uniforme
                    // — cajas rotadas entre sí bajo una escala NO uniforme
                    // del grupo se deformarían (w/h/d + rotación simple no
                    // puede representar eso). `showX/Y/Z = false` oculta las
                    // flechas/planos de un solo eje del gizmo de escala,
                    // pero TransformControls sigue mostrando el cubo
                    // central (escala uniforme) — es la única forma de
                    // escalar un grupo.
                    function applyScaleAxisRestriction() {
                        const restrict = transformControls.getMode() === 'scale' && transformControls.object === groupObject;
                        transformControls.showX = ! restrict;
                        transformControls.showY = ! restrict;
                        transformControls.showZ = ! restrict;
                    }

                    window.addEventListener('object-editor-set-mode', (event) => {
                        transformControls.setMode(event.detail.mode);
                        applyScaleAxisRestriction();
                        setActiveGizmoModeButton(event.detail.mode);
                    });

                    setActiveGizmoModeButton(transformControls.getMode());

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
                            target.world.remove(mesh);
                        });

                        boxRoots.clear();
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

                    // Deshace el reparentado temporal de `selectGroupMesh()`
                    // — cada caja vuelve a `target.world` conservando su
                    // transform mundial (`attach()`, igual que al agrupar),
                    // así que sigue exactamente donde el usuario la dejó.
                    function teardownGroup() {
                        if (groupObject) {
                            [...groupObject.children].forEach((child) => target.world.attach(child));
                            scene.remove(groupObject);
                            groupObject = null;
                        }

                        selectedGroupId = null;
                        selectedGroupBoxIndices = [];
                    }

                    function detachBoxSelection() {
                        if (selectionHelper) {
                            scene.remove(selectionHelper);
                            selectionHelper = null;
                        }

                        transformControls.enabled = true;
                        transformControls.detach();
                        selectedIndex = null;
                    }

                    // Pedido del usuario: marcar cajas para agrupar también
                    // con Shift+clic en el visor, no solo desde los
                    // checkboxes de la lista — ambos caminos leen/escriben
                    // la MISMA propiedad reactiva de Livewire
                    // (`$wire.selectedForGrouping`), así que quedan
                    // sincronizados solos sin código extra. `$wire.$watch`
                    // dispara el resaltado (naranja) sin importar cuál de
                    // los dos caminos cambió la selección.
                    const groupMarkHelpers = new Map();

                    function syncGroupMarkers() {
                        const marked = new Set((($wire.selectedForGrouping) ?? []).map(Number));

                        groupMarkHelpers.forEach((helper, index) => {
                            if (! marked.has(index)) {
                                scene.remove(helper);
                                groupMarkHelpers.delete(index);
                            }
                        });

                        marked.forEach((index) => {
                            if (groupMarkHelpers.has(index) || ! boxRoots.has(index)) {
                                return;
                            }

                            const helper = new THREE.BoxHelper(boxRoots.get(index), 0xf59e0b);
                            scene.add(helper);
                            groupMarkHelpers.set(index, helper);
                        });
                    }

                    function toggleGroupMark(index) {
                        const current = ($wire.selectedForGrouping ?? []).map(Number);

                        $wire.setGroupingSelection(index, ! current.includes(index));
                    }

                    $wire.$watch('selectedForGrouping', () => syncGroupMarkers());

                    // Attach/detach del gizmo y del recuadro de selección —
                    // se llama después de CUALQUIER reconstrucción de
                    // mallas (`renderDefinition()` recrea todo desde cero),
                    // así que siempre vuelve a apuntar a la malla vigente en
                    // vez de a una ya destruida. Seleccionar una caja
                    // individual siempre deshace cualquier grupo activo
                    // (mutuamente excluyentes, igual que en el backend).
                    function selectMesh(index) {
                        teardownGroup();
                        detachBoxSelection();

                        const mesh = (index === null || index === undefined) ? null : boxRoots.get(index);

                        if (! mesh) {
                            return;
                        }

                        selectedIndex = index;
                        const locked = !! mesh.userData?.locked;
                        transformControls.enabled = ! locked;

                        selectionHelper = new THREE.BoxHelper(mesh, locked ? 0x9ca3af : 0xef4444);
                        scene.add(selectionHelper);

                        transformControls.attach(mesh);
                        applyScaleAxisRestriction();
                        setActiveGizmoModeButton(transformControls.getMode());
                    }

                    // Construye el `THREE.Group` temporal para el gizmo de
                    // grupo — pivote en el centro promedio de sus cajas
                    // (más predecible que el centro de la caja delimitadora
                    // cuando los tamaños son muy distintos).
                    function selectGroupMesh(groupId, boxIndices) {
                        teardownGroup();
                        detachBoxSelection();

                        const meshes = (boxIndices ?? []).map((i) => boxRoots.get(i)).filter(Boolean);

                        if (meshes.length === 0) {
                            return;
                        }

                        selectedGroupId = groupId;
                        selectedGroupBoxIndices = boxIndices;

                        const pivot = new THREE.Vector3();
                        meshes.forEach((mesh) => pivot.add(mesh.getWorldPosition(new THREE.Vector3())));
                        pivot.divideScalar(meshes.length);

                        groupObject = new THREE.Group();
                        groupObject.position.copy(pivot);
                        scene.add(groupObject);
                        meshes.forEach((mesh) => groupObject.attach(mesh));

                        // Si CUALQUIER miembro está bloqueado, el grupo
                        // completo no se puede arrastrar — mover el grupo
                        // movería igual a la caja bloqueada.
                        const anyLocked = meshes.some((mesh) => mesh.userData?.locked);
                        transformControls.enabled = ! anyLocked;

                        selectionHelper = new THREE.BoxHelper(groupObject, anyLocked ? 0x9ca3af : 0x6366f1);
                        scene.add(selectionHelper);

                        transformControls.attach(groupObject);
                        applyScaleAxisRestriction();
                        setActiveGizmoModeButton(transformControls.getMode());
                    }

                    function reselectAfterRebuild() {
                        if (selectedGroupId) {
                            selectGroupMesh(selectedGroupId, selectedGroupBoxIndices);
                        } else {
                            selectMesh(selectedIndex);
                        }
                    }

                    function renderDefinition(definition) {
                        // Cualquier caja bajo `groupObject` debe volver a
                        // `target.world` ANTES de destruir todo — si no,
                        // `clearMeshes()` la busca donde ya no está.
                        teardownGroup();
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
                                rotationX: box.rotationX ?? 0,
                                rotationY: box.rotationY ?? 0,
                                rotationZ: box.rotationZ ?? 0,
                                emissive: box.emissive ? parseInt(box.emissive.slice(1), 16) : 0x000000,
                            });

                            mesh.userData = { index, locked: !! box.locked, groupId: box.groupId ?? null };

                            if (box.tiling) {
                                applyTiling(mesh, box.tiling);
                            }

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

                        // Las mallas se reconstruyeron desde cero — los
                        // resaltados de 'marcada para agrupar' (`groupMarkHelpers`)
                        // apuntaban a mallas que ya no existen.
                        groupMarkHelpers.forEach((helper) => scene.remove(helper));
                        groupMarkHelpers.clear();
                        syncGroupMarkers();
                    }

                    function resizeRenderer() {
                        const width = Math.max(container.clientWidth, 320);
                        const height = Math.max(container.clientHeight, 720);
                        camera.aspect = width / height;
                        camera.updateProjectionMatrix();
                        renderer.setSize(width, height);
                    }

                    renderer.domElement.addEventListener('pointerdown', (event) => {
                        // El clic fue sobre el propio gizmo — no
                        // reinterpretarlo como selección ni como
                        // deseleccionar por clic en vacío.
                        if (transformControls.dragging) {
                            return;
                        }

                        setPointer(event);
                        raycaster.setFromCamera(pointer, camera);
                        const hit = raycaster.intersectObjects(Array.from(boxRoots.values()), false)[0];

                        if (! hit) {
                            if (event.button === 0 && ! event.shiftKey) {
                                $wire.clearSelectedBox();
                                selectMesh(null);
                            }

                            return;
                        }

                        const index = hit.object.userData.index;

                        // Shift+clic marca/desmarca la caja para agrupar
                        // (pedido del usuario) — no cambia la selección de
                        // edición ni el panel de Propiedades, es
                        // independiente, igual que los checkboxes de la
                        // lista.
                        if (event.shiftKey) {
                            toggleGroupMark(index);

                            return;
                        }

                        // Clic normal sobre una caja individual en el visor
                        // siempre selecciona ESA caja (no su grupo, si tiene
                        // uno) — los grupos se seleccionan desde la lista
                        // lateral.
                        $wire.selectBox(index);
                        selectMesh(index);
                    });

                    window.addEventListener('object-editor-select', (event) => selectMesh(event.detail.index));

                    window.addEventListener('object-editor-select-group', (event) => {
                        if (event.detail.groupId) {
                            selectGroupMesh(event.detail.groupId, event.detail.boxIndices ?? []);
                        } else {
                            teardownGroup();
                            detachBoxSelection();
                        }
                    });

                    window.addEventListener('object-editor-box-updated', (event) => {
                        renderDefinition(event.detail.definition ?? sceneState.definition);
                        selectMesh(event.detail.box?.index ?? selectedIndex);
                    });

                    window.addEventListener('object-editor-box-added', (event) => {
                        renderDefinition(event.detail.definition ?? sceneState.definition);
                        selectMesh(event.detail.index);
                    });

                    window.addEventListener('object-editor-box-removed', (event) => {
                        renderDefinition(event.detail.definition ?? sceneState.definition);
                        selectMesh(null);
                    });

                    window.addEventListener('object-editor-definition-updated', (event) => {
                        sceneState.definition = event.detail.definition ?? sceneState.definition;
                        renderDefinition(sceneState.definition);
                        reselectAfterRebuild();
                    });

                    window.addEventListener('object-editor-reject', () => {
                        renderDefinition(sceneState.definition);
                        reselectAfterRebuild();
                    });

                    window.addEventListener('object-editor-max-size-updated', (event) => {
                        sceneState.maxSize = event.detail.maxSize ?? sceneState.maxSize;
                        drawMaxBoundsBox(sceneState.maxSize);
                    });

                    window.addEventListener('object-editor-tiling-preview', (event) => {
                        const mesh = boxRoots.get(event.detail.index);

                        if (mesh) {
                            applyTiling(mesh, event.detail.tiling);
                        }
                    });

                    new ResizeObserver(() => resizeRenderer()).observe(container);

                    resizeRenderer();
                    renderDefinition(sceneState.definition);
                    drawMaxBoundsBox(sceneState.maxSize);

                    // Navegación con flechas/WASD — mismo mapeo y mecanismo
                    // que `PlazaSpatialEditor` (ver ese archivo para más
                    // contexto): `container` (no `window`) recibe los
                    // eventos, y solo cuando tiene foco (`tabindex` en el
                    // div de abajo), para no competir con las flechas de
                    // los inputs numéricos de Posición/Tamaño/Rotación.
                    const movement = { forward: false, backward: false, left: false, right: false };

                    function setMovement(code, pressed) {
                        if (code === 'ArrowUp' || code === 'KeyW') movement.forward = pressed;
                        if (code === 'ArrowDown' || code === 'KeyS') movement.backward = pressed;
                        if (code === 'ArrowLeft' || code === 'KeyA') movement.left = pressed;
                        if (code === 'ArrowRight' || code === 'KeyD') movement.right = pressed;
                    }

                    container.addEventListener('keydown', (event) => {
                        setMovement(event.code, true);

                        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.code)) {
                            event.preventDefault();
                        }
                    });

                    container.addEventListener('keyup', (event) => setMovement(event.code, false));

                    const clock = new THREE.Clock();
                    const panSpeed = 22;

                    function applyKeyboardPan(delta) {
                        if (! controls.enabled) {
                            return;
                        }

                        const forward = new THREE.Vector3();
                        camera.getWorldDirection(forward);
                        forward.y = 0;
                        forward.normalize();
                        const right = new THREE.Vector3().crossVectors(forward, camera.up).normalize();

                        const move = new THREE.Vector3();
                        if (movement.forward) move.add(forward);
                        if (movement.backward) move.sub(forward);
                        if (movement.right) move.add(right);
                        if (movement.left) move.sub(right);

                        if (move.lengthSq() === 0) {
                            return;
                        }

                        move.normalize().multiplyScalar(panSpeed * delta);
                        camera.position.add(move);
                        controls.target.add(move);
                    }

                    function animate() {
                        requestAnimationFrame(animate);
                        applyKeyboardPan(clock.getDelta());
                        controls.update();

                        if (selectionHelper) {
                            selectionHelper.update();
                        }

                        groupMarkHelpers.forEach((helper) => helper.update());

                        renderer.render(scene, camera);
                    }

                    animate();
                });
            "
        >
            <div
                id="object-template-editor-{{ $template->id }}"
                tabindex="0"
                style="width: 100%; min-height: 760px; border-radius: 0.75rem; overflow: hidden; background: #cfe8ff; outline: none;"
                onfocus="this.style.boxShadow = 'inset 0 0 0 2px #6366f1';"
                onblur="this.style.boxShadow = 'none';"
            ></div>
        </div>
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Propiedades</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Selecciona una caja o un grupo desde la lista, o haz clic sobre el visor para editar una caja.
        </p>

        @if ($selectedGroupId !== null)
            @php $selectedGroup = collect($sceneData['groups'] ?? [])->firstWhere('id', $selectedGroupId); @endphp
            @if ($selectedGroup)
                <div class="mt-4 space-y-4">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Grupo</div>
                        <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $selectedGroup['name'] }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($selectedGroup['boxIndices']) }} {{ Str::plural('caja', count($selectedGroup['boxIndices'])) }}</div>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Modo del gizmo en el visor</h4>
                        <div class="flex gap-1">
                            <button
                                type="button"
                                id="object-gizmo-mode-translate"
                                class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                                onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'translate' } }))"
                            >
                                Mover
                            </button>
                            <button
                                type="button"
                                id="object-gizmo-mode-rotate"
                                class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                                onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'rotate' } }))"
                            >
                                Rotar
                            </button>
                            <button
                                type="button"
                                id="object-gizmo-mode-scale"
                                class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                                onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'scale' } }))"
                            >
                                Escalar
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Un grupo escala de forma uniforme (mismo factor en los 3 ejes).</p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nombre del grupo</h4>
                        <div class="flex gap-2">
                            <x-filament::input.wrapper class="flex-1">
                                <x-filament::input type="text" wire:model="selectedGroupForm.name" />
                            </x-filament::input.wrapper>
                            <x-filament::button wire:click="renameSelectedGroup" size="sm">
                                Guardar
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cajas en este grupo</h4>
                        <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                            @foreach ($selectedGroup['boxIndices'] as $memberIndex)
                                @php $memberBox = collect($sceneData['boxes'] ?? [])->firstWhere('index', $memberIndex); @endphp
                                @if ($memberBox)
                                    <li>{{ $memberBox['label'] }} — {{ $memberBox['texture'] }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::button
                            wire:click="toggleGroupLock('{{ $selectedGroupId }}')"
                            color="gray"
                            :icon="$selectedGroup['locked'] ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open'"
                            size="sm"
                        >
                            {{ $selectedGroup['locked'] ? 'Desbloquear grupo' : 'Bloquear grupo' }}
                        </x-filament::button>

                        <button
                            type="button"
                            wire:click="duplicateSelectedGroup"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-white/5 dark:border-white/10 dark:text-gray-300"
                            title="Duplicar grupo (copia todas sus cajas en un grupo nuevo)"
                        >
                            <x-filament::icon icon="heroicon-m-squares-plus" class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="ungroupSelected"
                            wire:confirm="¿Desagrupar? Las cajas se quedan tal cual, solo dejan de estar agrupadas."
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-white/5 dark:border-white/10 dark:text-gray-300"
                            title="Desagrupar"
                        >
                            <x-filament::icon icon="heroicon-m-x-circle" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            @endif
        @elseif ($selectedBoxIndex !== null)
            <div class="mt-4 space-y-4">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Caja</div>
                    <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $selectedBoxForm['label'] ?? 'Caja' }}</div>
                </div>

                <div class="space-y-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Modo del gizmo en el visor</h4>
                    <div class="flex gap-1">
                        <button
                            type="button"
                            id="object-gizmo-mode-translate"
                            class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                            onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'translate' } }))"
                        >
                            Mover
                        </button>
                        <button
                            type="button"
                            id="object-gizmo-mode-rotate"
                            class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                            onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'rotate' } }))"
                        >
                            Rotar
                        </button>
                        <button
                            type="button"
                            id="object-gizmo-mode-scale"
                            class="object-gizmo-mode-button flex-1 rounded-md border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 transition dark:border-red-500/30 dark:text-red-400"
                            onclick="window.dispatchEvent(new CustomEvent('object-editor-set-mode', { detail: { mode: 'scale' } }))"
                        >
                            Escalar
                        </button>
                    </div>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sin límite de eje, una caja puede rotar libre en X/Y/Z.</p>
                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.rotation.x" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.rotation.y" /></x-filament::input.wrapper>
                        <x-filament::input.wrapper><x-filament::input type="number" step="0.001" wire:model.live="selectedBoxForm.rotation.z" /></x-filament::input.wrapper>
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

                <div class="space-y-3">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <input
                            type="checkbox"
                            wire:model.live="selectedBoxForm.glowEnabled"
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                        >
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                            Efecto de iluminado
                            <x-info-popover>
                                Da la impresión de que esta caja emite luz (ej. el bombillo de un farol), sin necesitar una luz real en la escena.
                            </x-info-popover>
                        </span>
                    </label>

                    @if ($selectedBoxForm['glowEnabled'] ?? false)
                        <input
                            type="color"
                            wire:model.live.debounce.500ms="selectedBoxForm.glowColor"
                            class="h-10 w-20 cursor-pointer rounded-md border border-gray-200 bg-transparent dark:border-white/10"
                        >
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tiling de textura (U,V)</h4>
                        <x-info-popover>
                            Repetición de la textura sobre esta caja.
                        </x-info-popover>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" min="0.001" step="0.1" wire:model.live.debounce.500ms="selectedBoxForm.tiling.u" />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" min="0.001" step="0.1" wire:model.live.debounce.500ms="selectedBoxForm.tiling.v" />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <input
                        type="checkbox"
                        wire:model.live="selectedBoxForm.collidable"
                        class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                    >
                    <span class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        Caja colisionable
                        <x-info-popover>
                            Si está activo, esta caja bloqueará el paso del personaje cuando se use en la escena.
                        </x-info-popover>
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
                Todavía no hay una caja ni un grupo seleccionado.
            </div>
        @endif
    </div>
</div>
