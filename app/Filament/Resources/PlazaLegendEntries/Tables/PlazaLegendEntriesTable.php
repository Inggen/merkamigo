<?php

namespace App\Filament\Resources\PlazaLegendEntries\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlazaLegendEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color_hex')
                    ->label('Color'),
                TextColumn::make('plaza.name')
                    ->label('Plaza')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('detected_pixel_count')
                    ->label('Muestras'),
                TextColumn::make('template.name')
                    ->label('Objeto asignado')
                    ->placeholder('Sin asignar'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'confirmado' ? 'Confirmado' : 'Pendiente')
                    ->color(fn (string $state): string => $state === 'confirmado' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('immersive_plaza_id')
                    ->label('Plaza')
                    ->relationship('plaza', 'name'),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Ignorar')
                    ->tooltip('Descarta este color si es ruido detectado por error (ej. un ícono pequeño de la leyenda).'),
            ]);
    }
}
