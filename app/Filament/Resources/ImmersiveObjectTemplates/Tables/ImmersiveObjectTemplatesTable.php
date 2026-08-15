<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Tables;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
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

class ImmersiveObjectTemplatesTable
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
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge(),
                TextColumn::make('builder_key')
                    ->label('Forma voxel')
                    ->placeholder('—'),
                TextColumn::make('max_width')
                    ->label('Ancho')
                    ->suffix(' m'),
                TextColumn::make('max_depth')
                    ->label('Profundidad')
                    ->suffix(' m'),
                TextColumn::make('max_boxes')
                    ->label('Máx. bloques')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('asset_input_mode')
                    ->label('Tipo de recurso')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'model_3d' => 'Modelo 3D',
                        'ia_voxel' => 'Objeto Voxel',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'stand' => 'Stand',
                        'construccion' => 'Construcción',
                        'arbol' => 'Árbol',
                        'fuente' => 'Fuente',
                        'monumento' => 'Monumento',
                        'personaje' => 'Personaje',
                    ]),
            ])
            ->recordActions([
                Action::make('generarIa')
                    ->label('Generar objeto')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('gray')
                    ->url(fn ($record) => ImmersiveObjectTemplateResource::getUrl('generar-ia', ['record' => $record])),
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
     * Pedido del usuario: duplicar un elemento del catálogo. Mismo patrón
     * que `ImmersiveExperiencesTable::duplicateAction()` — copia borrador
     * nueva vía `ImmersiveObjectTemplate::duplicate()` y lleva directo a
     * editarla.
     */
    private static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicar')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Crea una copia en borrador con los mismos campos.')
            ->action(function (ImmersiveObjectTemplate $record) {
                $copy = $record->duplicate();

                Notification::make()
                    ->title("Duplicado como \"{$copy->name}\"")
                    ->success()
                    ->send();

                return redirect(ImmersiveObjectTemplateResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
