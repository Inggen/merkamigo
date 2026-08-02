<?php

namespace App\Filament\Resources\WebhookSubscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebhookSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->placeholder('Todos los negocios')
                    ->searchable(),
                TextColumn::make('url')
                    ->limit(50),
                TextColumn::make('subscribed_events')
                    ->label('Eventos')
                    ->badge()
                    ->limitList(3),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
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
