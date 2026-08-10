<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Pages;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditImmersiveObjectTemplateSpatialEditor extends Page
{
    use InteractsWithRecord {
        getRecord as getBaseRecord;
    }

    protected static string $resource = ImmersiveObjectTemplateResource::class;

    protected string $view = 'filament.immersive.object-template-spatial-editor-page';

    protected Width|string|null $maxContentWidth = 'w-full max-w-none';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(ImmersiveObjectTemplateResource::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string
    {
        return 'Editar Objeto: '.$this->getRecord()->name;
    }

    public function getRecord(): ImmersiveObjectTemplate
    {
        /** @var ImmersiveObjectTemplate */
        return $this->getBaseRecord();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateObject')
                ->label('Generar objeto')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->url(ImmersiveObjectTemplateResource::getUrl('edit', ['record' => $this->getRecord()]).'#generar-objeto'),
            // Pedido del usuario: comparar rápido las cajas contra la foto
            // de referencia (Miniatura) sin salir del editor de cajas.
            Action::make('viewThumbnailPreview')
                ->label('Vista previa')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading('Vista previa: '.$this->getRecord()->name)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('4xl')
                ->modalContent(fn () => view('filament.immersive.object-template-preview-modal', [
                    'template' => $this->getRecord(),
                ])),
            Action::make('editObject')
                ->label('Editar')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('danger')
                ->url(ImmersiveObjectTemplateResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
