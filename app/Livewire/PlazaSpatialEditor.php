<?php

namespace App\Livewire;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\ImmersivePlazaProp;
use App\Domain\Immersive\Models\StandSlot;
use App\Domain\Immersive\Models\StandZone;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class PlazaSpatialEditor extends Component
{
    private const MIN_DIMENSION = 0.001;

    /** Ancho/profundidad por defecto de un slot creado desde `addSlot()`. */
    private const DEFAULT_SLOT_SIZE = 3.0;

    /**
     * Alto del marcador de un slot SIN plantilla/negocio (`describeSlot()`
     * usa esto cuando no hay `stand_template_id`). Antes era 1m — se veía
     * como una plancha delgada, difícil de notar aunque ancho/profundidad
     * estuvieran bien configurados. 2.2m se acerca más a un puesto real.
     */
    private const DEFAULT_SLOT_HEIGHT = 2.2;

    /**
     * `StandSlot.stand_zone_id` es obligatorio y el sistema de asignación
     * de negocios (`StandAssignment`) depende de que exista una zona, pero
     * el usuario pidió que agregar un slot sea tan directo como agregar
     * cualquier otro objeto — sin elegir/crear una zona a mano. Esta es el
     * nombre de la zona invisible que se autogenera una sola vez por
     * plaza (ver `defaultStandZone()`) para cubrir ese requisito sin que
     * el admin la vea ni la elija nunca.
     */
    private const AUTO_ZONE_NAME = 'Zona general (automática)';

    /**
     * Separación (borde a borde, en metros) entre un slot y su copia al
     * duplicarlo — pedido explícito del usuario. Un simple "+1" en X/Z
     * (como usa `duplicateProp()`, sin restricciones geométricas) no
     * alcanza aquí: `StandSlot` valida solapamiento contra sus vecinos
     * (`validateAgainstZoneTemplateAndNeighbors()`), así que un offset
     * pequeño rechazaba la duplicación casi siempre.
     */
    private const SLOT_DUPLICATE_GAP = 2.0;

    /**
     * Tope arbitrario y conservador para no dejar crecer sin límite el
     * estado del componente (cada entrada es una foto completa de
     * stands + elementos + configuración de la plaza).
     */
    private const MAX_HISTORY = 20;

    private static ?bool $supportsScaleVector = null;

    private static ?bool $supportsCollisionEnabled = null;

    private static ?bool $supportsTiling = null;

    public ImmersivePlaza $plaza;

    /** @var array<string, mixed> */
    public array $sceneData = [];

    public ?string $selectedObjectType = null;

    public ?int $selectedObjectId = null;

    public bool $sizeLockEnabled = false;

    /** @var array<string, mixed> */
    public array $selectedObjectForm = [];

    /** @var array<string, float|int> */
    public array $sizeReference = [];

    /** @var array<string, float|int> */
    public array $boundsForm = [];

    /** @var array<string, float|int|null> */
    public array $imageDimensionsForm = [];

    public ?int $newPropTemplateId = null;

    /**
     * Identifica el historial de undo/redo de esta instancia del editor en
     * caché — no las pilas en sí. Guardar las fotos completas (stands +
     * elementos + configuración) como propiedades públicas de Livewire hacía
     * que se reenviaran enteras en cada petición, disparando
     * `PayloadTooLargeException` al mover elementos. Con solo este token
     * público, el payload de cada petición vuelve a ser pequeño.
     */
    public string $historyToken = '';

    public function mount(ImmersivePlaza $plaza): void
    {
        $this->plaza = $plaza;
        $this->historyToken = (string) Str::random(32);
        $this->syncFormsFromPlaza();
        $this->reloadSceneData();
    }

    public function selectObject(string $type, int $id): void
    {
        $object = $this->findObjectData($type, $id);

        if (! $object) {
            return;
        }

        $this->selectedObjectType = $type;
        $this->selectedObjectId = $id;
        $this->selectedObjectForm = [
            'label' => $object['label'],
            'position' => $object['position'],
            'size' => $object['size'],
            'rotation' => $object['rotation'],
            'collisionEnabled' => (bool) ($object['collisionEnabled'] ?? false),
            'hasGlbModel' => (bool) ($object['hasGlbModel'] ?? false),
            'objectEditorUrl' => $object['objectEditorUrl'] ?? null,
            'status' => $object['status'] ?? null,
            'tiling' => $object['tiling'] ?? null,
        ];
        $this->sizeReference = $object['size'];

        $this->dispatch('spatial-editor-select', type: $type, id: $id);
    }

    public function clearSelectedObject(): void
    {
        $this->selectedObjectType = null;
        $this->selectedObjectId = null;
        $this->selectedObjectForm = [];
        $this->sizeReference = [];
    }

    public function toggleSizeLock(): void
    {
        $this->sizeLockEnabled = ! $this->sizeLockEnabled;
        $this->sizeReference = $this->selectedObjectForm['size'] ?? $this->sizeReference;
    }

    public function saveSelectedObject(): void
    {
        if (! $this->selectedObjectType || $this->selectedObjectId === null) {
            return;
        }

        if ($this->selectedObjectType === 'slot') {
            $this->saveSelectedSlot();

            return;
        }

        if ($this->selectedObjectType === 'spawn') {
            $this->saveSelectedSpawn();

            return;
        }

        $this->saveSelectedProp();
    }

    /**
     * Antes esto vivía solo en `$lockedObjectKeys`, una propiedad Livewire
     * en memoria — por eso el candado siempre volvía a aparecer abierto al
     * recargar la página (bug reportado por el usuario). Ahora persiste en
     * una columna real (`locked`) en `stand_slots`/`immersive_plaza_props`,
     * o dentro del JSON `spawn_point` para el punto de aparición, que no
     * tiene fila propia.
     */
    public function toggleObjectLock(string $type, int $id): void
    {
        if ($type === 'slot') {
            $slot = StandSlot::query()->with('zone')->find($id);

            if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
                return;
            }

            try {
                $slot->update(['locked' => ! $slot->locked]);
            } catch (ValidationException) {
                return;
            }
        } elseif ($type === 'prop') {
            $prop = ImmersivePlazaProp::query()->find($id);

            if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
                return;
            }

            $prop->update(['locked' => ! $prop->locked]);
        } elseif ($type === 'spawn') {
            $spawn = $this->plaza->spawn_point ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0];

            $this->plaza->update([
                'spawn_point' => [
                    'x' => (float) $spawn['x'],
                    'y' => (float) $spawn['y'],
                    'z' => (float) $spawn['z'],
                    'rotationY' => (float) $spawn['rotationY'],
                    'locked' => ! ($spawn['locked'] ?? false),
                ],
            ]);
        } else {
            return;
        }

        $this->reloadSceneData();

        $payload = $this->findObjectData($type, $id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }
    }

    public function saveSpatialSettings(): void
    {
        $this->commitHistorySnapshot($this->captureSnapshot());

        $this->plaza->update([
            'navigable_bounds' => [
                'minX' => (float) ($this->boundsForm['minX'] ?? -50),
                'maxX' => (float) ($this->boundsForm['maxX'] ?? 50),
                'minZ' => (float) ($this->boundsForm['minZ'] ?? -50),
                'maxZ' => (float) ($this->boundsForm['maxZ'] ?? 50),
            ],
            'reference_image_width' => filled($this->imageDimensionsForm['width'] ?? null)
                ? (float) $this->imageDimensionsForm['width']
                : null,
            'reference_image_height' => filled($this->imageDimensionsForm['height'] ?? null)
                ? (float) $this->imageDimensionsForm['height']
                : null,
        ]);

        $this->plaza->refresh();
        $this->reloadSceneData();

        $this->dispatch(
            'spatial-editor-settings-updated',
            bounds: $this->sceneData['bounds'],
            plane: $this->sceneData['plane'],
        );

        Notification::make()
            ->title('Configuración espacial guardada')
            ->success()
            ->send();
    }

    /**
     * Arrastrar el punto de aparición en el visor (igual que un stand o un
     * elemento) mueve X/Z. No hay validación posible de rechazar aquí — es
     * un campo JSON simple de la plaza, sin reglas de zona/solapamiento —
     * así que no hace falta el patrón de "capturar antes, confirmar solo si
     * no falla" que sí necesitan los stands.
     */
    public function updateSpawnPosition(float $x, float $y, float $z): void
    {
        $this->commitHistorySnapshot($this->captureSnapshot());

        $spawn = $this->plaza->spawn_point ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0];

        $this->plaza->update([
            'spawn_point' => [
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'rotationY' => (float) $spawn['rotationY'],
                'locked' => (bool) ($spawn['locked'] ?? false),
            ],
        ]);

        $this->afterObjectSaved('spawn', -1);
    }

    public function updateSpawnRotation(float $y): void
    {
        $this->commitHistorySnapshot($this->captureSnapshot());

        $spawn = $this->plaza->spawn_point ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0];

        $this->plaza->update([
            'spawn_point' => [
                'x' => (float) $spawn['x'],
                'y' => (float) $spawn['y'],
                'z' => (float) $spawn['z'],
                'rotationY' => $y,
                'locked' => (bool) ($spawn['locked'] ?? false),
            ],
        ]);

        $this->afterObjectSaved('spawn', -1);
    }

    /**
     * El punto de aparición se edita con el mismo panel de Propiedades que
     * un stand o un elemento (mismo pedido del usuario: "debe tener las
     * mismas propiedades de los demás objetos"), pero solo expone Posición
     * X/Y/Z y Rotación Y — es lo único que el personaje realmente usa
     * (`ImmersivePlaza::spawn_point`); no tiene ancho/alto/profundidad ni
     * se inclina en X/Z.
     */
    private function saveSelectedSpawn(): void
    {
        $this->commitHistorySnapshot($this->captureSnapshot());

        $spawn = $this->plaza->spawn_point ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0];

        $this->plaza->update([
            'spawn_point' => [
                'x' => (float) ($this->selectedObjectForm['position']['x'] ?? 0),
                'y' => (float) ($this->selectedObjectForm['position']['y'] ?? 0),
                'z' => (float) ($this->selectedObjectForm['position']['z'] ?? 0),
                'rotationY' => (float) ($this->selectedObjectForm['rotation']['y'] ?? 0),
                'locked' => (bool) ($spawn['locked'] ?? false),
            ],
        ]);

        $this->afterObjectSaved('spawn', -1);

        Notification::make()
            ->title('Punto de aparición actualizado')
            ->success()
            ->send();
    }

    public function updateSlotPosition(int $slotId, float $x, float $y, float $z): void
    {
        $slot = StandSlot::query()->with('template', 'zone')->find($slotId);

        if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        // Se captura ANTES de intentar mover, pero solo se confirma en el
        // historial si el movimiento realmente se aplica — si la
        // validación lo rechaza no hay nada que deshacer.
        $snapshot = $this->captureSnapshot();

        try {
            $slot->update([
                'world_position' => [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z,
                ],
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('spatial-editor-reject', type: 'slot', id: $slotId);

            Notification::make()
                ->title('No se pudo mover el stand')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('slot', $slot->id);
    }

    /**
     * Rotación desde el gizmo (círculo de eje Y, `TransformControls` en
     * modo `rotate`, snap de 45° aplicado del lado del cliente) — mismo
     * patrón de captura/validar/revertir que `updateSlotPosition`. Solo
     * recibe `y`: X/Z de rotación no son editables todavía en ningún punto
     * de este editor (ver panel de Propiedades, esos inputs vienen
     * `disabled`), así que se preservan tal cual estaban.
     */
    public function updateSlotRotation(int $slotId, float $y): void
    {
        $slot = StandSlot::query()->with('template', 'zone')->find($slotId);

        if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();
        $rotation = $this->normalizeRotation($slot->rotation);

        try {
            $slot->update([
                'rotation' => [
                    'x' => $rotation['x'],
                    'y' => $y,
                    'z' => $rotation['z'],
                ],
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('spatial-editor-reject', type: 'slot', id: $slotId);

            Notification::make()
                ->title('No se pudo rotar el stand')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('slot', $slot->id);
    }

    public function updatePropPosition(int $propId, float $x, float $y, float $z): void
    {
        $prop = ImmersivePlazaProp::query()->with('template')->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();

        try {
            $prop->update([
                'world_position' => [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('spatial-editor-reject', type: 'prop', id: $propId);

            Notification::make()
                ->title('No se pudo mover el elemento')
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('prop', $prop->id);
    }

    /**
     * Mismo mecanismo que `updateSlotRotation()` — ver ese comentario.
     */
    public function updatePropRotation(int $propId, float $y): void
    {
        $prop = ImmersivePlazaProp::query()->with('template')->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();
        $rotation = $this->normalizeRotation($prop->rotation);

        try {
            $prop->update([
                'rotation' => [
                    'x' => $rotation['x'],
                    'y' => $y,
                    'z' => $rotation['z'],
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('spatial-editor-reject', type: 'prop', id: $propId);

            Notification::make()
                ->title('No se pudo rotar el elemento')
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('prop', $prop->id);
    }

    /**
     * Gizmo de escala (`TransformControls` en modo `scale`) — solo para
     * elementos (`prop`), nunca stands: un stand no tiene escala libre, su
     * tamaño sale de `max_width`/`max_depth` planas más la altura fija de
     * la plantilla (ver panel de Propiedades, "la altura se muestra pero
     * se conserva desde la plantilla actual"). El botón "Escalar" del
     * visor ni siquiera se muestra para slots/spawn.
     */
    public function updatePropScale(int $propId, float $x, float $y, float $z): void
    {
        if (! $this->supportsScaleVector()) {
            return;
        }

        $prop = ImmersivePlazaProp::query()->with('template')->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();
        $scaleVector = [
            'x' => max(self::MIN_DIMENSION, $x),
            'y' => max(self::MIN_DIMENSION, $y),
            'z' => max(self::MIN_DIMENSION, $z),
        ];

        try {
            $prop->update([
                'scale_vector' => $scaleVector,
                'scale' => ($scaleVector['x'] + $scaleVector['y'] + $scaleVector['z']) / 3,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('spatial-editor-reject', type: 'prop', id: $propId);

            Notification::make()
                ->title('No se pudo escalar el elemento')
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('prop', $prop->id);
    }

    public function render(): View
    {
        return view('livewire.plaza-spatial-editor');
    }

    public function updated(string $name, mixed $value): void
    {
        // Vista previa en vivo del tiling mientras se escribe — el guardado
        // real sigue requiriendo el botón "Guardar props" (mismo patrón que
        // posición/tamaño/rotación), esto solo refresca el visor 3D antes.
        if (str_starts_with($name, 'selectedObjectForm.tiling.') && $this->selectedObjectType === 'prop' && $this->selectedObjectId !== null) {
            $this->dispatch(
                'spatial-editor-tiling-preview',
                type: 'prop',
                id: $this->selectedObjectId,
                tiling: [
                    'u' => (float) ($this->selectedObjectForm['tiling']['u'] ?? 1),
                    'v' => (float) ($this->selectedObjectForm['tiling']['v'] ?? 1),
                ],
            );
        }

        if (! $this->sizeLockEnabled) {
            return;
        }

        if (! str_starts_with($name, 'selectedObjectForm.size.')) {
            return;
        }

        $axis = str($name)->after('selectedObjectForm.size.')->value();

        if (! in_array($axis, ['x', 'y', 'z'], true)) {
            return;
        }

        $currentSize = $this->selectedObjectForm['size'] ?? null;
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
            if ($otherAxis === $axis) {
                $this->selectedObjectForm['size'][$otherAxis] = $nextValue;

                continue;
            }

            if ($this->selectedObjectType === 'slot' && $otherAxis === 'y') {
                continue;
            }

            $base = (float) ($referenceSize[$otherAxis] ?? 0);

            if ($base <= 0) {
                continue;
            }

            $this->selectedObjectForm['size'][$otherAxis] = round(max(self::MIN_DIMENSION, $base * $ratio), 4);
        }

        $this->sizeReference = $this->selectedObjectForm['size'];
    }

    public function addProp(): void
    {
        if (! $this->newPropTemplateId) {
            Notification::make()
                ->title('Selecciona un elemento del catálogo')
                ->danger()
                ->send();

            return;
        }

        $template = ImmersiveObjectTemplate::query()
            ->whereKey($this->newPropTemplateId)
            ->where('category', '!=', 'stand')
            ->first();

        if (! $template) {
            Notification::make()
                ->title('El elemento seleccionado no es válido')
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($this->captureSnapshot());

        $prop = $this->plaza->props()->create([
            'object_template_id' => $template->id,
            'world_position' => $this->defaultPropPosition(),
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'scale' => 1,
            'scale_vector' => $this->supportsScaleVector() ? ['x' => 1, 'y' => 1, 'z' => 1] : null,
            'collision_enabled' => false,
            'status' => 'borrador',
            'source' => 'manual',
        ]);

        $this->reloadSceneData();
        $this->selectObject('prop', $prop->id);
        $payload = $this->findObjectData('prop', $prop->id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }

        Notification::make()
            ->title('Elemento agregado')
            ->success()
            ->send();
    }

    /**
     * Pedido del usuario: poder agregar slots vacíos (sin negocio) desde el
     * editor espacial, tan directo como agregar cualquier otro objeto —
     * sin elegir/crear una zona a mano (`defaultStandZone()` se encarga
     * de eso por detrás). Igual que `addProp()`, pero creando un
     * `StandSlot` en vez de un `ImmersivePlazaProp`. Un slot sin
     * asignación activa ya es invisible para el público por diseño
     * (`ImmersivePlazaStandsController` solo expone slots con
     * `assignment->isLive()`), así que no hace falta un campo de estado
     * "borrador" aparte como en los props.
     */
    public function addSlot(): void
    {
        $zone = $this->defaultStandZone();

        $snapshot = $this->captureSnapshot();

        try {
            $slot = $zone->slots()->create([
                'code' => $this->nextSlotCode(),
                'world_position' => $this->randomPositionWithinZone($zone, self::DEFAULT_SLOT_SIZE / 2),
                'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
                'max_width' => self::DEFAULT_SLOT_SIZE,
                'max_depth' => self::DEFAULT_SLOT_SIZE,
                'status' => 'disponible',
                'source' => 'manual',
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('No se pudo agregar el slot')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->reloadSceneData();
        $this->selectObject('slot', $slot->id);

        $payload = $this->findObjectData('slot', $slot->id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }

        Notification::make()
            ->title('Slot agregado')
            ->body('Todavía no tiene negocio asignado — no se ve en la versión pública hasta que se le asigne uno.')
            ->success()
            ->send();
    }

    public function deleteProp(int $propId): void
    {
        $prop = ImmersivePlazaProp::query()->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $this->commitHistorySnapshot($this->captureSnapshot());

        $prop->delete();

        if ($this->selectedObjectType === 'prop' && $this->selectedObjectId === $propId) {
            $this->clearSelectedObject();
        }

        $this->reloadSceneData();
        $this->dispatch('spatial-editor-object-removed', type: 'prop', id: $propId);

        Notification::make()
            ->title('Elemento eliminado')
            ->success()
            ->send();
    }

    /**
     * Un elemento nuevo queda en "borrador" (`addProp()`) y solo se ve con
     * `?preview=1` — antes, la única forma de pasarlo a "confirmado" (y que
     * el público lo vea) era el recurso Filament "Elementos de plaza",
     * fila por fila. Publicar la experiencia también confirma en bloque
     * (`ImmersiveExperience::publish()`), pero este botón cubre el caso de
     * querer confirmar un elemento puntual sin republicar toda la plaza.
     */
    public function confirmProp(int $propId): void
    {
        $prop = ImmersivePlazaProp::query()->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id || $prop->status === 'confirmado') {
            return;
        }

        $this->commitHistorySnapshot($this->captureSnapshot());

        $prop->update(['status' => 'confirmado']);

        if ($this->selectedObjectType === 'prop' && $this->selectedObjectId === $propId) {
            $this->selectedObjectForm['status'] = 'confirmado';
        }

        $this->reloadSceneData();

        Notification::make()
            ->title('Elemento confirmado')
            ->body('Ya es visible para el público, sin necesidad de vista previa.')
            ->success()
            ->send();
    }

    public function duplicateProp(int $propId): void
    {
        $prop = ImmersivePlazaProp::query()->with('template')->find($propId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $this->commitHistorySnapshot($this->captureSnapshot());

        $duplicate = $this->plaza->props()->create([
            'object_template_id' => $prop->object_template_id,
            'world_position' => [
                'x' => (float) ($prop->world_position['x'] ?? 0) + 1,
                'y' => (float) ($prop->world_position['y'] ?? 0),
                'z' => (float) ($prop->world_position['z'] ?? 0) + 1,
            ],
            'rotation' => $prop->rotation,
            'scale' => $prop->scale,
            'scale_vector' => $this->supportsScaleVector() ? ($prop->scale_vector ?? ['x' => 1, 'y' => 1, 'z' => 1]) : null,
            'collision_enabled' => (bool) $prop->collision_enabled,
            'status' => $prop->status,
            'source' => 'manual',
        ]);

        $this->reloadSceneData();
        $this->selectObject('prop', $duplicate->id);
        $payload = $this->findObjectData('prop', $duplicate->id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }

        Notification::make()
            ->title('Elemento duplicado')
            ->success()
            ->send();
    }

    /**
     * Mismo patrón que duplicateProp() — ver ese comentario. El duplicado
     * nace sin asignación de negocio (`StandAssignment` es una relación
     * aparte, nunca se copia con `create()`), así que su estado siempre
     * arranca en "disponible" igual que `addSlot()`, sin importar el
     * estado del slot original (evita duplicar un slot "ocupado" sin que
     * en realidad tenga un negocio asignado).
     */
    public function duplicateSlot(int $slotId): void
    {
        $slot = StandSlot::query()->with('zone')->find($slotId);

        if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();

        try {
            $duplicate = $slot->zone->slots()->create([
                'code' => $this->nextSlotCode(),
                'stand_template_id' => $slot->stand_template_id,
                'allowed_category_id' => $slot->allowed_category_id,
                'world_position' => [
                    // Desplazamiento solo en X: el duplicado comparte el
                    // mismo ancho (`max_width`), así que separar los
                    // centros por `max_width + SLOT_DUPLICATE_GAP` dejando
                    // Z igual garantiza exactamente 2m libres entre sus
                    // bordes, sin importar la profundidad de ninguno.
                    'x' => (float) ($slot->world_position['x'] ?? 0) + $slot->max_width + self::SLOT_DUPLICATE_GAP,
                    'y' => (float) ($slot->world_position['y'] ?? 0),
                    'z' => (float) ($slot->world_position['z'] ?? 0),
                ],
                'rotation' => $slot->rotation,
                'max_width' => $slot->max_width,
                'max_depth' => $slot->max_depth,
                'orientation_mode' => $slot->orientation_mode,
                'accessible' => $slot->accessible,
                'status' => 'disponible',
                'source' => 'manual',
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('No se pudo duplicar el stand')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->reloadSceneData();
        $this->selectObject('slot', $duplicate->id);

        $payload = $this->findObjectData('slot', $duplicate->id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }

        Notification::make()
            ->title('Stand duplicado')
            ->body('Todavía no tiene negocio asignado.')
            ->success()
            ->send();
    }

    /**
     * Mismo patrón que deleteProp(). `stand_slot_id` en `stand_assignments`
     * es `nullOnDelete()` (ver migración), así que si el slot tiene un
     * negocio asignado la asignación no se borra en cascada — queda huérfana
     * (sin slot) en vez de romper por FK.
     */
    public function deleteSlot(int $slotId): void
    {
        $slot = StandSlot::query()->with('zone')->find($slotId);

        if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $this->commitHistorySnapshot($this->captureSnapshot());

        $slot->delete();

        if ($this->selectedObjectType === 'slot' && $this->selectedObjectId === $slotId) {
            $this->clearSelectedObject();
        }

        $this->reloadSceneData();
        $this->dispatch('spatial-editor-object-removed', type: 'slot', id: $slotId);

        Notification::make()
            ->title('Stand eliminado')
            ->success()
            ->send();
    }

    private function saveSelectedSlot(): void
    {
        $slot = StandSlot::query()->with('template', 'zone')->find($this->selectedObjectId);

        if (! $slot || $slot->zone?->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $snapshot = $this->captureSnapshot();

        try {
            $slot->update([
                'world_position' => [
                    'x' => (float) ($this->selectedObjectForm['position']['x'] ?? 0),
                    'y' => (float) ($this->selectedObjectForm['position']['y'] ?? 0),
                    'z' => (float) ($this->selectedObjectForm['position']['z'] ?? 0),
                ],
                'rotation' => [
                    'x' => 0,
                    'y' => (float) ($this->selectedObjectForm['rotation']['y'] ?? 0),
                    'z' => 0,
                ],
                'max_width' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['size']['x'] ?? self::MIN_DIMENSION)),
                'max_depth' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['size']['z'] ?? self::MIN_DIMENSION)),
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('No se pudo guardar el stand')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('slot', $slot->id);

        Notification::make()
            ->title('Stand actualizado')
            ->success()
            ->send();
    }

    private function saveSelectedProp(): void
    {
        $prop = ImmersivePlazaProp::query()->with('template')->find($this->selectedObjectId);

        if (! $prop || $prop->immersive_plaza_id !== $this->plaza->id) {
            return;
        }

        $size = [
            'x' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['size']['x'] ?? self::MIN_DIMENSION)),
            'y' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['size']['y'] ?? self::MIN_DIMENSION)),
            'z' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['size']['z'] ?? self::MIN_DIMENSION)),
        ];
        $scaleVector = $this->scaleVectorFromSize($prop->template, $size);
        $attributes = [
            'world_position' => [
                'x' => (float) ($this->selectedObjectForm['position']['x'] ?? 0),
                'y' => (float) ($this->selectedObjectForm['position']['y'] ?? 0),
                'z' => (float) ($this->selectedObjectForm['position']['z'] ?? 0),
            ],
            'rotation' => [
                'x' => 0,
                'y' => (float) ($this->selectedObjectForm['rotation']['y'] ?? 0),
                'z' => 0,
            ],
            'scale' => ($scaleVector['x'] + $scaleVector['y'] + $scaleVector['z']) / 3,
        ];

        if ($this->supportsScaleVector()) {
            $attributes['scale_vector'] = $scaleVector;
        }

        if ($this->supportsCollisionEnabled()) {
            $attributes['collision_enabled'] = (bool) ($this->selectedObjectForm['collisionEnabled'] ?? false);
        }

        if ($this->supportsTiling()) {
            $attributes['texture_tiling'] = [
                'u' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['tiling']['u'] ?? 1)),
                'v' => max(self::MIN_DIMENSION, (float) ($this->selectedObjectForm['tiling']['v'] ?? 1)),
            ];
        }

        $snapshot = $this->captureSnapshot();

        try {
            $prop->update($attributes);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('No se pudo guardar el elemento')
                ->body('Revisa que la tabla de elementos inmersivos esté actualizada. Si el problema persiste, aplica las migraciones pendientes.')
                ->danger()
                ->send();

            return;
        }

        $this->commitHistorySnapshot($snapshot);
        $this->afterObjectSaved('prop', $prop->id);

        Notification::make()
            ->title('Elemento actualizado')
            ->body($this->legacyCompatibilityMessage())
            ->success()
            ->send();
    }

    private function afterObjectSaved(string $type, int $id): void
    {
        $this->reloadSceneData();

        if ($this->selectedObjectType === $type && $this->selectedObjectId === $id) {
            $this->selectObject($type, $id);
        }

        $payload = $this->findObjectData($type, $id);

        if ($payload) {
            $this->dispatch('spatial-editor-object-updated', object: $payload);
        }
    }

    /**
     * Pedido del usuario: deshacer/rehacer en el editor espacial de la
     * plaza. A diferencia del editor de cajas de un objeto (una sola
     * definición JSON), aquí el estado son varias filas de BD (stands,
     * elementos) más la configuración propia de la plaza — la "foto" es
     * una copia de todos los campos editables desde aquí, no un solo blob.
     *
     * @return array{
     *     slots: array<int, array<string, mixed>>,
     *     props: array<int, array<string, mixed>>,
     *     plaza: array<string, mixed>,
     * }
     */
    private function captureSnapshot(): array
    {
        $this->plaza->loadMissing(['zones.slots', 'props']);

        return [
            'slots' => $this->plaza->zones->flatMap->slots->map(fn (StandSlot $slot): array => [
                'id' => $slot->id,
                // El resto de campos (menos los ya cubiertos por
                // `saveSelectedSlot()`) se necesitan para poder RECREAR el
                // slot si se rehace su creación (`addSlot()`) después de
                // haberla deshecho — mismo motivo que `immersive_plaza_id`
                // en el prop de abajo.
                'stand_zone_id' => $slot->stand_zone_id,
                'code' => $slot->code,
                'stand_template_id' => $slot->stand_template_id,
                'allowed_category_id' => $slot->allowed_category_id,
                'world_position' => $slot->world_position,
                'rotation' => $slot->rotation,
                'max_width' => $slot->max_width,
                'max_depth' => $slot->max_depth,
                'orientation_mode' => $slot->orientation_mode,
                'accessible' => $slot->accessible,
                'status' => $slot->status,
                'source' => $slot->source,
            ])->values()->all(),
            'props' => $this->plaza->props->map(function (ImmersivePlazaProp $prop): array {
                $state = [
                    'id' => $prop->id,
                    // Necesario para poder RECREAR el prop si se deshace su
                    // eliminación — sin esto, updateOrCreate() intentaría
                    // insertar la fila sin la FK obligatoria a la plaza.
                    'immersive_plaza_id' => $prop->immersive_plaza_id,
                    'object_template_id' => $prop->object_template_id,
                    'world_position' => $prop->world_position,
                    'rotation' => $prop->rotation,
                    'scale' => $prop->scale,
                    'status' => $prop->status,
                    'source' => $prop->source,
                ];

                // Solo se incluyen si la columna existe — igual que
                // `saveSelectedProp()`/`addProp()` ya hacen al guardar, para
                // no reintroducir una columna que la migración pendiente
                // todavía no aplicó.
                if ($this->supportsScaleVector()) {
                    $state['scale_vector'] = $prop->scale_vector;
                }

                if ($this->supportsCollisionEnabled()) {
                    $state['collision_enabled'] = (bool) $prop->collision_enabled;
                }

                if ($this->supportsTiling()) {
                    $state['texture_tiling'] = $prop->texture_tiling;
                }

                return $state;
            })->values()->all(),
            'plaza' => [
                'spawn_point' => $this->plaza->spawn_point,
                'navigable_bounds' => $this->plaza->navigable_bounds,
                'reference_image_width' => $this->plaza->reference_image_width,
                'reference_image_height' => $this->plaza->reference_image_height,
            ],
        ];
    }

    /**
     * El tipo del parámetro es genérico a propósito (no la forma exacta que
     * devuelve `captureSnapshot()`): viene de hacer `array_pop()` sobre
     * `$undoStack`/`$redoStack`, propiedades públicas de Livewire tipadas
     * como `array<int, array<string, mixed>>` sin forma específica.
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function restoreSnapshot(array $snapshot): void
    {
        DB::transaction(function () use ($snapshot): void {
            $snapshotSlotIds = array_column($snapshot['slots'], 'id');

            // Elimina slots creados DESPUÉS de esta foto (`addSlot()`) — no
            // existían todavía en el momento que se está restaurando.
            StandSlot::query()
                ->whereIn('stand_zone_id', $this->plaza->zones()->pluck('id'))
                ->whereNotIn('id', $snapshotSlotIds)
                ->delete();

            // `withoutEvents` (en vez de `$slot->update()` o `save()` a
            // secas) porque restaurar nunca debe fallar por la validación
            // de solapamiento/zona de StandSlot — ese estado ya fue válido
            // cuando se guardó la primera vez, y validar contra posiciones
            // a medio restaurar de otros stands daría falsos rechazos.
            // `updateOrCreate` (no solo `update`) porque un slot agregado
            // con `addSlot()` puede necesitar RECREARSE al rehacer
            // (`redo()`) después de haber deshecho su creación.
            StandSlot::withoutEvents(function () use ($snapshot): void {
                foreach ($snapshot['slots'] as $slotState) {
                    StandSlot::query()->updateOrCreate(
                        ['id' => $slotState['id']],
                        Arr::except($slotState, ['id']),
                    );
                }
            });

            $snapshotPropIds = collect($snapshot['props'])->pluck('id');

            // Elimina elementos creados DESPUÉS de esta foto (no existían
            // todavía en el momento que se está restaurando).
            $this->plaza->props()->whereNotIn('id', $snapshotPropIds)->delete();

            foreach ($snapshot['props'] as $propState) {
                ImmersivePlazaProp::query()->updateOrCreate(
                    ['id' => $propState['id']],
                    Arr::except($propState, ['id']),
                );
            }

            $this->plaza->update($snapshot['plaza']);
        });

        $this->clearSelectedObject();
        $this->plaza->refresh();
        $this->syncFormsFromPlaza();
        $this->reloadSceneData();

        $this->dispatch('spatial-editor-history-restored', objects: $this->sceneData['objects']);
        $this->dispatch(
            'spatial-editor-settings-updated',
            bounds: $this->sceneData['bounds'],
            plane: $this->sceneData['plane'],
        );
    }

    private function historyCacheKey(string $stack): string
    {
        return "immersive-plaza-editor-history:{$this->historyToken}:{$stack}";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getHistoryStack(string $stack): array
    {
        /** @var array<int, array<string, mixed>> */
        return Cache::get($this->historyCacheKey($stack), []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $value
     */
    private function setHistoryStack(string $stack, array $value): void
    {
        Cache::put($this->historyCacheKey($stack), array_slice($value, -self::MAX_HISTORY), now()->addHours(4));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function commitHistorySnapshot(array $snapshot): void
    {
        $undoStack = $this->getHistoryStack('undo');
        $undoStack[] = $snapshot;
        $this->setHistoryStack('undo', $undoStack);
        $this->setHistoryStack('redo', []);
    }

    public function canUndo(): bool
    {
        return $this->getHistoryStack('undo') !== [];
    }

    public function canRedo(): bool
    {
        return $this->getHistoryStack('redo') !== [];
    }

    public function undo(): void
    {
        $undoStack = $this->getHistoryStack('undo');

        if ($undoStack === []) {
            return;
        }

        $target = array_pop($undoStack);
        $current = $this->captureSnapshot();

        $this->restoreSnapshot($target);

        $this->setHistoryStack('undo', $undoStack);
        $redoStack = $this->getHistoryStack('redo');
        $redoStack[] = $current;
        $this->setHistoryStack('redo', $redoStack);
    }

    public function redo(): void
    {
        $redoStack = $this->getHistoryStack('redo');

        if ($redoStack === []) {
            return;
        }

        $target = array_pop($redoStack);
        $current = $this->captureSnapshot();

        $this->restoreSnapshot($target);

        $this->setHistoryStack('redo', $redoStack);
        $undoStack = $this->getHistoryStack('undo');
        $undoStack[] = $current;
        $this->setHistoryStack('undo', $undoStack);
    }

    private function syncFormsFromPlaza(): void
    {
        $bounds = $this->plaza->navigable_bounds ?? [];

        $this->boundsForm = [
            'minX' => (float) ($bounds['minX'] ?? -50),
            'maxX' => (float) ($bounds['maxX'] ?? 50),
            'minZ' => (float) ($bounds['minZ'] ?? -50),
            'maxZ' => (float) ($bounds['maxZ'] ?? 50),
        ];

        $this->imageDimensionsForm = [
            'width' => $this->plaza->reference_image_width,
            'height' => $this->plaza->reference_image_height,
        ];
    }

    private function reloadSceneData(): void
    {
        $this->plaza->refresh();
        $this->sceneData = $this->buildSceneData($this->plaza);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSceneData(ImmersivePlaza $plaza): array
    {
        $plaza->loadMissing(['zones.slots.template', 'props.template']);

        $slots = $plaza->zones->flatMap->slots->map(fn (StandSlot $slot) => $this->describeSlot($slot))->values()->all();
        $props = $plaza->props->map(fn (ImmersivePlazaProp $prop) => $this->describeProp($prop))->values()->all();
        $objects = collect([$this->describeSpawn($plaza), ...$slots, ...$props])
            ->sortBy(fn (array $object) => [$object['type'], $object['label']])
            ->values()
            ->all();

        return [
            'bounds' => $plaza->navigable_bounds,
            'plane' => $this->planeData($plaza),
            'referenceImageUrl' => $plaza->reference_image_path ? Storage::disk('public')->url($plaza->reference_image_path) : null,
            // La zona invisible de `defaultStandZone()` no se dibuja: no es
            // una zona real que el admin haya delimitado, es solo el
            // contenedor técnico que le da `stand_zone_id` a los slots
            // agregados desde acá.
            'zones' => $plaza->zones
                ->reject(fn ($zone) => $zone->name === self::AUTO_ZONE_NAME)
                ->map(fn ($zone) => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'polygon' => $zone->polygon,
                ])->values()->all(),
            'excludedZones' => $plaza->excluded_zones ?? [],
            'slots' => $slots,
            'props' => $props,
            'objects' => $objects,
        ];
    }

    /**
     * @return array{centerX: float, centerZ: float, width: float, depth: float}
     */
    private function planeData(ImmersivePlaza $plaza): array
    {
        $bounds = $plaza->navigable_bounds ?? [];

        $fallbackWidth = is_array($bounds) && isset($bounds['minX'], $bounds['maxX'])
            ? max(1, (float) $bounds['maxX'] - (float) $bounds['minX'])
            : 100.0;
        $fallbackDepth = is_array($bounds) && isset($bounds['minZ'], $bounds['maxZ'])
            ? max(1, (float) $bounds['maxZ'] - (float) $bounds['minZ'])
            : 100.0;
        $fallbackCenterX = is_array($bounds) && isset($bounds['minX'], $bounds['maxX'])
            ? ((float) $bounds['minX'] + (float) $bounds['maxX']) / 2
            : 0.0;
        $fallbackCenterZ = is_array($bounds) && isset($bounds['minZ'], $bounds['maxZ'])
            ? ((float) $bounds['minZ'] + (float) $bounds['maxZ']) / 2
            : 0.0;

        return [
            'centerX' => $fallbackCenterX,
            'centerZ' => $fallbackCenterZ,
            'width' => filled($plaza->reference_image_width) ? max(1, (float) $plaza->reference_image_width) : $fallbackWidth,
            'depth' => filled($plaza->reference_image_height) ? max(1, (float) $plaza->reference_image_height) : $fallbackDepth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Pedido del usuario: acceso directo desde el panel de Propiedades al
     * editor de cajas del objeto seleccionado. Un objeto con GLB cargado en
     * modo "Modelo 3D" se renderiza siempre con ese GLB (misma prioridad
     * que `renderObjectByPriority`), así que el editor de cajas no tiene
     * ningún efecto visible para él — se oculta devolviendo null.
     */
    private function objectEditorUrl(?ImmersiveObjectTemplate $template): ?string
    {
        if (! $template || ($template->asset_input_mode === 'model_3d' && filled($template->model_path))) {
            return null;
        }

        return ImmersiveObjectTemplateResource::getUrl('object-editor', ['record' => $template->id]);
    }

    /**
     * El punto de aparición se trata como "un objeto más" (mismo pedido del
     * usuario) para reutilizar la selección/arrastre/panel de Propiedades
     * ya construidos para stands y elementos — sin duplicar esa lógica. Su
     * `id` es el sentinel `-1` (nunca hay más de un punto de aparición por
     * plaza y no existe una fila en BD que darle un id real); tiene que ser
     * distinto de `0`, que en PHP/Blade se evalúa como "falsy" en varias
     * comprobaciones (`$selectedObjectId` truthy) ya existentes.
     *
     * @return array<string, mixed>
     */
    private function describeSpawn(ImmersivePlaza $plaza): array
    {
        $spawn = $plaza->spawn_point ?? ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0];
        $position = [
            'x' => (float) $spawn['x'],
            'y' => (float) $spawn['y'],
            'z' => (float) $spawn['z'],
        ];
        $rotationY = (float) $spawn['rotationY'];

        return [
            'type' => 'spawn',
            'id' => -1,
            'label' => 'Punto de aparición',
            'position' => $position,
            // Tamaño fijo, solo cosmético para el marcador del visor — no
            // corresponde a ningún campo real de `spawn_point`.
            'size' => ['x' => 0.6, 'y' => 0.8, 'z' => 0.6],
            'rotation' => ['x' => 0, 'y' => $rotationY, 'z' => 0],
            'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
            'modelUrl' => null,
            'modelDefinition' => null,
            'builderKey' => null,
            'collisionEnabled' => false,
            'hasGlbModel' => false,
            'objectEditorUrl' => null,
            'status' => null,
            'locked' => (bool) ($spawn['locked'] ?? false),
            'x' => $position['x'],
            'y' => $position['y'],
            'z' => $position['z'],
            'rotationY' => $rotationY,
        ];
    }

    private function describeSlot(StandSlot $slot): array
    {
        $position = $this->normalizePosition($slot->world_position);
        $rotation = $this->normalizeRotation($slot->rotation);
        $template = $slot->template;
        $size = [
            'x' => (float) $slot->max_width,
            'y' => (float) ($template?->max_height ?? self::DEFAULT_SLOT_HEIGHT),
            'z' => (float) $slot->max_depth,
        ];

        return [
            'type' => 'slot',
            'id' => $slot->id,
            'label' => $slot->code,
            'position' => $position,
            'size' => $size,
            'rotation' => $rotation,
            'scale' => $this->slotScale($slot, $template),
            'modelUrl' => $template?->modelPathUrl(),
            'modelDefinition' => $template?->model_definition,
            'builderKey' => $template?->builder_key,
            'hasGlbModel' => filled($template?->model_path),
            'objectEditorUrl' => $this->objectEditorUrl($template),
            'locked' => (bool) $slot->locked,
            'x' => $position['x'],
            'y' => $position['y'],
            'z' => $position['z'],
            'rotationY' => $rotation['y'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeProp(ImmersivePlazaProp $prop): array
    {
        $position = $this->normalizePosition($prop->world_position);
        $rotation = $this->normalizeRotation($prop->rotation);
        $template = $prop->template;
        $scale = $prop->scaleVector();
        $size = [
            'x' => max(self::MIN_DIMENSION, (float) ($template?->max_width ?? 1) * $scale['x']),
            'y' => max(self::MIN_DIMENSION, (float) ($template?->max_height ?? 1) * $scale['y']),
            'z' => max(self::MIN_DIMENSION, (float) ($template?->max_depth ?? 1) * $scale['z']),
        ];

        return [
            'type' => 'prop',
            'id' => $prop->id,
            'label' => $template?->name ?? 'Elemento',
            'position' => $position,
            'size' => $size,
            'rotation' => $rotation,
            'scale' => $scale,
            'modelUrl' => $template?->modelPathUrl(),
            'modelDefinition' => $template?->model_definition,
            'builderKey' => $template?->builder_key,
            'collisionEnabled' => (bool) $prop->collision_enabled,
            'hasGlbModel' => filled($template?->model_path),
            'objectEditorUrl' => $this->objectEditorUrl($template),
            'status' => $prop->status,
            'locked' => (bool) $prop->locked,
            'tiling' => $this->supportsTiling() ? $prop->textureTiling() : null,
            'x' => $position['x'],
            'y' => $position['y'],
            'z' => $position['z'],
            'rotationY' => $rotation['y'],
        ];
    }

    /**
     * @return array{x: float, y: float, z: float}
     */
    private function normalizePosition(?array $position): array
    {
        return [
            'x' => (float) ($position['x'] ?? 0),
            'y' => (float) ($position['y'] ?? 0),
            'z' => (float) ($position['z'] ?? 0),
        ];
    }

    /**
     * @return array{x: float, y: float, z: float}
     */
    private function normalizeRotation(?array $rotation): array
    {
        return [
            'x' => (float) ($rotation['x'] ?? 0),
            'y' => (float) ($rotation['y'] ?? 0),
            'z' => (float) ($rotation['z'] ?? 0),
        ];
    }

    /**
     * @return array{x: float, y: float, z: float}
     */
    private function slotScale(StandSlot $slot, ?ImmersiveObjectTemplate $template): array
    {
        return [
            'x' => $template && $template->max_width > 0 ? round($slot->max_width / $template->max_width, 4) : 1.0,
            'y' => 1.0,
            'z' => $template && $template->max_depth > 0 ? round($slot->max_depth / $template->max_depth, 4) : 1.0,
        ];
    }

    /**
     * @param  array{x: float, y: float, z: float}  $size
     * @return array{x: float, y: float, z: float}
     */
    private function scaleVectorFromSize(?ImmersiveObjectTemplate $template, array $size): array
    {
        return [
            'x' => $template && $template->max_width > 0 ? round($size['x'] / $template->max_width, 4) : 1.0,
            'y' => $template && $template->max_height > 0 ? round($size['y'] / $template->max_height, 4) : 1.0,
            'z' => $template && $template->max_depth > 0 ? round($size['z'] / $template->max_depth, 4) : 1.0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findObjectData(string $type, int $id): ?array
    {
        return collect($this->sceneData['objects'] ?? [])
            ->first(fn (array $object): bool => $object['type'] === $type && $object['id'] === $id);
    }

    /**
     * @return Collection<int, ImmersiveObjectTemplate>
     */
    public function availablePropTemplates(): Collection
    {
        return ImmersiveObjectTemplate::query()
            ->where('category', '!=', 'stand')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
    }

    /**
     * @return array{x: float, y: float, z: float}
     */
    private function defaultPropPosition(): array
    {
        $bounds = $this->plaza->navigable_bounds ?? null;

        if (is_array($bounds) && isset($bounds['minX'], $bounds['maxX'], $bounds['minZ'], $bounds['maxZ'])) {
            return [
                'x' => (float) (($bounds['minX'] + $bounds['maxX']) / 2),
                'y' => 0,
                'z' => (float) (($bounds['minZ'] + $bounds['maxZ']) / 2),
            ];
        }

        return ['x' => 0, 'y' => 0, 'z' => 0];
    }

    /**
     * Reusa (o crea una única vez) una zona invisible que cubre toda el
     * área navegable de la plaza, para que `addSlot()` no tenga que
     * pedirle una zona al admin. `withoutEvents()` evita la validación de
     * `StandZone` (polígono dentro de límites / sin invadir excluidas):
     * esta zona sintética por diseño cubre TODA el área, así que
     * "invade" cualquier zona excluida que exista dentro — no es un
     * problema real porque no es una zona geométrica con la que alguien
     * está delimitando algo a propósito, solo un contenedor técnico. La
     * protección real sigue viva donde importa: la validación de
     * `StandSlot` (que si compara la huella de CADA slot contra las
     * zonas excluidas) no se toca.
     */
    private function defaultStandZone(): StandZone
    {
        $existing = $this->plaza->zones()->where('name', self::AUTO_ZONE_NAME)->first();

        if ($existing) {
            return $existing;
        }

        $bounds = $this->plaza->navigable_bounds ?? ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50];

        return StandZone::withoutEvents(fn () => $this->plaza->zones()->create([
            'name' => self::AUTO_ZONE_NAME,
            'polygon' => ['points' => [
                ['x' => (float) $bounds['minX'], 'z' => (float) $bounds['minZ']],
                ['x' => (float) $bounds['maxX'], 'z' => (float) $bounds['minZ']],
                ['x' => (float) $bounds['maxX'], 'z' => (float) $bounds['maxZ']],
                ['x' => (float) $bounds['minX'], 'z' => (float) $bounds['maxZ']],
            ]],
        ]));
    }

    /**
     * Único requisito real es no chocar con el índice único
     * `(stand_zone_id, code)` — un sufijo al azar alcanza, no hace falta
     * consultar el máximo actual (que además se rompería si se borró un
     * slot intermedio).
     */
    private function nextSlotCode(): string
    {
        return 'SLOT-'.strtoupper(Str::random(6));
    }

    /**
     * Punto al azar dentro del rectángulo que envuelve el polígono de la
     * zona — pedido del usuario ("que se posicionen aleatoriamente"), para
     * que agregar varios slots seguidos no los deje todos apilados en el
     * mismo punto (lo que además haría fallar la validación de
     * solapamiento del segundo en adelante). `$margin` reduce el rango de
     * muestreo por la mitad del ancho/profundidad del slot, para que su
     * huella (no solo el punto central) no sobresalga del rectángulo —
     * sin esto, un punto cerca del borde hacía fallar casi la mitad de
     * los intentos por invadir el límite de la zona. Aun así no garantiza
     * caer DENTRO de un polígono no rectangular; si la validación de la
     * zona lo rechaza, `addSlot()` ya muestra el motivo y el admin puede
     * reintentar.
     *
     * @return array{x: float, y: float, z: float}
     */
    private function randomPositionWithinZone(StandZone $zone, float $margin = 0): array
    {
        $points = $zone->polygon['points'] ?? [];

        if ($points === []) {
            return $this->defaultPropPosition();
        }

        $xs = array_column($points, 'x');
        $zs = array_column($points, 'z');
        $minX = min($xs) + $margin;
        $maxX = max($xs) - $margin;
        $minZ = min($zs) + $margin;
        $maxZ = max($zs) - $margin;

        if ($minX > $maxX || $minZ > $maxZ) {
            return [
                'x' => (float) ((min($xs) + max($xs)) / 2),
                'y' => 0,
                'z' => (float) ((min($zs) + max($zs)) / 2),
            ];
        }

        return [
            'x' => $minX + mt_rand() / mt_getrandmax() * ($maxX - $minX),
            'y' => 0,
            'z' => $minZ + mt_rand() / mt_getrandmax() * ($maxZ - $minZ),
        ];
    }

    private function supportsScaleVector(): bool
    {
        if (self::$supportsScaleVector === null) {
            self::$supportsScaleVector = Schema::hasColumn('immersive_plaza_props', 'scale_vector');
        }

        return self::$supportsScaleVector;
    }

    private function supportsCollisionEnabled(): bool
    {
        if (self::$supportsCollisionEnabled === null) {
            self::$supportsCollisionEnabled = Schema::hasColumn('immersive_plaza_props', 'collision_enabled');
        }

        return self::$supportsCollisionEnabled;
    }

    private function supportsTiling(): bool
    {
        if (self::$supportsTiling === null) {
            self::$supportsTiling = Schema::hasColumn('immersive_plaza_props', 'texture_tiling');
        }

        return self::$supportsTiling;
    }

    private function legacyCompatibilityMessage(): ?string
    {
        $messages = [];

        if (! $this->supportsScaleVector()) {
            $messages[] = 'Se guardó con compatibilidad antigua. Para escala distinta por eje, aplica las migraciones pendientes.';
        }

        if (! $this->supportsCollisionEnabled()) {
            $messages[] = 'La opción de colisiones todavía no se persistió porque falta aplicar la migración pendiente.';
        }

        return $messages !== [] ? implode(' ', $messages) : null;
    }
}
