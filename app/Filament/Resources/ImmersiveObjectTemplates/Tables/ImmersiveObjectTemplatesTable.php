<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Tables;

use App\Filament\Resources\ImmersiveObjectTemplates\ImmersiveObjectTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
