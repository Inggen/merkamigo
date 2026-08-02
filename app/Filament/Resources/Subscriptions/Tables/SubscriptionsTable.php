<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Domain\Billing\Actions\CancelSubscription;
use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Platform\Actions\RecordAuditLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plan'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Subscription::ACTIVA => 'success',
                        Subscription::PRUEBA => 'info',
                        Subscription::EN_GRACIA => 'warning',
                        Subscription::SUSPENDIDA, Subscription::CANCELADA => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('trial_ends_at')
                    ->label('Prueba hasta')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('current_period_ends_at')
                    ->label('Periodo hasta')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Subscription::PRUEBA => 'Prueba',
                        Subscription::ACTIVA => 'Activa',
                        Subscription::EN_GRACIA => 'En gracia',
                        Subscription::SUSPENDIDA => 'Suspendida',
                        Subscription::CANCELADA => 'Cancelada',
                    ]),
            ])
            ->recordActions([
                Action::make('change_plan')
                    ->label('Cambiar plan')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Select::make('plan_id')
                            ->label('Nuevo plan')
                            ->options(fn () => Plan::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $plan = Plan::findOrFail((int) $data['plan_id']);
                        app(SubscribeToPlan::class)->handle($record->business, $plan, Auth::user());

                        Notification::make()->title('Plan actualizado')->success()->send();
                    }),
                Action::make('extend_trial')
                    ->label('Extender prueba')
                    ->icon('heroicon-o-clock')
                    ->visible(fn (Subscription $record) => $record->status === Subscription::PRUEBA)
                    ->form([
                        DateTimePicker::make('trial_ends_at')->required(),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update(['trial_ends_at' => $data['trial_ends_at']]);

                        app(RecordAuditLog::class)->handle(Auth::user(), 'subscription.trial_extended', $record, [
                            'business_id' => $record->business_id,
                        ]);

                        Notification::make()->title('Prueba extendida')->success()->send();
                    }),
                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => $record->status !== Subscription::SUSPENDIDA)
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        $record->update(['status' => Subscription::SUSPENDIDA]);

                        app(RecordAuditLog::class)->handle(Auth::user(), 'subscription.suspended', $record, [
                            'business_id' => $record->business_id,
                        ]);

                        Notification::make()->title('Suscripción suspendida')->success()->send();
                    }),
                Action::make('reactivate')
                    ->label('Reactivar')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Subscription $record) => $record->status === Subscription::SUSPENDIDA)
                    ->action(function (Subscription $record) {
                        $record->update(['status' => Subscription::ACTIVA]);

                        app(RecordAuditLog::class)->handle(Auth::user(), 'subscription.reactivated', $record, [
                            'business_id' => $record->business_id,
                        ]);

                        Notification::make()->title('Suscripción reactivada')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => ! in_array($record->status, [Subscription::CANCELADA], true))
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        app(CancelSubscription::class)->handle($record, Auth::user());

                        Notification::make()->title('Suscripción cancelada')->success()->send();
                    }),
            ]);
    }
}
