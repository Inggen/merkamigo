<?php

namespace App\Filament\Resources\ImmersiveExperiences\RelationManagers;

use App\Domain\Immersive\Models\ExperienceVersion;
use App\Domain\Immersive\Models\ImmersiveExperience;
use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * IMM-014 del TODO inmersivo: historial de publicación de una experiencia
 * — "borrador, previsualización, publicación y reversión a versión
 * anterior". No hay alta/edición manual (una versión solo la crea
 * `ImmersiveExperience::publish()`); aquí solo se previsualiza cada
 * versión (`config_snapshot`) y se puede revertir a ella.
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versiones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label('Versión')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('author.name')
                    ->label('Autor')
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label('Publicada')
                    ->dateTime(),
                TextColumn::make('checksum')
                    ->label('Checksum')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…')
                    ->copyable(),
            ])
            ->defaultSort('version', 'desc')
            ->headerActions([])
            ->recordActions([
                $this->previewAction(),
                $this->revertAction(),
            ])
            ->toolbarActions([]);
    }

    private function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Ver configuración')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->schema(fn (ExperienceVersion $record): array => [
                KeyValueEntry::make('config_snapshot')
                    ->label('Configuración publicada en esta versión')
                    ->state(self::flattenSnapshot($record->config_snapshot)),
            ]);
    }

    private function revertAction(): Action
    {
        return Action::make('revert')
            ->label('Revertir a esta versión')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Crea una versión NUEVA con la configuración de esta versión anterior (no se reescribe el historial). Las plazas borradas después de esta versión no se recrean.')
            ->visible(fn (ExperienceVersion $record): bool => $record->immersive_experience_id === $this->ownerExperience()->id
                && $record->id !== $this->ownerExperience()->published_version_id)
            ->action(function (ExperienceVersion $record): void {
                $newVersion = $this->ownerExperience()->revertToVersion($record, auth()->user());

                Notification::make()
                    ->title("Revertido — nueva versión publicada: {$newVersion->version}")
                    ->success()
                    ->send();
            });
    }

    private function ownerExperience(): ImmersiveExperience
    {
        /** @var ImmersiveExperience */
        return $this->getOwnerRecord();
    }

    /**
     * `KeyValueEntry` espera pares clave/valor planos — aplana el snapshot
     * (incluidas las plazas anidadas) a algo legible en la previsualización.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string>
     */
    private static function flattenSnapshot(array $snapshot, string $prefix = ''): array
    {
        $flat = [];

        foreach ($snapshot as $key => $value) {
            $label = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += self::flattenSnapshot($value, $label);

                continue;
            }

            $flat[$label] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_null($value) => '—',
                default => (string) $value,
            };
        }

        return $flat;
    }
}
