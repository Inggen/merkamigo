<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('price_cents')
                    ->label('Precio')
                    ->formatStateUsing(fn (?int $state) => $state === null ? 'Gratis' : '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('billing_period')
                    ->label('Periodicidad'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label('Negocios')
                    ->counts('subscriptions'),
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
