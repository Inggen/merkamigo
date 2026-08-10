<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Pages;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditImmersiveObjectTemplate extends EditRecord
{
    protected static string $resource = ImmersiveObjectTemplateResource::class;

    protected function getHeaderActions(): array
    {
        /** @var ImmersiveObjectTemplate $record */
        $record = $this->getRecord();

        // Un objeto con GLB cargado en modo "Modelo 3D" se renderiza SIEMPRE
        // con ese GLB (prioridad GLB > definición voxel > builder — ver
        // `renderObjectByPriority`), así que el editor de cajas y el
        // generador IA no tienen ningún efecto visible en la escena para
        // él: se ocultan y se ofrece en su lugar previsualizar el GLB real.
        $hasGlb = $record->asset_input_mode === 'model_3d' && filled($record->model_path);

        return [
            Action::make('viewGlb')
                ->label('Ver Objeto GLB')
                ->icon(Heroicon::OutlinedCube)
                ->color('gray')
                ->visible($hasGlb)
                ->modalHeading('Objeto GLB: '.$record->name)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.immersive.object-template-glb-viewer', [
                    'template' => $record,
                ])),
            Action::make('objectEditor')
                ->label('Editar Objeto')
                ->icon(Heroicon::OutlinedCube)
                ->color('gray')
                ->visible(! $hasGlb)
                ->url(ImmersiveObjectTemplateResource::getUrl('object-editor', ['record' => $record])),
            Action::make('generarIa')
                ->label('Generar objeto')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->visible(! $hasGlb)
                ->modalHeading('Generar objeto con IA')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.immersive.object-template-ai-modal', [
                    'template' => $record,
                ])),
            DeleteAction::make(),
        ];
    }
}
