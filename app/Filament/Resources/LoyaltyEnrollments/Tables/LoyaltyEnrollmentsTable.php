<?php

namespace App\Filament\Resources\LoyaltyEnrollments\Tables;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Loyalty\Actions\AdminReviewLoyaltyEnrollment;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LoyaltyEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['business.municipality', 'responsible']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('business.municipality.name')
                    ->label('Municipio')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        LoyaltyEnrollment::ACTIVA => 'success',
                        LoyaltyEnrollment::PENDIENTE => 'gray',
                        LoyaltyEnrollment::SUSPENDIDA => 'danger',
                        LoyaltyEnrollment::RETIRADA => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        LoyaltyEnrollment::ACTIVA => 'Activa',
                        LoyaltyEnrollment::PENDIENTE => 'Pendiente de política',
                        LoyaltyEnrollment::SUSPENDIDA => 'Suspendida',
                        LoyaltyEnrollment::RETIRADA => 'Retirada',
                        default => $state,
                    }),
                TextColumn::make('budget_cents')
                    ->label('Presupuesto mensual')
                    ->formatStateUsing(fn (?int $state) => $state ? '$'.number_format($state / 100, 0, ',', '.') : '—'),
                // F4.3: "métricas" — datos reales por negocio, calculados al
                // vuelo. Tabla de administración, no una vista de alto
                // tráfico, así que una consulta extra por fila es aceptable.
                TextColumn::make('points_issued')
                    ->label('Puntos emitidos')
                    ->state(fn (LoyaltyEnrollment $record) => (int) LoyaltyMovement::whereIn('account_id', $record->business->loyaltyAccounts()->pluck('id'))
                        ->where('type', LoyaltyMovement::ACUMULACION)
                        ->sum('points')),
                TextColumn::make('redemptions_delivered')
                    ->label('Canjes entregados')
                    ->state(fn (LoyaltyEnrollment $record) => LoyaltyRedemption::whereIn('account_id', $record->business->loyaltyAccounts()->pluck('id'))
                        ->where('status', LoyaltyRedemption::ENTREGADO)
                        ->count()),
                TextColumn::make('responsible.name')
                    ->label('Responsable')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('consented_at')
                    ->label('Adherido')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        LoyaltyEnrollment::ACTIVA => 'Activa',
                        LoyaltyEnrollment::PENDIENTE => 'Pendiente de política',
                        LoyaltyEnrollment::SUSPENDIDA => 'Suspendida',
                        LoyaltyEnrollment::RETIRADA => 'Retirada',
                    ]),
                Filter::make('municipio')
                    ->form([
                        Select::make('municipality_id')
                            ->label('Municipio')
                            ->options(fn () => Municipality::where('is_active', true)->orderBy('name')->pluck('name', 'id')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['municipality_id'] ?? null,
                        fn (Builder $q, $municipalityId) => $q->whereHas('business', fn (Builder $b) => $b->where('municipality_id', $municipalityId)),
                    )),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->visible(fn (LoyaltyEnrollment $record) => $record->status === LoyaltyEnrollment::ACTIVA)
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')->label('Motivo')->required(),
                    ])
                    ->action(function (LoyaltyEnrollment $record, array $data) {
                        try {
                            app(AdminReviewLoyaltyEnrollment::class)->suspend($record, Auth::user(), $data['reason']);
                        } catch (LoyaltyActionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Adhesión suspendida')->success()->send();
                    }),
                Action::make('reactivate')
                    ->label('Reactivar')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (LoyaltyEnrollment $record) => $record->status === LoyaltyEnrollment::SUSPENDIDA)
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')->label('Motivo')->required(),
                    ])
                    ->action(function (LoyaltyEnrollment $record, array $data) {
                        try {
                            app(AdminReviewLoyaltyEnrollment::class)->reactivate($record, Auth::user(), $data['reason']);
                        } catch (LoyaltyActionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Adhesión reactivada')->success()->send();
                    }),
            ]);
    }
}
