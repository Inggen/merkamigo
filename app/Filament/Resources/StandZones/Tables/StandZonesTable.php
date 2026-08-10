<?php

namespace App\Filament\Resources\StandZones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StandZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plaza.name')
                    ->label('Plaza')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('default_orientation')
                    ->label('Orientación')
                    ->badge(),
                TextColumn::make('slots_count')
                    ->label('Slots')
                    ->counts('slots'),
                TextColumn::make('min_separation')
                    ->label('Separación mín.')
                    ->suffix(' m'),
                TextColumn::make('priority')
                    ->label('Prioridad'),
            ])
            ->filters([
                SelectFilter::make('immersive_plaza_id')
                    ->label('Plaza')
                    ->relationship('plaza', 'name'),
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
