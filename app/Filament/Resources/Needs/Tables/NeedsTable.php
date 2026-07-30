<?php

namespace App\Filament\Resources\Needs\Tables;

use App\Domain\Moderation\Actions\RestoreNeed;
use App\Domain\Moderation\Actions\SuspendNeed;
use App\Domain\Needs\Models\Need;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * 2.1 del TODO: moderación de necesidades y reportes.
 */
class NeedsTable
{
    private const SUSPENSION_REASONS = [
        'contenido_inapropiado' => 'Contenido inapropiado',
        'informacion_falsa' => 'Información falsa o engañosa',
        'incumple_reglas' => 'Incumple las reglas de comunidad',
        'otro' => 'Otro',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Comprador')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Necesidad')
                    ->searchable(),
                TextColumn::make('municipality.name')
                    ->label('Municipio'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'publicada', 'recibiendo_ofertas' => 'success',
                        'seleccionada' => 'info',
                        'cerrada' => 'gray',
                        'vencida', 'cancelada' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('offers_count')
                    ->label('Propuestas')
                    ->counts('offers'),
                TextColumn::make('suspended_at')
                    ->label('Suspendida')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'publicada' => 'Publicada',
                        'recibiendo_ofertas' => 'Recibiendo ofertas',
                        'seleccionada' => 'Seleccionada',
                        'cerrada' => 'Cerrada',
                        'vencida' => 'Vencida',
                        'cancelada' => 'Cancelada',
                    ]),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Need $record) => $record->suspended_at === null)
                    ->form([
                        Select::make('reason')
                            ->label('Motivo')
                            ->options(self::SUSPENSION_REASONS)
                            ->required(),
                    ])
                    ->action(function (Need $record, array $data) {
                        app(SuspendNeed::class)->handle($record, Auth::user(), self::SUSPENSION_REASONS[$data['reason']]);

                        Notification::make()->title('Necesidad suspendida')->success()->send();
                    }),
                Action::make('restore')
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Need $record) => $record->suspended_at !== null)
                    ->requiresConfirmation()
                    ->action(function (Need $record) {
                        app(RestoreNeed::class)->handle($record, Auth::user());

                        Notification::make()->title('Necesidad restaurada')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}
