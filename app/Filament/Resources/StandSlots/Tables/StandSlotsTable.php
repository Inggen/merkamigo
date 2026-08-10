<?php

namespace App\Filament\Resources\StandSlots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StandSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('zone.name')
                    ->label('Zona')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zone.plaza.name')
                    ->label('Plaza'),
                TextColumn::make('template.name')
                    ->label('Plantilla')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disponible' => 'success',
                        'ocupada' => 'info',
                        'bloqueada' => 'gray',
                        'invalida' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label('Origen')
                    ->formatStateUsing(fn (string $state): string => $state === 'auto_detected' ? 'Detección automática' : 'Manual')
                    ->badge(),
                IconColumn::make('accessible')
                    ->label('Accesible')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('stand_zone_id')
                    ->label('Zona')
                    ->relationship('zone', 'name'),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'ocupada' => 'Ocupada',
                        'bloqueada' => 'Bloqueada',
                        'invalida' => 'Inválida',
                    ]),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options(['manual' => 'Manual', 'auto_detected' => 'Detección automática']),
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
