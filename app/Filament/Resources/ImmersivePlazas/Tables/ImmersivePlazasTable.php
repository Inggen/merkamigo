<?php

namespace App\Filament\Resources\ImmersivePlazas\Tables;

use App\Filament\Resources\ImmersivePlazas\ImmersivePlazaResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImmersivePlazasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('reference_image_path')
                    ->label('Imagen')
                    ->disk('public'),
                TextColumn::make('experience.name')
                    ->label('Experiencia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('order')
                    ->label('Orden')
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Capacidad')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('published_at')
                    ->label('Publicada')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('immersive_experience_id')
                    ->label('Experiencia')
                    ->relationship('experience', 'name'),
            ])
            ->recordActions([
                ImmersivePlazaResource::enterExperienceAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
