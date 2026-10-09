<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Domain\Marketplace\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable(),
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('buyer.name')
                    ->label('Comprador')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('amount_cents')
                    ->label('Monto')
                    ->formatStateUsing(fn (int $state) => '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('commission_cents')
                    ->label('Comisión')
                    ->formatStateUsing(fn (int $state) => '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Order::PAGADO => 'success',
                        Order::RECHAZADO => 'danger',
                        Order::REEMBOLSADO => 'gray',
                        Order::CANCELADO => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('commission_charge_id')
                    ->label('Comisión asignada')
                    ->placeholder('Sin asignar')
                    ->formatStateUsing(fn (?int $state) => $state ? "Cargo #{$state}" : null),
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Order::PENDIENTE => 'Pendiente',
                        Order::PAGADO => 'Pagado',
                        Order::RECHAZADO => 'Rechazado',
                        Order::CANCELADO => 'Cancelado',
                        Order::REEMBOLSADO => 'Reembolsado',
                    ]),
            ]);
    }
}
