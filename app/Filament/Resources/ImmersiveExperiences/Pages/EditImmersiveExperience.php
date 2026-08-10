<?php

namespace App\Filament\Resources\ImmersiveExperiences\Pages;

use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\ImmersiveExperienceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditImmersiveExperience extends EditRecord
{
    protected static string $resource = ImmersiveExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->previewAction(),
            $this->publishAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * Pedido explícito del usuario: poder "entrar" a la experiencia antes
     * de publicar, no solo ver la vista previa 2D/el editor espacial de
     * arriba. Abre la escena real (`route_name`) en una pestaña nueva con
     * `?preview=1` — `ImmersiveExperience::previewUrl()`/`PlazaController`
     * se encargan de que solo un administrador autenticado pueda ver una
     * experiencia todavía no publicada por esa vía.
     */
    private function previewAction(): Action
    {
        return Action::make('preview3d')
            ->label('Entrar a la experiencia')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (ImmersiveExperience $record): ?string => $record->previewUrl())
            ->openUrlInNewTab()
            ->visible(fn (ImmersiveExperience $record): bool => filled($record->previewUrl()));
    }

    /**
     * IMM-014 del TODO inmersivo: publicar siempre crea una fila en
     * `experience_versions` con una foto fija de la configuración actual —
     * es la única vía para que una experiencia quede en estado "publicada",
     * para que jamás se publique sin dejar rastro reversible. La lógica en
     * sí vive en `ImmersiveExperience::publish()` para poder reutilizarla
     * desde la reversión a una versión anterior.
     */
    private function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Publicar versión')
            ->icon(Heroicon::OutlinedRocketLaunch)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Se creará una nueva versión publicada con la configuración actual. Los visitantes verán este cambio de inmediato.')
            ->visible(fn (ImmersiveExperience $record): bool => $record->status !== 'publicada')
            ->action(function (ImmersiveExperience $record): void {
                $version = $record->publish(auth()->user());

                Notification::make()
                    ->title("Experiencia publicada — versión {$version->version}")
                    ->success()
                    ->send();
            });
    }
}
