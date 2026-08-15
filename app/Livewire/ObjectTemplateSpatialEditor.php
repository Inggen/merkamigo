<?php

namespace App\Livewire;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelDefinitionValidationException;
use App\Domain\Immersive\Support\VoxelDefinitionBounds;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ObjectTemplateSpatialEditor extends Component
{
    private const MIN_DIMENSION = 0.001;

    private const DEFAULT_GLOW_COLOR = '#ffd873';

    /**
     * Tope arbitrario y conservador para no dejar crecer sin límite el
     * estado del componente (cada entrada es una definición completa de
     * cajas) — de sobra para una sesión de edición normal.
     */
    private const MAX_HISTORY = 20;

    public ImmersiveObjectTemplate $template;

    /** @var array<string, mixed> */
    public array $sceneData = [];

    public ?int $selectedBoxIndex = null;

    public bool $sizeLockEnabled = false;

    /** @var array<string, mixed> */
    public array $selectedBoxForm = [];

    /** @var array<string, float|int> */
    public array $sizeReference = [];

    /**
     * Pedido del usuario: agrupar cajas para moverlas/rotarlas/escalarlas
     * juntas con el gizmo, renombrar el grupo y bloquear/desbloquear todos
     * sus miembros a la vez. Igual que `$selectedBoxIndex`, mutuamente
     * excluyente con él — seleccionar una caja limpia el grupo y viceversa.
     */
    public ?string $selectedGroupId = null;

    /** @var array<string, mixed> */
    public array $selectedGroupForm = [];

    /**
     * Índices de cajas marcadas (checkbox en la lista, o Shift+clic en el
     * visor) para agruparlas con `createGroup()` — selección efímera, no
     * forma parte de la definición ni del historial de deshacer.
     *
     * @var array<int, int>
     */
    public array $selectedForGrouping = [];

    public string $maxWidthForm = '1';

    public string $maxDepthForm = '1';

    public string $maxHeightForm = '1';

    /** @var array<int, array<string, mixed>> */
    public array $undoStack = [];

    /** @var array<int, array<string, mixed>> */
    public array $redoStack = [];

    public function mount(ImmersiveObjectTemplate $template): void
    {
        $this->template = $template;
        $this->reloadSceneData();
    }

    public function render(): View
    {
        return view('livewire.object-template-spatial-editor', [
            'textureOptions' => VoxelDefinitionValidator::ALLOWED_TEXTURES,
        ]);
    }

    public function selectBox(int $index): void
    {
        $box = $this->findBoxData($index);

        if (! $box) {
            return;
        }

        $this->selectedGroupId = null;
        $this->selectedGroupForm = [];
        $this->selectedForGrouping = [];

        $this->selectedBoxIndex = $index;
        $this->selectedBoxForm = [
            'label' => $box['label'],
            'position' => $box['position'],
            'size' => $box['size'],
            'rotation' => $box['rotation'],
            'texture' => $box['texture'],
            'collidable' => (bool) $box['collidable'],
            'locked' => (bool) $box['locked'],
            'tiling' => $box['tiling'],
            'glowEnabled' => $box['emissive'] !== null,
            'glowColor' => $box['emissive'] ?? self::DEFAULT_GLOW_COLOR,
        ];
        $this->sizeReference = $box['size'];

        $this->dispatch('object-editor-select', index: $index);
    }

    public function clearSelectedBox(): void
    {
        $this->selectedBoxIndex = null;
        $this->selectedBoxForm = [];
        $this->sizeReference = [];
    }

    /**
     * Marca o desmarca una caja para agruparla — pedido del usuario. Cada
     * checkbox manda su nuevo estado explícito (`$event.target.checked`),
     * no "alternar la actual" (`wire:click="toggleX()"` calculando el
     * siguiente estado contra el último snapshot sincronizado): con varias
     * cajas marcadas rápido, dos peticiones en vuelo podían basarse en el
     * mismo snapshot viejo y una pisaba el resultado de la otra, perdiendo
     * marcas aunque los checkboxes se vieran marcados en pantalla (bug
     * reportado por el usuario). Mandar la intención explícita hace que
     * cada petición sea idempotente sin importar en qué orden lleguen.
     */
    public function setGroupingSelection(int $index, bool $selected): void
    {
        if ($selected) {
            if (! in_array($index, $this->selectedForGrouping, true)) {
                $this->selectedForGrouping[] = $index;
            }

            return;
        }

        $this->selectedForGrouping = array_values(array_diff($this->selectedForGrouping, [$index]));
    }

    /**
     * Pedido del usuario: agrupar varias cajas para editarlas juntas con el
     * gizmo. El reagrupamiento visual real (mover cada malla bajo un
     * `THREE.Group` temporal conservando su transform) lo hace el
     * navegador — aquí solo se anota el `groupId` en cada caja elegida y se
     * declara el grupo en `definition.groups`.
     */
    public function createGroup(): void
    {
        if (count($this->selectedForGrouping) < 2) {
            Notification::make()
                ->title('Selecciona al menos 2 cajas para agrupar')
                ->danger()
                ->send();

            return;
        }

        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];
        $groupId = (string) Str::random(10);

        // `selectedForGrouping` llega desde checkboxes HTML (`wire:model`
        // ligado al mismo array) — sus valores son strings aunque el
        // índice real de la caja sea entero.
        foreach ($this->selectedForGrouping as $index) {
            $index = (int) $index;

            if (isset($boxes[$index]) && is_array($boxes[$index])) {
                $boxes[$index]['groupId'] = $groupId;
            }
        }

        $definition['boxes'] = $boxes;
        $definition['groups'] = [
            ...($definition['groups'] ?? []),
            ['id' => $groupId, 'name' => 'Grupo '.(count($definition['groups'] ?? []) + 1)],
        ];

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $this->selectedForGrouping = [];
        $this->selectGroup($groupId);

        Notification::make()
            ->title('Grupo creado')
            ->success()
            ->send();
    }

    /**
     * Disuelve el grupo seleccionado — las cajas siguen existiendo tal cual
     * estaban, solo pierden su `groupId`.
     */
    public function ungroupSelected(): void
    {
        if ($this->selectedGroupId === null) {
            return;
        }

        $groupId = $this->selectedGroupId;
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        foreach ($boxes as $index => $box) {
            if (is_array($box) && ($box['groupId'] ?? null) === $groupId) {
                unset($boxes[$index]['groupId']);
            }
        }

        $definition['boxes'] = array_values($boxes);
        $definition['groups'] = collect($definition['groups'] ?? [])
            ->reject(fn (array $group): bool => ($group['id'] ?? null) === $groupId)
            ->values()
            ->all();

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $this->clearSelectedGroup();
        $this->dispatch('object-editor-select-group', groupId: null, boxIndices: []);

        Notification::make()
            ->title('Grupo disuelto')
            ->body('Las cajas siguen ahí, ya no están agrupadas.')
            ->success()
            ->send();
    }

    /**
     * Pedido del usuario: duplicar un grupo completo — copia todas sus
     * cajas (con el mismo desplazamiento +0.5 en X/Z que usa
     * `duplicateSelectedBox()`) dentro de un grupo NUEVO, dejando el
     * original intacto.
     */
    public function duplicateSelectedGroup(): void
    {
        if ($this->selectedGroupId === null) {
            return;
        }

        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];
        $sourceGroupId = $this->selectedGroupId;

        $memberBoxes = collect($boxes)
            ->filter(fn ($box): bool => is_array($box) && ($box['groupId'] ?? null) === $sourceGroupId)
            ->values();

        if ($memberBoxes->isEmpty()) {
            return;
        }

        $newGroupId = (string) Str::random(10);
        $sourceGroupName = collect($definition['groups'] ?? [])
            ->firstWhere('id', $sourceGroupId)['name'] ?? 'Grupo';

        $duplicatedBoxes = $memberBoxes->map(function (array $box) use ($newGroupId): array {
            $box['x'] = (float) ($box['x'] ?? 0) + 0.5;
            $box['z'] = (float) ($box['z'] ?? 0) + 0.5;
            // Mismo criterio que `duplicateSelectedBox()`: el duplicado
            // nace desbloqueado para que el admin pueda ajustarlo de
            // inmediato, aunque el original (o sus cajas) estén bloqueados.
            $box['locked'] = false;
            $box['groupId'] = $newGroupId;

            return $box;
        })->values()->all();

        $definition['boxes'] = [...$boxes, ...$duplicatedBoxes];
        $definition['groups'] = [
            ...($definition['groups'] ?? []),
            ['id' => $newGroupId, 'name' => "{$sourceGroupName} (copia)"],
        ];

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $this->selectGroup($newGroupId);

        Notification::make()
            ->title('Grupo duplicado')
            ->success()
            ->send();
    }

    public function selectGroup(string $groupId): void
    {
        $group = collect($this->sceneData['groups'] ?? [])->first(fn (array $group): bool => $group['id'] === $groupId);

        if (! $group) {
            return;
        }

        $this->selectedBoxIndex = null;
        $this->selectedBoxForm = [];
        $this->selectedForGrouping = [];

        $this->selectedGroupId = $groupId;
        $this->selectedGroupForm = ['name' => $group['name']];

        $this->dispatch('object-editor-select-group', groupId: $groupId, boxIndices: $group['boxIndices']);
    }

    public function clearSelectedGroup(): void
    {
        $this->selectedGroupId = null;
        $this->selectedGroupForm = [];
    }

    public function renameSelectedGroup(): void
    {
        if ($this->selectedGroupId === null) {
            return;
        }

        $name = trim((string) ($this->selectedGroupForm['name'] ?? ''));

        if ($name === '') {
            Notification::make()
                ->title('El grupo necesita un nombre')
                ->danger()
                ->send();

            return;
        }

        $definition = $this->definition();
        $definition['groups'] = collect($definition['groups'] ?? [])
            ->map(function (array $group) use ($name): array {
                if (($group['id'] ?? null) === $this->selectedGroupId) {
                    $group['name'] = $name;
                }

                return $group;
            })
            ->values()
            ->all();

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        Notification::make()
            ->title('Grupo renombrado')
            ->success()
            ->send();
    }

    /**
     * Candado del grupo — bloquea o desbloquea TODOS sus miembros de una
     * vez. Mismo criterio que `toggleBoxLock()`: no pasa por deshacer/
     * rehacer ni por la validación de bounds, bloquear no cambia geometría.
     * Si ALGUNO de los miembros ya está bloqueado, la próxima pulsación
     * bloquea el resto (en vez de exigir que estén todos en el mismo
     * estado para poder actuar).
     */
    public function toggleGroupLock(string $groupId): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];
        $memberIndices = collect($boxes)
            ->filter(fn ($box): bool => is_array($box) && ($box['groupId'] ?? null) === $groupId)
            ->keys();

        if ($memberIndices->isEmpty()) {
            return;
        }

        $allLocked = $memberIndices->every(fn (int $index): bool => (bool) ($boxes[$index]['locked'] ?? false));
        $nextState = ! $allLocked;

        foreach ($memberIndices as $index) {
            $boxes[$index]['locked'] = $nextState;
        }

        $definition['boxes'] = $boxes;

        $this->template->update([
            'model_definition' => $definition,
            'ai_draft_definition' => $definition,
        ]);

        $this->reloadSceneData();

        $group = collect($this->sceneData['groups'] ?? [])->first(fn (array $group): bool => $group['id'] === $groupId);

        $this->dispatch(
            'object-editor-select-group',
            groupId: $groupId,
            boxIndices: $group['boxIndices'] ?? [],
        );
    }

    public function addBox(): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        $boxes[] = [
            'x' => 0,
            'y' => 0.5,
            'z' => 0,
            'w' => 1,
            'h' => 1,
            'd' => 1,
            'texture' => 'stone',
            'rotationY' => 0,
            'collidable' => false,
        ];

        $definition['boxes'] = $boxes;

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $newIndex = count($boxes) - 1;
        $this->selectBox($newIndex);
        $this->dispatch('object-editor-box-added', definition: $this->sceneData['definition'], index: $newIndex);

        Notification::make()
            ->title('Caja agregada')
            ->success()
            ->send();
    }

    public function duplicateSelectedBox(): void
    {
        if ($this->selectedBoxIndex === null) {
            return;
        }

        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];
        $source = $boxes[$this->selectedBoxIndex] ?? null;

        if (! is_array($source)) {
            return;
        }

        $source['x'] = (float) ($source['x'] ?? 0) + 0.5;
        $source['z'] = (float) ($source['z'] ?? 0) + 0.5;
        // El duplicado nace desbloqueado aunque el original esté bloqueado
        // — mismo criterio que `duplicateProp()` en el editor de plaza,
        // para que el admin pueda ajustarlo de inmediato. Tampoco hereda el
        // grupo del original: agrupar es una acción explícita del usuario,
        // no algo que deba pasar solo por duplicar una caja.
        $source['locked'] = false;
        unset($source['groupId']);
        $boxes[] = $source;
        $definition['boxes'] = $boxes;

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $newIndex = count($boxes) - 1;
        $this->selectBox($newIndex);
        $this->dispatch('object-editor-box-added', definition: $this->sceneData['definition'], index: $newIndex);

        Notification::make()
            ->title('Caja duplicada')
            ->success()
            ->send();
    }

    public function deleteSelectedBox(): void
    {
        if ($this->selectedBoxIndex === null) {
            return;
        }

        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        if (! isset($boxes[$this->selectedBoxIndex])) {
            return;
        }

        unset($boxes[$this->selectedBoxIndex]);
        $boxes = array_values($boxes);

        if ($boxes === []) {
            Notification::make()
                ->title('Debe quedar al menos una caja')
                ->danger()
                ->send();

            return;
        }

        $definition['boxes'] = $boxes;

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        $removedIndex = $this->selectedBoxIndex;
        if (! $this->persistDefinition($definition)) {
            return;
        }

        $this->clearSelectedBox();
        $this->dispatch('object-editor-box-removed', index: $removedIndex, definition: $this->sceneData['definition']);

        Notification::make()
            ->title('Caja eliminada')
            ->success()
            ->send();
    }

    public function saveSelectedBox(): void
    {
        if ($this->selectedBoxIndex === null) {
            return;
        }

        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        if (! isset($boxes[$this->selectedBoxIndex])) {
            return;
        }

        $boxes[$this->selectedBoxIndex] = [
            'x' => (float) ($this->selectedBoxForm['position']['x'] ?? 0),
            'y' => (float) ($this->selectedBoxForm['position']['y'] ?? 0),
            'z' => (float) ($this->selectedBoxForm['position']['z'] ?? 0),
            'w' => max(self::MIN_DIMENSION, (float) ($this->selectedBoxForm['size']['x'] ?? self::MIN_DIMENSION)),
            'h' => max(self::MIN_DIMENSION, (float) ($this->selectedBoxForm['size']['y'] ?? self::MIN_DIMENSION)),
            'd' => max(self::MIN_DIMENSION, (float) ($this->selectedBoxForm['size']['z'] ?? self::MIN_DIMENSION)),
            'texture' => (string) ($this->selectedBoxForm['texture'] ?? 'stone'),
            'rotationX' => (float) ($this->selectedBoxForm['rotation']['x'] ?? 0),
            'rotationY' => (float) ($this->selectedBoxForm['rotation']['y'] ?? 0),
            'rotationZ' => (float) ($this->selectedBoxForm['rotation']['z'] ?? 0),
            'collidable' => (bool) ($this->selectedBoxForm['collidable'] ?? false),
            'locked' => (bool) ($this->selectedBoxForm['locked'] ?? false),
            'tiling' => [
                'u' => max(self::MIN_DIMENSION, (float) ($this->selectedBoxForm['tiling']['u'] ?? 1)),
                'v' => max(self::MIN_DIMENSION, (float) ($this->selectedBoxForm['tiling']['v'] ?? 1)),
            ],
            'emissive' => ($this->selectedBoxForm['glowEnabled'] ?? false)
                ? (string) ($this->selectedBoxForm['glowColor'] ?? self::DEFAULT_GLOW_COLOR)
                : null,
        ];

        $definition['boxes'] = array_values($boxes);

        if (! $this->validateCandidateDefinition($definition)) {
            return;
        }

        if (! $this->persistDefinition($definition)) {
            return;
        }

        $this->selectBox($this->selectedBoxIndex);
        $payload = $this->findBoxData($this->selectedBoxIndex);

        if ($payload) {
            $this->dispatch('object-editor-box-updated', box: $payload);
        }

        Notification::make()
            ->title('Caja actualizada')
            ->success()
            ->send();
    }

    /**
     * Gizmo en modo Mover (`TransformControls`, sin restricción de eje) —
     * pedido del usuario: mismas propiedades que el editor de plaza, pero
     * las cajas sí se mueven libre en Y (antes solo X/Z, arrastre casero
     * sobre un plano en el suelo).
     */
    public function updateBoxPosition(int $index, float $x, float $y, float $z): void
    {
        $this->applyBoxTransform($index, function (array &$box) use ($x, $y, $z): void {
            $box['x'] = $x;
            $box['y'] = $y;
            $box['z'] = $z;
        });
    }

    /**
     * Gizmo en modo Rotar — pedido del usuario: a diferencia del editor de
     * plaza (rotación restringida a Y), aquí no hay límite en ningún eje.
     */
    public function updateBoxRotation(int $index, float $x, float $y, float $z): void
    {
        $this->applyBoxTransform($index, function (array &$box) use ($x, $y, $z): void {
            $box['rotationX'] = $x;
            $box['rotationY'] = $y;
            $box['rotationZ'] = $z;
        });
    }

    /**
     * Gizmo en modo Escalar. Una caja no tiene un multiplicador de escala
     * separado como un `prop` de plaza (`scale_vector` sobre un modelo
     * base) — su tamaño ES `w`/`h`/`d`, así que "escalar" multiplica esas
     * dimensiones directamente por el factor que dejó el gizmo (que arranca
     * en 1 en cada intento, porque `renderDefinition()` siempre reconstruye
     * la caja desde `w`/`h`/`d` con escala 1).
     */
    public function updateBoxScale(int $index, float $scaleX, float $scaleY, float $scaleZ): void
    {
        $this->applyBoxTransform($index, function (array &$box) use ($scaleX, $scaleY, $scaleZ): void {
            $box['w'] = max(self::MIN_DIMENSION, (float) ($box['w'] ?? 1) * $scaleX);
            $box['h'] = max(self::MIN_DIMENSION, (float) ($box['h'] ?? 1) * $scaleY);
            $box['d'] = max(self::MIN_DIMENSION, (float) ($box['d'] ?? 1) * $scaleZ);
        });
    }

    /**
     * Gizmo aplicado a un GRUPO completo — pedido del usuario. El navegador
     * hace el reagrupamiento real: reparenta cada malla bajo un
     * `THREE.Group` temporal con `Object3D.attach()` (conserva el transform
     * mundial de cada caja), deja que el gizmo mueva/rote/escale ese grupo,
     * y al soltar ya manda aquí la posición/rotación/tamaño resuelto de
     * CADA caja — un solo `persistDefinition()` para todo el grupo, un solo
     * registro en el historial de deshacer (no uno por caja).
     *
     * @param  array<int, array<string, mixed>>  $updates  [{index, x, y, z, rotationX, rotationY, rotationZ, w?, h?, d?}, ...]
     */
    public function updateGroupBoxes(string $groupId, array $updates): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        foreach ($updates as $update) {
            $index = (int) ($update['index'] ?? -1);

            if (! isset($boxes[$index]) || ! is_array($boxes[$index])) {
                continue;
            }

            foreach (['x', 'y', 'z', 'rotationX', 'rotationY', 'rotationZ'] as $field) {
                if (array_key_exists($field, $update)) {
                    $boxes[$index][$field] = (float) $update[$field];
                }
            }

            foreach (['w', 'h', 'd'] as $field) {
                if (array_key_exists($field, $update)) {
                    $boxes[$index][$field] = max(self::MIN_DIMENSION, (float) $update[$field]);
                }
            }
        }

        $definition['boxes'] = $boxes;

        if (! $this->persistDefinition($definition)) {
            $this->dispatch('object-editor-reject', index: null);

            return;
        }

        $group = collect($this->sceneData['groups'] ?? [])->first(fn (array $group): bool => $group['id'] === $groupId);

        if ($group) {
            $this->dispatch('object-editor-select-group', groupId: $groupId, boxIndices: $group['boxIndices']);
        }
    }

    /**
     * @param  callable  $mutate  recibe la caja por referencia y la modifica in-place
     */
    private function applyBoxTransform(int $index, callable $mutate): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        if (! isset($boxes[$index]) || ! is_array($boxes[$index])) {
            return;
        }

        $mutate($boxes[$index]);
        $definition['boxes'] = array_values($boxes);

        if (! $this->persistDefinition($definition)) {
            $this->dispatch('object-editor-reject', index: $index);

            return;
        }

        if ($this->selectedBoxIndex === $index) {
            $this->selectBox($index);
        }

        $payload = $this->findBoxData($index);

        if ($payload) {
            $this->dispatch('object-editor-box-updated', box: $payload);
        }
    }

    /**
     * Candado para bloquear una caja en el visor — mismo comportamiento que
     * `toggleObjectLock()` del editor de plaza: no pasa por el historial de
     * deshacer/rehacer (bloquear/desbloquear no es una edición de
     * contenido) ni por la validación de bounds (no cambia geometría).
     */
    public function toggleBoxLock(int $index): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        if (! isset($boxes[$index]) || ! is_array($boxes[$index])) {
            return;
        }

        $boxes[$index]['locked'] = ! ($boxes[$index]['locked'] ?? false);
        $definition['boxes'] = $boxes;

        $this->template->update([
            'model_definition' => $definition,
            'ai_draft_definition' => $definition,
        ]);

        $this->reloadSceneData();

        $payload = $this->findBoxData($index);

        if ($payload) {
            $this->dispatch('object-editor-box-updated', box: $payload);
        }
    }

    public function toggleSizeLock(): void
    {
        $this->sizeLockEnabled = ! $this->sizeLockEnabled;
        $this->sizeReference = $this->selectedBoxForm['size'] ?? $this->sizeReference;
    }

    public function updated(string $name, mixed $value): void
    {
        // Vista previa en vivo del tiling mientras se escribe — mismo
        // patrón que `PlazaSpatialEditor`: el guardado real sigue
        // requiriendo el botón "Guardar props", esto solo refresca el
        // visor 3D antes.
        if (str_starts_with($name, 'selectedBoxForm.tiling.') && $this->selectedBoxIndex !== null) {
            $this->dispatch(
                'object-editor-tiling-preview',
                index: $this->selectedBoxIndex,
                tiling: [
                    'u' => (float) ($this->selectedBoxForm['tiling']['u'] ?? 1),
                    'v' => (float) ($this->selectedBoxForm['tiling']['v'] ?? 1),
                ],
            );
        }

        if (! $this->sizeLockEnabled || ! str_starts_with($name, 'selectedBoxForm.size.')) {
            return;
        }

        $axis = str($name)->after('selectedBoxForm.size.')->value();

        if (! in_array($axis, ['x', 'y', 'z'], true)) {
            return;
        }

        $currentSize = $this->selectedBoxForm['size'] ?? null;
        $referenceSize = $this->sizeReference;

        if (! is_array($currentSize) || blank($referenceSize)) {
            return;
        }

        $previousValue = (float) ($referenceSize[$axis] ?? 0);
        $nextValue = max(self::MIN_DIMENSION, (float) $value);

        if ($previousValue <= 0) {
            $this->sizeReference = $currentSize;

            return;
        }

        $ratio = $nextValue / $previousValue;

        foreach (['x', 'y', 'z'] as $otherAxis) {
            $base = (float) ($referenceSize[$otherAxis] ?? 0);

            if ($base <= 0) {
                continue;
            }

            $this->selectedBoxForm['size'][$otherAxis] = round(max(self::MIN_DIMENSION, $base * $ratio), 4);
        }

        $this->sizeReference = $this->selectedBoxForm['size'];
    }

    /**
     * Pedido del usuario: los campos de tamaño máximo son editables a mano
     * (no solo informativos) — sirven para reservar más huella de la que
     * las cajas actuales ocupan, o para achicarla antes de seguir editando.
     * Ya no se recalculan solos al cambiar las cajas (ver
     * `applyValidatedDefinition()`); solo cambian aquí o al pulsar el botón
     * "Ajustar al contenido" (`recalculateMaxSize()`).
     */
    public function updatedMaxWidthForm(mixed $value): void
    {
        $this->persistMaxDimension('max_width', $value);
    }

    public function updatedMaxDepthForm(mixed $value): void
    {
        $this->persistMaxDimension('max_depth', $value);
    }

    public function updatedMaxHeightForm(mixed $value): void
    {
        $this->persistMaxDimension('max_height', $value);
    }

    private function persistMaxDimension(string $column, mixed $value): void
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            $this->syncMaxSizeForm();

            return;
        }

        $this->template->update([$column => round((float) $value, 3)]);
        $this->reloadSceneData();

        $this->dispatch('object-editor-max-size-updated', maxSize: $this->sceneData['maxSize']);
    }

    private function reloadSceneData(): void
    {
        $this->template->refresh();
        $this->sceneData = $this->buildSceneData();
        $this->syncMaxSizeForm();
    }

    private function syncMaxSizeForm(): void
    {
        $this->maxWidthForm = $this->formatDimension($this->template->max_width);
        $this->maxDepthForm = $this->formatDimension($this->template->max_depth);
        $this->maxHeightForm = $this->formatDimension($this->template->max_height);
    }

    private function formatDimension(?float $value): string
    {
        return rtrim(rtrim(number_format(max(self::MIN_DIMENSION, (float) ($value ?? 1)), 3, '.', ''), '0'), '.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSceneData(): array
    {
        $definition = $this->definition();
        $boxes = collect($definition['boxes'] ?? [])
            ->values()
            ->map(fn (array $box, int $index): array => $this->describeBox($box, $index));

        // Pedido del usuario: agrupar cajas. Los grupos se listan a partir
        // de las cajas ya descritas (no del `groupId` crudo) para no
        // duplicar la lógica de qué caja pertenece a cuál — un grupo sin
        // ninguna caja apuntándolo (ej. se borró la última) simplemente no
        // aparece, aunque `pruneEmptyGroups()` ya debería haberlo limpiado.
        $groups = collect($definition['groups'] ?? [])
            ->map(function (array $group) use ($boxes): array {
                $members = $boxes->filter(fn (array $box): bool => ($box['groupId'] ?? null) === ($group['id'] ?? null));

                return [
                    'id' => (string) ($group['id'] ?? ''),
                    'name' => (string) ($group['name'] ?? 'Grupo'),
                    'boxIndices' => $members->pluck('index')->values()->all(),
                    'locked' => $members->isNotEmpty() && $members->every(fn (array $box): bool => $box['locked']),
                ];
            })
            ->filter(fn (array $group): bool => $group['boxIndices'] !== [])
            ->values();

        return [
            'definition' => $definition,
            'boxes' => $boxes->all(),
            'groups' => $groups->all(),
            'maxSize' => [
                'width' => max(self::MIN_DIMENSION, (float) $this->template->max_width),
                'depth' => max(self::MIN_DIMENSION, (float) $this->template->max_depth),
                'height' => max(self::MIN_DIMENSION, (float) $this->template->max_height),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $box
     * @return array<string, mixed>
     */
    private function describeBox(array $box, int $index): array
    {
        return [
            'index' => $index,
            'label' => 'Caja '.($index + 1),
            'texture' => (string) ($box['texture'] ?? 'stone'),
            'collidable' => (bool) ($box['collidable'] ?? false),
            'locked' => (bool) ($box['locked'] ?? false),
            'groupId' => $box['groupId'] ?? null,
            'tiling' => [
                'u' => (float) ($box['tiling']['u'] ?? 1),
                'v' => (float) ($box['tiling']['v'] ?? 1),
            ],
            'emissive' => $box['emissive'] ?? null,
            'position' => [
                'x' => (float) ($box['x'] ?? 0),
                'y' => (float) ($box['y'] ?? 0),
                'z' => (float) ($box['z'] ?? 0),
            ],
            'size' => [
                'x' => max(self::MIN_DIMENSION, (float) ($box['w'] ?? 1)),
                'y' => max(self::MIN_DIMENSION, (float) ($box['h'] ?? 1)),
                'z' => max(self::MIN_DIMENSION, (float) ($box['d'] ?? 1)),
            ],
            // Pedido del usuario: sin límite de rotación en X/Z (a
            // diferencia de `PlazaSpatialEditor`, que sí los fuerza a 0).
            'rotation' => [
                'x' => (float) ($box['rotationX'] ?? 0),
                'y' => (float) ($box['rotationY'] ?? 0),
                'z' => (float) ($box['rotationZ'] ?? 0),
            ],
            'x' => (float) ($box['x'] ?? 0),
            'y' => (float) ($box['y'] ?? 0),
            'z' => (float) ($box['z'] ?? 0),
            'rotationX' => (float) ($box['rotationX'] ?? 0),
            'rotationY' => (float) ($box['rotationY'] ?? 0),
            'rotationZ' => (float) ($box['rotationZ'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(): array
    {
        $definition = $this->template->ai_draft_definition ?? $this->template->model_definition;

        if (! is_array($definition) || ! isset($definition['boxes']) || ! is_array($definition['boxes'])) {
            return [
                'version' => 1,
                'boxes' => [[
                    'x' => 0,
                    'y' => 0.5,
                    'z' => 0,
                    'w' => 1,
                    'h' => 1,
                    'd' => 1,
                    'texture' => 'stone',
                    'rotationY' => 0,
                    'collidable' => false,
                ]],
            ];
        }

        $definition['version'] = 1;

        return $definition;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBoxData(int $index): ?array
    {
        return collect($this->sceneData['boxes'] ?? [])
            ->first(fn (array $box): bool => $box['index'] === $index);
    }

    /**
     * Pedido del usuario: deshacer/rehacer cambios de cajas. Cada llamada
     * que SÍ cambia algo (a diferencia de deshacer/rehacer en sí) guarda el
     * estado anterior en `undoStack` y vacía `redoStack` — un "rehacer"
     * pendiente deja de tener sentido en cuanto el admin sigue editando
     * desde aquí en vez de rehacer.
     *
     * @param  array<string, mixed>  $definition
     */
    private function persistDefinition(array $definition): bool
    {
        if ($this->validateDefinitionOrNotify($definition, 'No se pudo guardar el objeto') === null) {
            return false;
        }

        $this->pushHistory($this->undoStack, $this->definition());
        $this->redoStack = [];

        $this->applyValidatedDefinition($definition);

        return true;
    }

    /**
     * Reaplica una definición ya validada anteriormente (viene de
     * undo()/redo()) — no vuelve a tocar las pilas de historial, eso lo
     * maneja el llamador.
     *
     * @param  array<string, mixed>  $definition
     */
    private function restoreDefinition(array $definition): bool
    {
        if ($this->validateDefinitionOrNotify($definition, 'No se pudo restaurar ese estado') === null) {
            return false;
        }

        $this->applyValidatedDefinition($definition);

        return true;
    }

    /**
     * Valida y, de paso, confirma que las cajas caben dentro del tamaño
     * máximo actual de la plantilla (`VoxelDefinitionValidator` compara
     * contra `$this->template->max_*`) — el valor de retorno solo se usa
     * como señal de éxito/fracaso, el tamaño máximo ya no se recalcula a
     * partir de él (ver `applyValidatedDefinition()`).
     *
     * @param  array<string, mixed>  $definition
     * @return array{width: float, depth: float, height: float}|null
     */
    private function validateDefinitionOrNotify(array $definition, string $errorTitle): ?array
    {
        try {
            return (new VoxelDefinitionValidator)->validate($definition, $this->template);
        } catch (VoxelDefinitionValidationException $exception) {
            Notification::make()
                ->title($errorTitle)
                ->body(implode("\n", $exception->errors()))
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Pedido del usuario: el tamaño máximo ya NO se recalcula solo al
     * cambiar las cajas — queda como lo dejó el admin (a mano o con el
     * botón "Ajustar al contenido" de `recalculateMaxSize()`).
     *
     * @param  array<string, mixed>  $definition
     */
    private function applyValidatedDefinition(array $definition): void
    {
        $definition = $this->pruneEmptyGroups($definition);

        $this->template->update([
            'model_definition' => $definition,
            'ai_draft_definition' => $definition,
        ]);

        $this->reloadSceneData();
        $this->dispatch('object-editor-definition-updated', definition: $this->sceneData['definition']);
    }

    /**
     * Limpia entradas de `groups` que ya se quedaron sin ninguna caja
     * apuntándolas (ej. se borró la última caja del grupo, o se desagrupó
     * a mano pero algo dejó el registro suelto) — evita que se acumulen
     * metadatos huérfanos en `model_definition`. Corre después de validar,
     * así que solo QUITA entradas — nunca puede introducir una referencia
     * inválida nueva.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function pruneEmptyGroups(array $definition): array
    {
        $groups = $definition['groups'] ?? [];

        if ($groups === []) {
            return $definition;
        }

        $referencedIds = collect($definition['boxes'] ?? [])
            ->filter(fn ($box): bool => is_array($box) && filled($box['groupId'] ?? null))
            ->pluck('groupId')
            ->unique();

        $definition['groups'] = collect($groups)
            ->filter(fn (array $group): bool => $referencedIds->contains($group['id'] ?? null))
            ->values()
            ->all();

        return $definition;
    }

    /**
     * Pedido del usuario: el ajuste automático del tamaño máximo a la huella
     * real de las cajas deja de pasar solo — ahora requiere pulsar este
     * botón ("Ajustar al contenido" en Opciones).
     */
    public function recalculateMaxSize(): void
    {
        $bounds = VoxelDefinitionBounds::calculate($this->definition());

        $this->template->update([
            'max_width' => $bounds['width'],
            'max_depth' => $bounds['depth'],
            'max_height' => $bounds['height'],
        ]);

        $this->reloadSceneData();
        $this->dispatch('object-editor-max-size-updated', maxSize: $this->sceneData['maxSize']);

        Notification::make()
            ->title('Tamaño máximo ajustado a las cajas actuales')
            ->success()
            ->send();
    }

    /**
     * @param  array<int, array<string, mixed>>  $stack
     * @param  array<string, mixed>  $state
     */
    private function pushHistory(array &$stack, array $state): void
    {
        $stack[] = $state;
        $stack = array_slice($stack, -self::MAX_HISTORY);
    }

    public function canUndo(): bool
    {
        return $this->undoStack !== [];
    }

    public function canRedo(): bool
    {
        return $this->redoStack !== [];
    }

    public function undo(): void
    {
        if ($this->undoStack === []) {
            return;
        }

        $target = array_pop($this->undoStack);
        $current = $this->definition();

        if (! $this->restoreDefinition($target)) {
            $this->undoStack[] = $target;

            return;
        }

        $this->pushHistory($this->redoStack, $current);
        $this->clearSelectedBox();
    }

    public function redo(): void
    {
        if ($this->redoStack === []) {
            return;
        }

        $target = array_pop($this->redoStack);
        $current = $this->definition();

        if (! $this->restoreDefinition($target)) {
            $this->redoStack[] = $target;

            return;
        }

        $this->pushHistory($this->undoStack, $current);
        $this->clearSelectedBox();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function validateCandidateDefinition(array $definition): bool
    {
        try {
            (new VoxelDefinitionValidator)->validate($definition, $this->template);

            return true;
        } catch (VoxelDefinitionValidationException $exception) {
            Notification::make()
                ->title('No se pudo aplicar el cambio')
                ->body(implode("\n", $exception->errors()))
                ->danger()
                ->send();

            return false;
        }
    }
}
