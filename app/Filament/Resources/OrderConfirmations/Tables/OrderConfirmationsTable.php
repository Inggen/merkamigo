<?php

namespace App\Filament\Resources\OrderConfirmations\Tables;

use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Models\OrderConfirmation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrderConfirmationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')->label('Negocio')->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        OrderConfirmation::CONFIRMADO, OrderConfirmation::COMPLETADO => 'success',
                        OrderConfirmation::EN_DISPUTA => 'danger',
                        OrderConfirmation::CANCELADO => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('is_reputation_eligible')->label('Cuenta para reputación')->boolean(),
                TextColumn::make('customer_confirmed_at')->label('Cliente confirma')->dateTime()->placeholder('Pendiente'),
                TextColumn::make('business_confirmed_at')->label('Negocio confirma')->dateTime()->placeholder('Pendiente'),
                TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        OrderConfirmation::PENDIENTE => 'Pendiente',
                        OrderConfirmation::CONFIRMADO => 'Confirmado por ambos',
                        OrderConfirmation::COMPLETADO => 'Completado',
                        OrderConfirmation::CANCELADO => 'Cancelado',
                        OrderConfirmation::EN_DISPUTA => 'En disputa',
                    ]),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (OrderConfirmation $record) => $record->status === OrderConfirmation::CONFIRMADO)
                    ->requiresConfirmation()
                    ->action(function (OrderConfirmation $record) {
                        app(ConfirmOrder::class)->complete($record, Auth::user());

                        Notification::make()->title('Pedido confirmado como completado')->success()->send();
                    }),
                Action::make('dispute')
                    ->label('Marcar disputa')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (OrderConfirmation $record) => $record->status !== OrderConfirmation::COMPLETADO)
                    ->requiresConfirmation()
                    ->action(function (OrderConfirmation $record) {
                        app(ConfirmOrder::class)->markDisputed($record, Auth::user());

                        Notification::make()->title('Pedido marcado en disputa')->success()->send();
                    }),
            ]);
    }
}
