<?php

namespace App\Filament\Resources\CommissionCharges\Tables;

use App\Domain\Marketplace\Actions\ChargeCommission;
use App\Domain\Marketplace\Models\CommissionCharge;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class CommissionChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('period_start')
                    ->label('Desde')
                    ->date(),
                TextColumn::make('period_end')
                    ->label('Hasta')
                    ->date(),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->numeric(),
                TextColumn::make('gross_amount_cents')
                    ->label('GMV del período')
                    ->formatStateUsing(fn (int $state) => '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('commission_cents')
                    ->label('Comisión')
                    ->formatStateUsing(fn (int $state) => '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        CommissionCharge::PAGADA => 'success',
                        CommissionCharge::FALLIDA => 'danger',
                        CommissionCharge::PENDIENTE_COBRO => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('retry_count')
                    ->label('Reintentos')
                    ->visible(fn ($record) => ! $record || $record->status === CommissionCharge::FALLIDA),
                IconColumn::make('needs_manual_attention')
                    ->label('Necesita atención')
                    ->boolean()
                    ->getStateUsing(fn (CommissionCharge $record) => $record->needsManualAttention())
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('next_retry_at')
                    ->label('Próximo reintento')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        CommissionCharge::ABIERTA => 'Abierta',
                        CommissionCharge::PENDIENTE_COBRO => 'Pendiente de cobro',
                        CommissionCharge::PAGADA => 'Pagada',
                        CommissionCharge::FALLIDA => 'Fallida',
                    ]),
                TernaryFilter::make('needs_manual_attention')
                    ->label('Necesita atención')
                    ->queries(
                        true: fn ($query) => $query->where('status', CommissionCharge::FALLIDA)->whereNull('next_retry_at'),
                        false: fn ($query) => $query->where(fn ($q) => $q->where('status', '!=', CommissionCharge::FALLIDA)->orWhereNotNull('next_retry_at')),
                    ),
            ])
            ->recordActions([
                Action::make('chargeNow')
                    ->label('Cobrar ahora')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->visible(fn (CommissionCharge $record) => in_array($record->status, [CommissionCharge::ABIERTA, CommissionCharge::FALLIDA], true)
                        && (auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->action(function (CommissionCharge $record) {
                        try {
                            app(ChargeCommission::class)->handle($record);
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        if ($record->fresh()->status === CommissionCharge::PAGADA) {
                            Notification::make()->title('Comisión cobrada correctamente')->success()->send();
                        } else {
                            Notification::make()->title('El cobro no se aprobó — queda programado un reintento')->warning()->send();
                        }
                    }),
            ]);
    }
}
