<?php

namespace App\Filament\Resources\ImmersivePlazas\Pages;

use App\Domain\Immersive\Actions\DetectPlazaLegendColors;
use App\Domain\Immersive\Actions\GeneratePlazaLayoutFromImage;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Resources\ImmersivePlazas\ImmersivePlazaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

class EditImmersivePlaza extends EditRecord
{
    protected static string $resource = ImmersivePlazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImmersivePlazaResource::spatialEditorAction(),
            ImmersivePlazaResource::enterExperienceAction(),
            $this->detectLegendAction(),
            $this->generateLayoutAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * IMM-013 redefinido: primer paso del flujo — leer los colores de la
     * imagen de leyenda y dejarlos como `PlazaLegendEntry` pendientes de
     * mapear. Ver `Filament\Resources\PlazaLegendEntries`.
     */
    private function detectLegendAction(): Action
    {
        return Action::make('detectLegend')
            ->label('Detectar leyenda')
            ->icon(Heroicon::OutlinedEyeDropper)
            ->color('gray')
            ->visible(fn (ImmersivePlaza $record): bool => filled($record->legend_image_path))
            ->action(function (ImmersivePlaza $record): void {
                try {
                    $summary = (new DetectPlazaLegendColors)->execute($record);
                } catch (RuntimeException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title('Leyenda detectada')
                    ->body("{$summary['detected']} colores encontrados — {$summary['created']} nuevos, {$summary['updated']} ya existían. Mapéalos en \"Leyenda de colores\" antes de generar.")
                    ->success()
                    ->send();
            });
    }

    /**
     * IMM-013 redefinido: segundo paso — con la leyenda ya confirmada,
     * detecta las manchas del plano y crea los stands/elementos en
     * borrador (`source = 'auto_detected'`) para que el administrador los
     * revise.
     */
    private function generateLayoutAction(): Action
    {
        return Action::make('generateLayout')
            ->label('Generar ubicaciones')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Lee el plano y crea un stand o elemento por cada mancha de color detectada, en borrador para revisión.')
            ->visible(fn (ImmersivePlaza $record): bool => filled($record->reference_image_path) && $record->legendIsFullyConfirmed())
            ->action(function (ImmersivePlaza $record): void {
                try {
                    $summary = (new GeneratePlazaLayoutFromImage)->execute($record);
                } catch (RuntimeException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                $body = "{$summary['stands_created']} stands y {$summary['props_created']} elementos creados.";

                if ($summary['skipped'] !== []) {
                    $body .= ' Omitidos: '.count($summary['skipped']).' — revisa el detalle en los registros.';
                }

                Notification::make()
                    ->title('Ubicaciones generadas')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }
}
