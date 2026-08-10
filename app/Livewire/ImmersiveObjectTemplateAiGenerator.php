<?php

namespace App\Livewire;

use App\Domain\Immersive\Contracts\GeneratesVoxelObjectDefinition;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelDefinitionValidationException;
use App\Domain\Immersive\Support\Exceptions\VoxelGenerationException;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use App\Domain\Immersive\Support\VoxelPaletteMatcher;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ImmersiveObjectTemplateAiGenerator extends Component
{
    use WithFileUploads;

    public ImmersiveObjectTemplate $template;

    public string $previewDomId;

    public string $previewEventName;

    public ?TemporaryUploadedFile $frontImage = null;

    public ?TemporaryUploadedFile $sideImage = null;

    public ?TemporaryUploadedFile $topImage = null;

    public ?TemporaryUploadedFile $glbModel = null;

    public string $instructions = '';

    public string $assetInputMode = 'model_3d';

    public string $maxWidth = '1';

    public string $maxDepth = '1';

    public string $maxHeight = '1';

    public string $maxBoxes = '40';

    /** @var array<int, string> */
    public array $allowedColors = [];

    public string $allowedColorsText = '';

    /** @var array<string, mixed>|null */
    public ?array $currentDefinition = null;

    /**
     * Snapshot de `currentDefinition` justo antes del último `generate()`
     * exitoso — permite deshacer ese refinamiento sin volver a llamar a la
     * IA. Un solo nivel de historial: el pedido es deshacer "el último
     * cambio", no mantener un historial completo.
     *
     * @var array<string, mixed>|null
     */
    public ?array $previousDefinitionSnapshot = null;

    /**
     * Aparte de `previousDefinitionSnapshot` porque ese valor puede ser
     * legítimamente `null` (si el último refinamiento fue la primerísima
     * generación) — sin esta bandera no se podría distinguir "no hay nada
     * que deshacer" de "lo que hay que deshacer es volver a estado vacío".
     */
    public bool $canUndoLastRefinement = false;

    /** @var array<int, array{role: string, text: string, at: string}> */
    public array $instructionLog = [];

    public function mount(ImmersiveObjectTemplate $template): void
    {
        $this->template = $template;
        $this->currentDefinition = $template->ai_draft_definition ?? $template->model_definition;
        $this->instructionLog = $template->ai_instruction_log ?? [];
        $this->assetInputMode = $template->asset_input_mode ?: 'model_3d';
        $this->maxWidth = $this->formatDecimal($template->max_width ?: 1);
        $this->maxDepth = $this->formatDecimal($template->max_depth ?: 1);
        $this->maxHeight = $this->formatDecimal($template->max_height ?: 1);
        $this->maxBoxes = (string) ($template->max_boxes ?: 40);
        $this->allowedColors = array_values(array_filter($template->allowed_colors ?? []));
        $this->allowedColorsText = implode(', ', $this->allowedColors);
        $suffix = Str::lower((string) Str::ulid());
        $this->previewDomId = 'voxel-ai-preview-'.$suffix;
        $this->previewEventName = 'voxel-definition-updated-'.$suffix;
    }

    public function updatedAssetInputMode(string $value): void
    {
        if (! in_array($value, ['model_3d', 'ia_voxel'], true)) {
            return;
        }

        $this->persistTemplateState([
            'asset_input_mode' => $value,
        ]);
    }

    public function updatedMaxWidth(mixed $value): void
    {
        $this->persistDecimalField('maxWidth', 'max_width', $value);
    }

    public function updatedMaxDepth(mixed $value): void
    {
        $this->persistDecimalField('maxDepth', 'max_depth', $value);
    }

    public function updatedMaxHeight(mixed $value): void
    {
        $this->persistDecimalField('maxHeight', 'max_height', $value);
    }

    public function updatedMaxBoxes(mixed $value): void
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return;
        }

        $normalized = (string) max(1, (int) $value);
        $this->maxBoxes = $normalized;

        $this->persistTemplateState([
            'max_boxes' => (int) $normalized,
        ]);
    }

    public function updatedAllowedColors(array $value): void
    {
        $normalized = array_values(array_filter(array_map(
            static fn (mixed $color): string => is_string($color) ? trim($color) : '',
            $value,
        )));

        $this->allowedColors = $normalized;
        $this->allowedColorsText = implode(', ', $normalized);

        $this->persistTemplateState([
            'allowed_colors' => $normalized,
        ]);
    }

    public function updatedAllowedColorsText(string $value): void
    {
        $this->updatedAllowedColors(array_values(array_filter(array_map(
            static fn (string $color): string => trim($color),
            explode(',', $value),
        ))));
    }

    public function generate(): void
    {
        $referenceImages = $this->prepareReferenceImagesForGeneration();

        if (count($referenceImages) !== 3) {
            Notification::make()
                ->title('Debes tener las 3 imágenes de referencia (frontal, lateral y superior) antes de generar.')
                ->danger()
                ->send();

            return;
        }

        $isRefinement = $this->currentDefinition !== null;
        $imagePaths = array_map(
            static fn (array $image): string => $image['path'],
            $referenceImages,
        );

        $this->appendLog(
            'admin',
            filled($this->instructions)
                ? $this->instructions
                : ($isRefinement ? 'Refinamiento sin instrucciones adicionales.' : 'Generación sin instrucciones adicionales.')
        );

        // La miniatura del catálogo (si existe) suele ser ya un render de
        // referencia del objeto completo — se manda como imagen extra a la
        // IA bajo la clave 'preview' (el generador la etiqueta y le baja el
        // detail, ya que no tiene por qué tener proporciones exactas), pero
        // NUNCA se agrega a $imagePaths: ese array es lo que se borra en el
        // finally, y la miniatura es un asset persistente del template, no
        // un archivo temporal de esta generación.
        $generationImagePaths = $imagePaths;

        if (filled($this->template->thumbnail_path)) {
            $generationImagePaths['preview'] = $this->template->thumbnail_path;
        }

        try {
            $definition = app(GeneratesVoxelObjectDefinition::class)->generate(
                imagePaths: $generationImagePaths,
                instructions: $this->instructions,
                context: [
                    'nombre' => $this->template->name,
                    'categoria' => $this->template->category,
                    'max_width' => $this->normalizedDecimal($this->maxWidth, fallback: $this->template->max_width ?: 1),
                    'max_depth' => $this->normalizedDecimal($this->maxDepth, fallback: $this->template->max_depth ?: 1),
                    'max_height' => $this->normalizedDecimal($this->maxHeight, fallback: $this->template->max_height ?: 1),
                    'max_boxes' => $this->normalizedInteger($this->maxBoxes, fallback: $this->template->max_boxes ?: 40),
                    // "Colores permitidos" (hex) no lo entiende el motor
                    // directamente — se traduce a los nombres de textura más
                    // parecidos (VoxelPaletteMatcher) para que el generador
                    // pueda restringir de verdad el enum del schema, no solo
                    // pedírselo de palabra a la IA.
                    'allowed_colors' => $this->allowedColors,
                    'allowed_textures' => VoxelPaletteMatcher::nearestTextures($this->allowedColors),
                ],
                previousDefinition: $isRefinement ? $this->currentDefinition : null,
            );

            $this->previousDefinitionSnapshot = $this->currentDefinition;
            $this->canUndoLastRefinement = true;

            $this->currentDefinition = $definition;
            $this->persistTemplateState([
                'ai_draft_definition' => $definition,
            ]);
            $this->appendLog('sistema', $isRefinement ? 'Definición refinada.' : 'Definición generada.');

            $this->dispatch($this->previewEventName, definition: $this->currentDefinition);

            // Solo se limpia si la generación tuvo éxito: si falla, el admin
            // necesita el texto ahí para corregirlo y reintentar sin
            // reescribirlo desde cero.
            $this->instructions = '';
        } catch (VoxelGenerationException $exception) {
            $this->appendLog('sistema', 'Error: '.$exception->getMessage());

            Notification::make()
                ->title('No se pudo generar la definición')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            foreach ($referenceImages as $image) {
                if ($image['temporary']) {
                    Storage::disk('public')->delete($image['path']);
                }
            }
        }
    }

    /**
     * Deshace el último "Generar"/"Refinar" exitoso, volviendo al valor que
     * tenía `currentDefinition` justo antes de esa llamada — sin gastar otra
     * generación con la IA. Solo revierte el borrador (`ai_draft_definition`,
     * lo mismo que toca `generate()`); si esa versión anterior ya se había
     * publicado con "Guardar", `model_definition` no se toca aquí, igual que
     * `generate()` tampoco lo toca.
     */
    public function undoLastRefinement(): void
    {
        if (! $this->canUndoLastRefinement) {
            return;
        }

        $this->currentDefinition = $this->previousDefinitionSnapshot;
        $this->previousDefinitionSnapshot = null;
        $this->canUndoLastRefinement = false;

        $this->persistTemplateState([
            'ai_draft_definition' => $this->currentDefinition,
        ]);

        $this->appendLog('sistema', 'Se deshizo el último refinamiento.');

        $this->dispatch($this->previewEventName, definition: $this->currentDefinition);

        Notification::make()->title('Refinamiento revertido.')->success()->send();
    }

    public function save(): void
    {
        $attributes = $this->persistUploadsAndSettings();

        if ($this->currentDefinition !== null) {
            try {
                $bounds = (new VoxelDefinitionValidator)->validate($this->currentDefinition, $this->template);
            } catch (VoxelDefinitionValidationException $exception) {
                Notification::make()
                    ->title('La definición no es válida')
                    ->body(implode("\n", $exception->errors()))
                    ->danger()
                    ->send();

                return;
            }

            $attributes['model_definition'] = $this->currentDefinition;
            $attributes['ai_draft_definition'] = $this->currentDefinition;

            if ($this->assetInputMode !== 'model_3d') {
                $attributes['max_width'] = $bounds['width'];
                $attributes['max_depth'] = $bounds['depth'];
                $attributes['max_height'] = $bounds['height'];
                $this->maxWidth = $this->formatDecimal($bounds['width']);
                $this->maxDepth = $this->formatDecimal($bounds['depth']);
                $this->maxHeight = $this->formatDecimal($bounds['height']);
            }
        }

        $this->persistTemplateState($attributes);
        $this->appendLog('sistema', 'Cambios guardados.');

        Notification::make()->title('Configuración guardada.')->success()->send();
    }

    public function render(): View
    {
        return view('livewire.immersive-object-template-ai-generator');
    }

    public function referenceImageUrl(string $view): ?string
    {
        return $this->template->referenceImageUrl($view);
    }

    /**
     * `generate()` (Generar/Refinar) solo escribe en `ai_draft_definition` y
     * en la previsualización; la plaza en vivo renderiza `model_definition`,
     * que solo se actualiza al hacer clic en "Guardar" (`save()`). Sin este
     * aviso, un admin que refina y cierra el modal sin guardar ve el objeto
     * "actualizado" en la previsualización pero la plaza sigue mostrando la
     * versión anterior.
     */
    public function hasUnpublishedChanges(): bool
    {
        if ($this->currentDefinition === null) {
            return false;
        }

        return json_encode($this->currentDefinition) !== json_encode($this->template->model_definition);
    }

    protected function persistDecimalField(string $property, string $column, mixed $value): void
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return;
        }

        $normalized = $this->normalizedDecimal($value);
        $this->{$property} = $this->formatDecimal($normalized);

        $this->persistTemplateState([
            $column => $normalized,
        ]);
    }

    /**
     * @return array<string, array{path: string, temporary: bool}>
     */
    protected function prepareReferenceImagesForGeneration(): array
    {
        return array_filter([
            'front' => $this->prepareReferenceImage('front', $this->frontImage),
            'side' => $this->prepareReferenceImage('side', $this->sideImage),
            'top' => $this->prepareReferenceImage('top', $this->topImage),
        ]);
    }

    /**
     * @return array{path: string, temporary: bool}|null
     */
    protected function prepareReferenceImage(string $view, ?TemporaryUploadedFile $file): ?array
    {
        if ($file) {
            $path = $file->store('immersive-object-templates/ai-source', 'public');

            return is_string($path) ? ['path' => $path, 'temporary' => true] : null;
        }

        $path = $this->template->referenceImagePath($view);

        return $path ? ['path' => $path, 'temporary' => false] : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function persistUploadsAndSettings(): array
    {
        $referenceImages = $this->template->ai_reference_images ?? [];

        foreach (['front' => $this->frontImage, 'side' => $this->sideImage, 'top' => $this->topImage] as $view => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('immersive-object-templates/ai-reference', 'public');

            if (! is_string($path)) {
                continue;
            }

            $previousPath = data_get($referenceImages, $view);

            if (is_string($previousPath) && $previousPath !== $path) {
                Storage::disk('public')->delete($previousPath);
            }

            $referenceImages[$view] = $path;
        }

        $attributes = [
            'asset_input_mode' => $this->assetInputMode,
            'max_width' => $this->normalizedDecimal($this->maxWidth, fallback: $this->template->max_width ?: 1),
            'max_depth' => $this->normalizedDecimal($this->maxDepth, fallback: $this->template->max_depth ?: 1),
            'max_height' => $this->normalizedDecimal($this->maxHeight, fallback: $this->template->max_height ?: 1),
            'max_boxes' => $this->normalizedInteger($this->maxBoxes, fallback: $this->template->max_boxes ?: 40),
            'allowed_colors' => $this->allowedColors,
            'ai_reference_images' => $referenceImages,
            'ai_instruction_log' => $this->instructionLog,
            'ai_draft_definition' => $this->currentDefinition,
        ];

        if ($this->glbModel) {
            $path = $this->glbModel->store('immersive-object-templates/models', 'public');

            if (is_string($path)) {
                if (filled($this->template->model_path) && $this->template->model_path !== $path) {
                    Storage::disk('public')->delete($this->template->model_path);
                }

                $attributes['model_path'] = $path;
            }
        }

        return $attributes;
    }

    protected function persistTemplateState(array $attributes): void
    {
        $this->template->forceFill($attributes)->save();
        $this->template->refresh();
    }

    protected function appendLog(string $role, string $text): void
    {
        $this->instructionLog[] = [
            'role' => $role,
            'text' => $text,
            'at' => now()->toTimeString(),
        ];

        $this->persistTemplateState([
            'ai_instruction_log' => $this->instructionLog,
        ]);
    }

    protected function normalizedDecimal(mixed $value, float $fallback = 1): float
    {
        if (! is_numeric($value)) {
            return round($fallback, 3);
        }

        return round(max(0.001, (float) $value), 3);
    }

    protected function normalizedInteger(mixed $value, int $fallback = 40): int
    {
        if (! is_numeric($value)) {
            return max(1, $fallback);
        }

        return max(1, (int) $value);
    }

    protected function formatDecimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
