<?php

namespace App\Livewire;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelDefinitionValidationException;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use Filament\Notifications\Notification;
use Illuminate\View\View;
use Livewire\Component;

class ObjectTemplateSpatialEditor extends Component
{
    private const MIN_DIMENSION = 0.001;

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

        $this->selectedBoxIndex = $index;
        $this->selectedBoxForm = [
            'label' => $box['label'],
            'position' => $box['position'],
            'size' => $box['size'],
            'rotation' => $box['rotation'],
            'texture' => $box['texture'],
            'collidable' => (bool) $box['collidable'],
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
            'rotationY' => (float) ($this->selectedBoxForm['rotation']['y'] ?? 0),
            'collidable' => (bool) ($this->selectedBoxForm['collidable'] ?? false),
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

    public function updateBoxPosition(int $index, float $x, float $z): void
    {
        $definition = $this->definition();
        $boxes = $definition['boxes'] ?? [];

        if (! isset($boxes[$index]) || ! is_array($boxes[$index])) {
            return;
        }

        $boxes[$index]['x'] = $x;
        $boxes[$index]['z'] = $z;
        $definition['boxes'] = array_values($boxes);

        if (! $this->validateCandidateDefinition($definition)) {
            $this->dispatch('object-editor-reject', index: $index);

            return;
        }

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

    public function toggleSizeLock(): void
    {
        $this->sizeLockEnabled = ! $this->sizeLockEnabled;
        $this->sizeReference = $this->selectedBoxForm['size'] ?? $this->sizeReference;
    }

    public function updated(string $name, mixed $value): void
    {
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
     * `persistDefinition()` los sigue recalculando automáticamente cada vez
     * que cambian las cajas, así que ambos caminos terminan aquí y
     * mantienen sincronizados el formulario y el recuadro rojo del visor.
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

        return [
            'definition' => $definition,
            'boxes' => collect($definition['boxes'] ?? [])
                ->values()
                ->map(fn (array $box, int $index): array => $this->describeBox($box, $index))
                ->all(),
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
            'rotation' => [
                'x' => 0.0,
                'y' => (float) ($box['rotationY'] ?? 0),
                'z' => 0.0,
            ],
            'x' => (float) ($box['x'] ?? 0),
            'y' => (float) ($box['y'] ?? 0),
            'z' => (float) ($box['z'] ?? 0),
            'rotationY' => (float) ($box['rotationY'] ?? 0),
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
        $bounds = $this->validateDefinitionOrNotify($definition, 'No se pudo guardar el objeto');

        if ($bounds === null) {
            return false;
        }

        $this->pushHistory($this->undoStack, $this->definition());
        $this->redoStack = [];

        $this->applyValidatedDefinition($definition, $bounds);

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
        $bounds = $this->validateDefinitionOrNotify($definition, 'No se pudo restaurar ese estado');

        if ($bounds === null) {
            return false;
        }

        $this->applyValidatedDefinition($definition, $bounds);

        return true;
    }

    /**
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
     * @param  array<string, mixed>  $definition
     * @param  array{width: float, depth: float, height: float}  $bounds
     */
    private function applyValidatedDefinition(array $definition, array $bounds): void
    {
        $this->template->update([
            'model_definition' => $definition,
            'ai_draft_definition' => $definition,
            'max_width' => $bounds['width'],
            'max_depth' => $bounds['depth'],
            'max_height' => $bounds['height'],
        ]);

        $this->reloadSceneData();
        $this->dispatch('object-editor-definition-updated', definition: $this->sceneData['definition']);
        // Las cajas también recalculan el tamaño máximo (huella real) — sin
        // este dispatch el recuadro rojo del visor se quedaba desactualizado
        // hasta tocar a mano un campo de tamaño máximo.
        $this->dispatch('object-editor-max-size-updated', maxSize: $this->sceneData['maxSize']);
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
