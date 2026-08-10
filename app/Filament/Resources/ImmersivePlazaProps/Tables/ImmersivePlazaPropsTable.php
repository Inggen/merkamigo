<?php

namespace App\Filament\Resources\ImmersivePlazaProps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImmersivePlazaPropsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plaza.name')
                    ->label('Plaza')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('template.name')
                    ->label('Objeto')
                    ->searchable(),
                TextColumn::make('template.category')
                    ->label('Categoría')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->formatStateUsing(fn (string $state): string => $state === 'auto_detected' ? 'Detección automática' : 'Manual')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('immersive_plaza_id')
                    ->label('Plaza')
                    ->relationship('plaza', 'name'),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(['borrador' => 'Borrador', 'confirmado' => 'Confirmado']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
