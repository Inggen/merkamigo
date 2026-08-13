<div class="grid gap-4 xl:grid-cols-[14rem_minmax(0,1fr)_16rem]">
    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 xl:order-3">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Propiedades</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Selecciona un elemento de la plaza o haz clic sobre el visor para editarlo.
        </p>

        @if ($selectedObjectType && $selectedObjectId)
            <div class="mt-4 space-y-4">
                <div class="flex items-start justify-between gap-2 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ match ($selectedObjectType) {
                                'slot' => 'Stand',
                                'spawn' => 'Punto de aparición',
                                default => 'Elemento',
                            } }}
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $selectedObjectForm['label'] ?? 'Objeto' }}</div>
                    </div>

                    @if ($selectedObjectForm['objectEditorUrl'] ?? null)
                        <a
                            href="{{ $selectedObjectForm['objectEditorUrl'] }}"
                            target="_blank"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400"
                            title="Editar objeto en el editor de objetos"
                        >
                            <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
                        </a>
                    @endif
                </div>

                <div class="space-y-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Modo del gizmo en el visor</h4>
                    <div class="flex gap-1">
                        <button
                            type="button"
                            id="gizmo-mode-translate"
                            class="gizmo-mode-button flex-1 rounded-md border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-600 transition dark:border-white/10 dark:text-gray-300"
                            onclick="window.dispatchEvent(new CustomEvent('spatial-editor-set-mode', { detail: { mode: 'translate' } }))"
                        >
                            Mover
                        </button>
                        <button
                            type="button"
                            id="gizmo-mode-rotate"
                            class="gizmo-mode-button flex-1 rounded-md border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-600 transition dark:border-white/10 dark:text-gray-300"
                            onclick="window.dispatchEvent(new CustomEvent('spatial-editor-set-mode', { detail: { mode: 'rotate' } }))"
                        >
                            Rotar
                        </button>
                        @if ($selectedObjectType === 'prop')
                            <button
                                type="button"
                                id="gizmo-mode-scale"
                                class="gizmo-mode-button flex-1 rounded-md border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-600 transition dark:border-white/10 dark:text-gray-300"
                                onclick="window.dispatchEvent(new CustomEvent('spatial-editor-set-mode', { detail: { mode: 'scale' } }))"
                            >
                                Escalar
                            </button>
                        @endif
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Posición (X, Y, Z)</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.position.x" />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.position.y" />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.position.z" />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                @unless ($selectedObjectType === 'spawn')
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tamaño (Anc, Alt, Pro)</h4>
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
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedObjectForm.size.x" />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedObjectForm.size.y" :disabled="$selectedObjectType === 'slot'" />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="0.001" step="0.001" wire:model.live="selectedObjectForm.size.z" />
                            </x-filament::input.wrapper>
                        </div>
                        @if ($selectedObjectType === 'slot')
                            <p class="text-xs text-gray-500 dark:text-gray-400">En stands, la altura se muestra pero se conserva desde la plantilla actual.</p>
                        @endif
                    </div>
                @endunless

                <div class="space-y-3">
                    @if ($selectedObjectType === 'spawn')
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rotación (Y)</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">El personaje solo gira sobre este eje — no se inclina en X/Z.</p>
                        <div class="grid grid-cols-3 gap-2">
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.rotation.y" />
                            </x-filament::input.wrapper>
                        </div>
                    @else
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rotación (X, Y, Z)</h4>
                        <div class="grid grid-cols-3 gap-2">
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.rotation.x" disabled />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.rotation.y" />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" step="any" wire:model.live="selectedObjectForm.rotation.z" disabled />
                            </x-filament::input.wrapper>
                        </div>
                    @endif
                </div>

                @if ($selectedObjectType === 'prop' && ($selectedObjectForm['hasGlbModel'] ?? false))
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Colisiones del modelo GLB</h4>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                            <input
                                type="checkbox"
                                wire:model.live="selectedObjectForm.collisionEnabled"
                                class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                            >
                            <span class="space-y-1">
                                <span class="block text-sm font-medium text-gray-950 dark:text-white">Validar colisiones</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Si está activo, este modelo 3D bloqueará el paso del personaje en la experiencia.</span>
                            </span>
                        </label>
                    </div>
                @endif

                @if ($selectedObjectType === 'prop')
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tiling de textura (U, V)</h4>
                            <button
                                type="button"
                                title="Repetición de la textura sobre este elemento — cada instancia puede tener su propio valor aunque compartan el mismo modelo 3D."
                                aria-label="Repetición de la textura sobre este elemento — cada instancia puede tener su propio valor aunque compartan el mismo modelo 3D."
                                class="inline-flex items-center justify-center text-zinc-400 transition hover:text-zinc-600 dark:hover:text-zinc-200"
                            >
                                <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="0.001" step="0.1" wire:model.live.debounce.500ms="selectedObjectForm.tiling.u" />
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" min="0.001" step="0.1" wire:model.live.debounce.500ms="selectedObjectForm.tiling.v" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @endif

                    @if ($selectedObjectType === 'prop' && ($selectedObjectForm['status'] ?? null))
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Estado:</span>
                            @if ($selectedObjectForm['status'] === 'confirmado')
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2 py-1 font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-3.5 w-3.5" />
                                    Confirmado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                    Borrador — solo visible con vista previa
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <x-filament::button wire:click="saveSelectedObject">
                            {{ $selectedObjectType === 'spawn' ? 'Guardar punto de aparición' : 'Guardar props' }}
                        </x-filament::button>

                    @if ($selectedObjectType === 'prop')
                        @if (($selectedObjectForm['status'] ?? null) === 'borrador')
                            <button
                                type="button"
                                wire:click="confirmProp({{ $selectedObjectId }})"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-success-200 text-success-600 transition hover:bg-success-500/10 dark:border-success-500/20 dark:text-success-400"
                                title="Confirmar elemento (hacerlo visible al público)"
                            >
                                <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="duplicateProp({{ $selectedObjectId }})"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-white/5 dark:border-white/10 dark:text-gray-300"
                            title="Duplicar elemento"
                        >
                            <x-filament::icon icon="heroicon-m-squares-plus" class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="deleteProp({{ $selectedObjectId }})"
                            wire:confirm="¿Eliminar este elemento de la plaza?"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 text-red-500 transition hover:bg-red-500/10 dark:border-red-500/20 dark:text-red-400"
                            title="Eliminar elemento"
                        >
                            <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                        </button>
                    @elseif ($selectedObjectType === 'slot')
                        <button
                            type="button"
                            wire:click="duplicateSlot({{ $selectedObjectId }})"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-white/5 dark:border-white/10 dark:text-gray-300"
                            title="Duplicar stand"
                        >
                            <x-filament::icon icon="heroicon-m-squares-plus" class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="deleteSlot({{ $selectedObjectId }})"
                            wire:confirm="¿Eliminar este stand de la plaza? Si tiene un negocio asignado, la asignación quedará sin stand."
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 text-red-500 transition hover:bg-red-500/10 dark:border-red-500/20 dark:text-red-400"
                            title="Eliminar stand"
                        >
                            <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                        </button>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-4 space-y-3 rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                <div>
                    Todavía no hay un objeto seleccionado.
                </div>

                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource::getUrl('create')"
                    icon="heroicon-m-plus"
                >
                    Agregar objeto
                </x-filament::button>
            </div>
        @endif
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 xl:order-2">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Editor espacial (3D)</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Mantiene el mismo visor actual: scroll para acercar o alejar, clic derecho para rotar la cámara y arrastre del objeto para guardar su posición en X/Z al soltar. Haz clic sobre el visor y usa las flechas o WASD para desplazarte.
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
                import('{{ asset('js/lib/voxel-plaza-engine.js') }}').then(async ({ THREE, createStandaloneVoxelTarget, renderObjectByPriority, createAxisLabels }) => {
                    const { OrbitControls } = await import('https://esm.sh/three@0.179.1/examples/jsm/controls/OrbitControls.js');
                    const { TransformControls } = await import('https://esm.sh/three@0.179.1/examples/jsm/controls/TransformControls.js');
                    const { applyTiling } = await import('{{ asset('js/lib/texture-tiling-utils.js') }}');

                    const container = document.getElementById(@js('plaza-spatial-editor-'.$plaza->id));
                    const sceneState = structuredClone(@js($sceneData));

                    if (! container || container.dataset.initialized === 'true') {
                        return;
                    }

                    container.dataset.initialized = 'true';

                    const threeScene = new THREE.Scene();
                    threeScene.background = new THREE.Color(0xcfe8ff);

                    const renderer = new THREE.WebGLRenderer({ antialias: true });
                    renderer.shadowMap.enabled = true;
                    container.appendChild(renderer.domElement);

                    const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 2000);
                    const controls = new OrbitControls(camera, renderer.domElement);
                    const target = createStandaloneVoxelTarget();
                    const raycaster = new THREE.Raycaster();
                    const pointer = new THREE.Vector2();
                    const groundPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
                    const draggables = [];
                    const objectIndex = new Map();
                    const boundsHandles = [];
                    let ground = null;
                    let boundsOutline = null;
                    let boundsFill = null;
                    let selectionHelper = null;
                    let draggingBoundsHandle = null;

                    // Gizmo de transformación (posición/rotación/tamaño) por
                    // objeto seleccionado — reemplaza el arrastre casero de
                    // X/Z que había antes. Se construye ANTES del listener
                    // 'pointerdown' de más abajo a propósito: TransformControls
                    // registra su propio listener sobre el mismo
                    // `renderer.domElement` en su constructor (vía `connect`),
                    // y como los listeners de un mismo evento se disparan en
                    // orden de registro, para cuando nuestro 'pointerdown'
                    // corre, `transformControls.dragging` ya refleja si el
                    // clic fue sobre el gizmo — así evitamos reinterpretar
                    // ese clic como seleccionar otra cosa o deseleccionar.
                    const transformControls = new TransformControls(camera, renderer.domElement);
                    threeScene.add(transformControls.getHelper());
                    transformControls.setRotationSnap(THREE.MathUtils.degToRad(45));

                    // Snapping/auto-conexión entre objetos (Fase 5):
                    // heurística de mejor esfuerzo por cajas
                    // delimitadoras — no es un sistema de conectores reales
                    // con puntos de anclaje declarados, solo detecta cuándo
                    // el borde del objeto arrastrado queda muy cerca del
                    // borde de OTRO objeto (en X o en Z, con solapamiento
                    // suficiente en el eje perpendicular para no encajar
                    // objetos que ni siquiera están uno frente al otro) y
                    // ajusta la posición para que queden borde-con-borde.
                    // Nunca toca Y ni rotación.
                    const SNAP_DISTANCE = 0.5;

                    function snapToNeighbors(root) {
                        if (root.userData?.type !== 'prop' && root.userData?.type !== 'slot') {
                            return;
                        }

                        const box = new THREE.Box3().setFromObject(root);

                        for (const neighbor of draggables) {
                            if (neighbor === root) {
                                continue;
                            }

                            const neighborBox = new THREE.Box3().setFromObject(neighbor);

                            const zOverlap = Math.min(box.max.z, neighborBox.max.z) - Math.max(box.min.z, neighborBox.min.z);

                            if (zOverlap > 0) {
                                if (Math.abs(box.min.x - neighborBox.max.x) < SNAP_DISTANCE) {
                                    root.position.x += neighborBox.max.x - box.min.x;

                                    return;
                                }

                                if (Math.abs(box.max.x - neighborBox.min.x) < SNAP_DISTANCE) {
                                    root.position.x += neighborBox.min.x - box.max.x;

                                    return;
                                }
                            }

                            const xOverlap = Math.min(box.max.x, neighborBox.max.x) - Math.max(box.min.x, neighborBox.min.x);

                            if (xOverlap > 0) {
                                if (Math.abs(box.min.z - neighborBox.max.z) < SNAP_DISTANCE) {
                                    root.position.z += neighborBox.max.z - box.min.z;

                                    return;
                                }

                                if (Math.abs(box.max.z - neighborBox.min.z) < SNAP_DISTANCE) {
                                    root.position.z += neighborBox.min.z - box.max.z;

                                    return;
                                }
                            }
                        }
                    }

                    transformControls.addEventListener('objectChange', () => {
                        if (transformControls.getMode() === 'translate' && transformControls.object) {
                            snapToNeighbors(transformControls.object);
                        }

                        if (selectionHelper?.object === transformControls.object) {
                            selectionHelper.update();
                        }
                    });

                    transformControls.addEventListener('dragging-changed', (event) => {
                        controls.enabled = !event.value;

                        // Solo al SOLTAR (value === false) se persiste — el
                        // arrastre en sí es puramente visual, igual que el
                        // mecanismo anterior de raycaster casero.
                        if (event.value || !transformControls.object) {
                            return;
                        }

                        const { type, id } = transformControls.object.userData;
                        const mode = transformControls.getMode();

                        if (mode === 'translate') {
                            const { x, y, z } = transformControls.object.position;

                            if (type === 'slot') {
                                $wire.updateSlotPosition(id, x, y, z);
                            } else if (type === 'spawn') {
                                $wire.updateSpawnPosition(x, y, z);
                            } else {
                                $wire.updatePropPosition(id, x, y, z);
                            }

                            return;
                        }

                        if (mode === 'rotate') {
                            // El eje Y de Three.js ya viene en radianes; el
                            // backend guarda grados (mismo formato que ya usa
                            // el resto del editor — inputs numéricos,
                            // `normalizeRotation()`), así que se convierte
                            // antes de persistir.
                            const rotationYDegrees = THREE.MathUtils.radToDeg(transformControls.object.rotation.y);

                            if (type === 'slot') {
                                $wire.updateSlotRotation(id, rotationYDegrees);
                            } else if (type === 'spawn') {
                                $wire.updateSpawnRotation(rotationYDegrees);
                            } else {
                                $wire.updatePropRotation(id, rotationYDegrees);
                            }

                            return;
                        }

                        // Solo props: el botón Escalar ni siquiera se
                        // renderiza para slot/spawn (ver `focusSelection`),
                        // así que este caso no debería alcanzarse para
                        // otros tipos, pero se guarda igual por si acaso.
                        if (mode === 'scale' && type === 'prop') {
                            const { x, y, z } = transformControls.object.scale;
                            $wire.updatePropScale(id, x, y, z);
                        }
                    });

                    const gizmoModeButtonActiveClasses = ['bg-primary-500/10', 'text-primary-600', 'border-primary-500', 'dark:text-primary-400'];
                    const gizmoModeButtonInactiveClasses = ['text-gray-600', 'border-gray-200', 'dark:text-gray-300', 'dark:border-white/10'];

                    function setActiveGizmoModeButton(mode) {
                        ['translate', 'rotate', 'scale'].forEach((candidate) => {
                            const button = document.getElementById(`gizmo-mode-${candidate}`);

                            if (! button) {
                                return;
                            }

                            const isActive = candidate === mode;
                            button.classList.toggle('is-active', isActive);
                            button.classList.remove(...gizmoModeButtonActiveClasses, ...gizmoModeButtonInactiveClasses);
                            button.classList.add(...(isActive ? gizmoModeButtonActiveClasses : gizmoModeButtonInactiveClasses));
                        });
                    }

                    window.addEventListener('spatial-editor-set-mode', (event) => {
                        const mode = event.detail.mode;

                        transformControls.setMode(mode);
                        // El gizmo de rotación de este editor solo expone el
                        // eje Y a propósito — es el único eje editable hoy
                        // en el panel de Propiedades (X/Z vienen `disabled`
                        // ahí también); mostrar círculos que no llevan a
                        // ningún lado sería confuso. `showX`/`showZ` son
                        // banderas compartidas entre modos, por eso se
                        // reafirman cada vez que cambia el modo (no solo en
                        // rotate) para no dejar las flechas de posición
                        // ocultas si se venía de rotar.
                        const restrictToYAxis = mode === 'rotate';
                        transformControls.showX = ! restrictToYAxis;
                        transformControls.showZ = ! restrictToYAxis;
                        setActiveGizmoModeButton(mode);
                    });

                    setActiveGizmoModeButton(transformControls.getMode());

                    function currentBounds() {
                        return sceneState.bounds ?? { minX: -50, maxX: 50, minZ: -50, maxZ: 50 };
                    }

                    function currentPlane() {
                        const bounds = currentBounds();

                        return sceneState.plane ?? {
                            centerX: (bounds.minX + bounds.maxX) / 2,
                            centerZ: (bounds.minZ + bounds.maxZ) / 2,
                            width: Math.max(1, bounds.maxX - bounds.minX),
                            depth: Math.max(1, bounds.maxZ - bounds.minZ),
                        };
                    }

                    function currentCenter() {
                        const plane = currentPlane();

                        return {
                            x: plane.centerX,
                            z: plane.centerZ,
                            width: Math.max(1, plane.width),
                            depth: Math.max(1, plane.depth),
                        };
                    }

                    function objectKey(type, id) {
                        return `${type}:${id}`;
                    }

                    // applyTiling() ahora vive en texture-tiling-utils.js
                    // (importado arriba) — compartido con
                    // dynamic-stand-loader.js para que el editor y la
                    // plaza inmersiva real nunca vuelvan a divergir (bug
                    // real de esta sesión: el editor la aplicaba pero la
                    // plaza pública nunca la leía, porque cada uno tenía
                    // su propia copia).

                    function registerObject(root, item) {
                        root.userData = {
                            type: item.type,
                            id: item.id,
                            x: item.position.x,
                            y: item.position.y,
                            z: item.position.z,
                            label: item.label,
                            locked: !! item.locked,
                        };

                        // Pedido del usuario: un objeto con candado debe
                        // poder seleccionarse haciendo clic sobre él en el
                        // visor 3D (no solo desde la lista lateral), para
                        // ver su gizmo aunque no se pueda mover — por eso
                        // entra a `draggables` igual que cualquier otro
                        // (ese array ya no controla si se puede arrastrar,
                        // desde que la traslación pasó a `TransformControls`
                        // — ver comentario de `snapToNeighbors`; el bloqueo
                        // real vive en `transformControls.enabled`, dentro
                        // de `focusSelection()`).
                        draggables.push(root);

                        objectIndex.set(objectKey(item.type, item.id), root);
                    }

                    function unregisterObject(type, id) {
                        const key = objectKey(type, id);
                        const root = objectIndex.get(key);

                        if (! root) {
                            return null;
                        }

                        objectIndex.delete(key);

                        const draggableIndex = draggables.indexOf(root);

                        if (draggableIndex >= 0) {
                            draggables.splice(draggableIndex, 1);
                        }

                        if (selectionHelper?.object === root) {
                            threeScene.remove(selectionHelper);
                            selectionHelper = null;
                        }

                        if (transformControls.object === root) {
                            transformControls.detach();
                        }

                        if (root.parent) {
                            root.parent.remove(root);
                        } else {
                            threeScene.remove(root);
                        }

                        return root;
                    }

                    function updateCameraFrame() {
                        const center = currentCenter();
                        const containerWidth = Math.max(container.clientWidth, 320);
                        const containerHeight = Math.max(container.clientHeight, 640);

                        camera.aspect = containerWidth / containerHeight;
                        camera.updateProjectionMatrix();
                        renderer.setSize(containerWidth, containerHeight);

                        if (! container.dataset.cameraReady) {
                            camera.position.set(center.x, Math.max(center.width, center.depth) * 0.9, center.z + Math.max(center.width, center.depth) * 0.6);
                            controls.target.set(center.x, 0, center.z);
                            controls.update();
                            container.dataset.cameraReady = 'true';
                        }
                    }

                    function buildGround() {
                        const plane = currentPlane();
                        const center = {
                            x: plane.centerX,
                            z: plane.centerZ,
                            width: Math.max(1, plane.width),
                            depth: Math.max(1, plane.depth),
                        };

                        if (ground) {
                            threeScene.remove(ground);
                            ground.geometry.dispose();
                        }

                        const groundGeometry = new THREE.PlaneGeometry(center.width, center.depth);
                        let groundMaterial;

                        if (sceneState.referenceImageUrl) {
                            const texture = new THREE.TextureLoader().load(sceneState.referenceImageUrl);
                            groundMaterial = new THREE.MeshStandardMaterial({ map: texture });
                        } else {
                            groundMaterial = new THREE.MeshStandardMaterial({ color: 0x9ca88a });
                        }

                        ground = new THREE.Mesh(groundGeometry, groundMaterial);
                        ground.rotation.x = -Math.PI / 2;
                        ground.position.set(center.x, 0, center.z);
                        ground.receiveShadow = true;
                        ground.userData = { plane };
                        threeScene.add(ground);
                    }

                    function drawBoundsOutline() {
                        const bounds = currentBounds();

                        boundsHandles.splice(0, boundsHandles.length);

                        if (boundsFill) {
                            threeScene.remove(boundsFill);
                        }

                        if (boundsOutline) {
                            threeScene.remove(boundsOutline);
                        }

                        const width = Math.max(0.1, bounds.maxX - bounds.minX);
                        const depth = Math.max(0.1, bounds.maxZ - bounds.minZ);
                        const centerX = (bounds.minX + bounds.maxX) / 2;
                        const centerZ = (bounds.minZ + bounds.maxZ) / 2;

                        boundsFill = new THREE.Mesh(
                            new THREE.PlaneGeometry(width, depth),
                            new THREE.MeshBasicMaterial({
                                color: 0x22c55e,
                                transparent: true,
                                opacity: 0.08,
                                side: THREE.DoubleSide,
                            })
                        );
                        boundsFill.rotation.x = -Math.PI / 2;
                        boundsFill.position.set(centerX, 0.03, centerZ);
                        threeScene.add(boundsFill);

                        const vertices = [
                            bounds.minX, 0.08, bounds.minZ,
                            bounds.maxX, 0.08, bounds.minZ,
                            bounds.maxX, 0.08, bounds.maxZ,
                            bounds.minX, 0.08, bounds.maxZ,
                            bounds.minX, 0.08, bounds.minZ,
                        ];
                        const geometry = new THREE.BufferGeometry();
                        geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
                        boundsOutline = new THREE.Line(
                            geometry,
                            new THREE.LineBasicMaterial({ color: 0x16a34a })
                        );
                        threeScene.add(boundsOutline);

                        const corners = [
                            { key: 'minX-minZ', x: bounds.minX, z: bounds.minZ },
                            { key: 'maxX-minZ', x: bounds.maxX, z: bounds.minZ },
                            { key: 'maxX-maxZ', x: bounds.maxX, z: bounds.maxZ },
                            { key: 'minX-maxZ', x: bounds.minX, z: bounds.maxZ },
                        ];

                        corners.forEach(({ key, x, z }) => {
                            const marker = new THREE.Mesh(
                                new THREE.BoxGeometry(0.6, 0.18, 0.6),
                                new THREE.MeshBasicMaterial({ color: 0x16a34a })
                            );
                            marker.position.set(x, 0.12, z);
                            marker.userData = { boundsHandle: true, key };
                            boundsHandles.push(marker);
                            boundsOutline.add(marker);
                        });
                    }

                    function syncBoundsToForm(bounds) {
                        $wire.set('boundsForm.minX', Number(bounds.minX.toFixed(3)));
                        $wire.set('boundsForm.maxX', Number(bounds.maxX.toFixed(3)));
                        $wire.set('boundsForm.minZ', Number(bounds.minZ.toFixed(3)));
                        $wire.set('boundsForm.maxZ', Number(bounds.maxZ.toFixed(3)));
                    }

                    function resizeBoundsFromHandle(handleKey, point) {
                        const nextBounds = { ...currentBounds() };
                        const minSize = 2;

                        if (handleKey.includes('minX')) {
                            nextBounds.minX = Math.min(point.x, nextBounds.maxX - minSize);
                        } else {
                            nextBounds.maxX = Math.max(point.x, nextBounds.minX + minSize);
                        }

                        if (handleKey.includes('minZ')) {
                            nextBounds.minZ = Math.min(point.z, nextBounds.maxZ - minSize);
                        } else {
                            nextBounds.maxZ = Math.max(point.z, nextBounds.minZ + minSize);
                        }

                        sceneState.bounds = nextBounds;
                        drawBoundsOutline();
                        syncBoundsToForm(nextBounds);
                    }

                    function drawPolygon(points, color, dashed = false) {
                        if (! points || points.length < 3) {
                            return;
                        }

                        const vertices = [...points, points[0]].flatMap((p) => [p.x, 0.05, p.z]);
                        const geometry = new THREE.BufferGeometry();
                        geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));

                        const material = dashed
                            ? new THREE.LineDashedMaterial({ color, dashSize: 1, gapSize: 0.5 })
                            : new THREE.LineBasicMaterial({ color });

                        const line = new THREE.Line(geometry, material);

                        if (dashed) {
                            line.computeLineDistances();
                        }

                        threeScene.add(line);
                    }

                    function focusSelection(root) {
                        if (selectionHelper) {
                            threeScene.remove(selectionHelper);
                            selectionHelper = null;
                        }

                        if (! root) {
                            transformControls.enabled = true;
                            transformControls.detach();

                            return;
                        }

                        // Pedido del usuario: un objeto con candado sigue
                        // mostrando el gizmo (para que se vea dónde está y
                        // qué modo tiene activo), pero no debe poder
                        // arrastrarse — `transformControls.enabled = false`
                        // deja el gizmo visible y attachado, solo bloquea
                        // la interacción de arrastre (Three.js ignora el
                        // pointerdown sobre sus ejes/planos cuando está
                        // deshabilitado, así que ni `dragging-changed` ni
                        // `objectChange` llegan a dispararse).
                        transformControls.enabled = ! root.userData?.locked;

                        selectionHelper = new THREE.BoxHelper(root, root.userData?.locked ? 0x9ca3af : 0xdc2626);
                        selectionHelper.object = root;
                        threeScene.add(selectionHelper);

                        // El modo Escalar solo aplica a elementos
                        // (`prop`) — un stand no tiene escala libre (ver
                        // comentario en `updatePropScale()`, backend). Si
                        // se venía escalando y se selecciona un slot/spawn,
                        // se cae de vuelta a Mover en vez de dejar un
                        // gizmo de escala activo sin forma de guardarlo.
                        if (transformControls.getMode() === 'scale' && root.userData?.type !== 'prop') {
                            window.dispatchEvent(new CustomEvent('spatial-editor-set-mode', { detail: { mode: 'translate' } }));
                        }

                        transformControls.attach(root);
                        // Livewire vuelve a renderizar el panel de
                        // Propiedades (y con él, los botones de modo) al
                        // seleccionar — reaplica el resaltado del modo
                        // actual sobre los botones recién morfeados.
                        setActiveGizmoModeButton(transformControls.getMode());
                    }

                    function clearSelection() {
                        focusSelection(null);
                        $wire.clearSelectedObject();
                    }

                    function findDraggableRoot(object) {
                        let current = object;

                        while (current && ! current.userData?.type) {
                            current = current.parent;
                        }

                        return current;
                    }

                    async function placeObject(item) {
                        // El punto de aparición se dibuja distinto (caja roja
                        // + flecha de dirección, no un modelo/voxel del
                        // catálogo), pero se registra igual que cualquier
                        // otro objeto para poder seleccionarlo/arrastrarlo
                        // con el mismo mecanismo.
                        if (item.type === 'spawn') {
                            const group = new THREE.Group();
                            group.position.set(item.position.x, item.position.y, item.position.z);
                            group.rotation.y = (item.rotation.y ?? 0) * (Math.PI / 180);

                            target.addVoxelBox({
                                x: 0,
                                y: Math.max(0.4, item.size.y / 2),
                                z: 0,
                                w: item.size.x,
                                h: item.size.y,
                                d: item.size.z,
                                texture: 'accent',
                                castShadow: false,
                                group,
                            });

                            group.add(new THREE.ArrowHelper(
                                new THREE.Vector3(0, 0, 1),
                                new THREE.Vector3(0, Math.max(0.8, item.size.y), 0),
                                2,
                                0xdc2626,
                            ));

                            registerObject(group, item);

                            return;
                        }

                        const group = await renderObjectByPriority(target, {
                            x: item.position.x,
                            z: item.position.z,
                            rotation: item.rotation.y ?? 0,
                            scale: item.scale,
                            modelUrl: item.modelUrl,
                            modelDefinition: item.modelDefinition,
                            builderKey: item.builderKey,
                        });

                        // Un slot sin negocio se nota más que un prop sin
                        // modelo (menos transparente) — pedido del usuario,
                        // porque a 0.45 se perdía de vista aunque el
                        // ancho/profundidad estuvieran bien configurados.
                        const root = group ?? target.addVoxelBox({
                            x: item.position.x,
                            y: Math.max(0.5, item.position.y + (item.size.y / 2)),
                            z: item.position.z,
                            w: item.size.x,
                            h: item.size.y,
                            d: item.size.z,
                            texture: 'stone',
                            opacity: item.type === 'slot' ? 0.65 : 0.45,
                            castShadow: false,
                        });

                        root.position.y = item.position.y;
                        root.rotation.y = (item.rotation.y ?? 0) * (Math.PI / 180);
                        registerObject(root, item);

                        if (item.type === 'prop' && item.tiling) {
                            applyTiling(root, item.tiling);
                        }

                        // Pedido del usuario: mostrar hacia dónde apunta el
                        // frente de un stand — mismo eje/convención que ya
                        // usa la flecha del punto de aparición un poco más
                        // arriba (`Vector3(0, 0, 1)` local, rotación Y en
                        // grados). Al agregarla como hija de `root`, hereda
                        // su rotación automáticamente: no hace falta
                        // recalcularla al arrastrar el gizmo de rotación.
                        if (item.type === 'slot') {
                            root.add(new THREE.ArrowHelper(
                                new THREE.Vector3(0, 0, 1),
                                new THREE.Vector3(0, Math.max(0.8, item.size.y) + 0.2, 0),
                                Math.min(2.5, Math.max(1.2, item.size.z * 0.6)),
                                0xf59e0b,
                            ));
                        }
                    }

                    async function rerenderObject(item) {
                        unregisterObject(item.type, item.id);
                        await placeObject(item);

                        const refreshedRoot = objectIndex.get(objectKey(item.type, item.id));

                        if (refreshedRoot) {
                            focusSelection(refreshedRoot);
                        }
                    }

                    function updatePointer(event) {
                        const rect = renderer.domElement.getBoundingClientRect();
                        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
                    }

                    threeScene.add(new THREE.AmbientLight(0xffffff, 0.8));
                    const sun = new THREE.DirectionalLight(0xffffff, 0.7);
                    sun.position.set(20, 40, 20);
                    threeScene.add(sun);
                    threeScene.add(target.world);

                    // Solo para este editor: referencia de cuadrícula y ejes
                    // X/Y/Z para ubicar objetos con precisión. La experiencia
                    // inmersiva real (VoxelPlazaEngine) nunca debe llevar
                    // estos helpers — son ayuda de edición, no parte de la
                    // escena jugable.
                    const gridHelper = new THREE.GridHelper(300, 60, 0x64748b, 0xcbd5e1);
                    gridHelper.position.y = 0.06;
                    threeScene.add(gridHelper);
                    threeScene.add(new THREE.AxesHelper(10));
                    threeScene.add(createAxisLabels(10));

                    buildGround();
                    drawBoundsOutline();

                    const zoneColors = [0x2563eb, 0x16a34a, 0xd97706, 0x9333ea, 0xdc2626, 0x0891b2];
                    (sceneState.zones ?? []).forEach((zone, index) => drawPolygon(zone.polygon?.points ?? [], zoneColors[index % zoneColors.length]));
                    (sceneState.excludedZones ?? []).forEach((excluded) => drawPolygon(excluded.points ?? [], 0xdc2626, true));
                    (sceneState.objects ?? []).forEach((item) => placeObject(item));

                    updateCameraFrame();

                    renderer.domElement.addEventListener('pointerdown', (event) => {
                        // El clic fue sobre el propio gizmo (TransformControls
                        // ya lo resolvió en su listener interno, registrado
                        // antes que este) — no reinterpretarlo como selección
                        // ni como deseleccionar por clic en vacío.
                        if (transformControls.dragging) {
                            return;
                        }

                        updatePointer(event);
                        raycaster.setFromCamera(pointer, camera);

                        const boundsHits = raycaster.intersectObjects(boundsHandles, false);

                        if (event.button === 0 && boundsHits.length > 0) {
                            draggingBoundsHandle = boundsHits[0].object.userData.key;
                            controls.enabled = false;

                            return;
                        }

                        const hits = raycaster.intersectObjects(draggables, true);

                        if (hits.length > 0) {
                            const root = findDraggableRoot(hits[0].object);

                            if (root?.userData?.type) {
                                $wire.selectObject(root.userData.type, root.userData.id);
                                focusSelection(root);
                            }
                        } else if (event.button === 0) {
                            clearSelection();
                        }
                    });

                    renderer.domElement.addEventListener('pointermove', (event) => {
                        if (! draggingBoundsHandle) {
                            return;
                        }

                        updatePointer(event);
                        raycaster.setFromCamera(pointer, camera);

                        const point = new THREE.Vector3();

                        if (raycaster.ray.intersectPlane(groundPlane, point)) {
                            resizeBoundsFromHandle(draggingBoundsHandle, point);
                        }
                    });

                    window.addEventListener('pointerup', () => {
                        if (! draggingBoundsHandle) {
                            return;
                        }

                        draggingBoundsHandle = null;
                        controls.enabled = true;
                    });

                    Livewire.on('spatial-editor-select', ({ type, id }) => {
                        focusSelection(objectIndex.get(objectKey(type, id)));

                        requestAnimationFrame(() => {
                            const listItem = document.getElementById('object-list-item-' + type + '-' + id);

                            listItem?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest',
                            });
                        });
                    });

                    Livewire.on('spatial-editor-object-updated', async ({ object }) => {
                        const payload = Array.isArray(object) ? object[0] : object;

                        if (! payload) {
                            return;
                        }

                        const objectArray = sceneState.objects ?? [];
                        const index = objectArray.findIndex((item) => item.type === payload.type && item.id === payload.id);

                        if (index >= 0) {
                            objectArray[index] = payload;
                        } else {
                            objectArray.push(payload);
                        }

                        await rerenderObject(payload);
                    });

                    Livewire.on('spatial-editor-object-removed', ({ type, id }) => {
                        const objectArray = sceneState.objects ?? [];
                        const index = objectArray.findIndex((item) => item.type === type && item.id === id);

                        if (index >= 0) {
                            objectArray.splice(index, 1);
                        }

                        unregisterObject(type, id);
                    });

                    Livewire.on('spatial-editor-history-restored', async ({ objects }) => {
                        // Deshacer/rehacer puede agregar o quitar elementos,
                        // no solo mover los existentes — reconstruir todo el
                        // visor es más simple y confiable que reconciliar
                        // objeto por objeto.
                        Array.from(objectIndex.keys()).forEach((key) => {
                            const [type, idStr] = key.split(':');
                            unregisterObject(type, Number(idStr));
                        });

                        sceneState.objects = objects ?? [];

                        for (const item of sceneState.objects) {
                            await placeObject(item);
                        }
                    });

                    Livewire.on('spatial-editor-settings-updated', ({ bounds, plane }) => {
                        sceneState.bounds = bounds ?? sceneState.bounds;
                        sceneState.plane = plane ?? sceneState.plane;

                        buildGround();
                        drawBoundsOutline();
                    });

                    Livewire.on('spatial-editor-tiling-preview', ({ type, id, tiling }) => {
                        const root = objectIndex.get(objectKey(type, id));
                        const source = (sceneState.objects ?? []).find((item) => item.type === type && item.id === id);

                        if (root && source) {
                            applyTiling(root, tiling);
                            // Sin esto, la vista previa se perdía si el
                            // objeto se reconstruía por CUALQUIER otro
                            // motivo antes de pulsar Guardar props (mover/
                            // rotar/escalar el mismo objeto, o cualquier
                            // otro evento que dispare `rerenderObject()`) —
                            // ese rebuild usa `item.tiling` de `sceneState`,
                            // que sin este ajuste seguía con el valor viejo
                            // porque solo Guardar props lo actualizaba.
                            source.tiling = tiling;
                        }
                    });

                    Livewire.on('spatial-editor-reject', ({ type, id }) => {
                        const object = objectIndex.get(objectKey(type, id));
                        const source = (sceneState.objects ?? []).find((item) => item.type === type && item.id === id);

                        if (object && source) {
                            object.position.x = source.position.x;
                            object.position.y = source.position.y;
                            object.position.z = source.position.z;
                            object.rotation.y = (source.rotationY ?? 0) * (Math.PI / 180);

                            if (source.scale) {
                                object.scale.set(source.scale.x ?? 1, source.scale.y ?? 1, source.scale.z ?? 1);
                            }

                            if (selectionHelper?.object === object) {
                                selectionHelper.update();
                            }
                        }
                    });

                    const resizeObserver = new ResizeObserver(updateCameraFrame);
                    resizeObserver.observe(container);

                    // Navegación con flechas/WASD — mismo mapeo de teclas que
                    // el motor del personaje inmersivo (voxel-plaza-engine.js,
                    // `setMovement`), solo por consistencia visual: este
                    // editor no usa `VoxelPlazaEngine`, es aditivo y aislado
                    // a este bloque. `container` (no `window`) recibe los
                    // eventos, y solo cuando tiene foco (`tabindex` en el
                    // div de arriba) — así nunca compite con las flechas que
                    // ya usan los inputs numéricos de Posición/Tamaño/
                    // Rotación en el panel de propiedades.
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
                        // No mover la cámara si el usuario está arrastrando
                        // un objeto (`controls.enabled` ya se apaga durante
                        // el drag, ver los toggles más arriba en este mismo
                        // bloque).
                        if (!controls.enabled) {
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
                        renderer.render(threeScene, camera);
                    }

                    animate();
                })
            "
        >
            <div
                id="plaza-spatial-editor-{{ $plaza->id }}"
                tabindex="0"
                style="width: 100%; min-height: 760px; border-radius: 0.75rem; overflow: hidden; background: #cfe8ff; outline: none;"
                onfocus="this.style.boxShadow = 'inset 0 0 0 2px #6366f1';"
                onblur="this.style.boxShadow = 'none';"
            ></div>
        </div>
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900 xl:order-1">
        <div>
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Opciones</h3>
        </div>

        <div class="mt-4 space-y-4">
            <div class="space-y-4">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agregar elementos</h4>
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="newPropTemplateId">
                            <option value="">Selecciona un elemento</option>
                            @foreach ($this->availablePropTemplates() as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <div class="grid grid-cols-2 gap-4">
                        <x-filament::button wire:click="addProp" size="sm" color="danger">
                            Objeto 3D
                        </x-filament::button>

                        <x-filament::button
                            wire:click="addSlot"
                            size="sm"
                            color="danger"
                            title="Un slot vacío (sin negocio) para dejarlo ubicado en la plaza. No es visible en la sección pública."
                        >
                            Slot stand
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Elementos de la plaza</h4>
                <div class="max-h-72 space-y-2 overflow-auto pr-1">
                    @forelse ($sceneData['objects'] ?? [] as $object)
                        <div
                            id="object-list-item-{{ $object['type'] }}-{{ $object['id'] }}"
                            @class([
                                'w-full rounded-lg border px-3 py-2 text-left text-sm transition',
                                'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $selectedObjectType === $object['type'] && $selectedObjectId === $object['id'],
                                'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200' => ! ($selectedObjectType === $object['type'] && $selectedObjectId === $object['id']),
                            ])
                        >
                            <div class="flex items-start justify-between gap-2">
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 text-left"
                                    wire:click="selectObject('{{ $object['type'] }}', {{ $object['id'] }})"
                                >
                                    <div class="font-medium">{{ $object['label'] }}</div>
                                    <div class="mt-1 text-xs opacity-70">X {{ number_format($object['position']['x'], 1) }} · Z {{ number_format($object['position']['z'], 1) }}</div>
                                </button>

                                <div class="flex flex-col items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="toggleObjectLock('{{ $object['type'] }}', {{ $object['id'] }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-primary-500/10 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400"
                                        title="{{ $object['locked'] ? 'Desbloquear elemento en el visor 3D' : 'Bloquear elemento en el visor 3D' }}"
                                    >
                                        <x-filament::icon :icon="$object['locked'] ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open'" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            No hay elementos en esta plaza todavía.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dimensiones de la imagen (px)</h4>
                <div class="grid grid-cols-2 gap-2">
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" step="any" wire:model.live="imageDimensionsForm.width" />
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" step="any" wire:model.live="imageDimensionsForm.height" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <x-filament::button color="danger" wire:click="saveSpatialSettings">
                Guardar configuración
            </x-filament::button>
        </div>
    </div>
</div>
