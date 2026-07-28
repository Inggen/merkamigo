<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('experience')
                    ->label('Experiencia')
                    ->badge(),
                TextColumn::make('platform_role')
                    ->label('Rol de plataforma')
                    ->state(fn (User $record) => $record->platformRoleName() ?? '—')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('experience')
                    ->options(['cliente' => 'Cliente', 'emprendedor' => 'Emprendedor']),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
