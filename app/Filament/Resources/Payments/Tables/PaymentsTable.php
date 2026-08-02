<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Domain\Billing\Actions\RefundPayment;
use App\Domain\Billing\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable(),
                TextColumn::make('amount_cents')
                    ->label('Monto')
                    ->formatStateUsing(fn (int $state) => '$'.number_format($state / 100, 0, ',', '.').' COP'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Payment::APROBADO => 'success',
                        Payment::RECHAZADO => 'danger',
                        Payment::REEMBOLSADO => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—'),
                TextColumn::make('billingProduct.name')
                    ->label('Producto')
                    ->placeholder('—'),
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Payment::PENDIENTE => 'Pendiente',
                        Payment::EN_PROCESO => 'En proceso',
                        Payment::APROBADO => 'Aprobado',
                        Payment::RECHAZADO => 'Rechazado',
                        Payment::REEMBOLSADO => 'Reembolsado',
                    ]),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label('Reembolsar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Payment $record) => $record->status === Payment::APROBADO
                        && (auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        app(RefundPayment::class)->handle($record, Auth::user());

                        Notification::make()->title('Pago reembolsado')->success()->send();
                    }),
            ]);
    }
}
