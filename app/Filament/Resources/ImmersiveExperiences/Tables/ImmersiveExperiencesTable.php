<?php

namespace App\Filament\Resources\ImmersiveExperiences\Tables;

use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\ImmersiveExperienceResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImmersiveExperiencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Miniatura')
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('municipality.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'borrador' => 'Borrador',
                        'validando' => 'Validando',
                        'publicada' => 'Publicada',
                        'pausada' => 'Pausada',
                        'archivada' => 'Archivada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'publicada' => 'success',
                        'validando' => 'warning',
                        'pausada' => 'gray',
                        'archivada' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('publishedVersion.version')
                    ->label('Versión publicada')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('municipality_id')
                    ->label('Municipio')
                    ->relationship('municipality', 'name'),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'validando' => 'Validando',
                        'publicada' => 'Publicada',
                        'pausada' => 'Pausada',
                        'archivada' => 'Archivada',
                    ]),
            ])
            ->recordActions([
                self::duplicateAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * IMM-010 — "el administrador crea, edita, duplica...". Clona
     * experiencia + plazas + zonas + slots + elementos como borrador nuevo
     * (`ImmersiveExperience::duplicate()`) y lleva directo a editarlo.
     */
    private static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicar')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Crea una copia en borrador con las mismas plazas, zonas y slots.')
            ->action(function (ImmersiveExperience $record) {
                $copy = $record->duplicate();

                Notification::make()
                    ->title("Duplicada como \"{$copy->name}\"")
                    ->success()
                    ->send();

                return redirect(ImmersiveExperienceResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
