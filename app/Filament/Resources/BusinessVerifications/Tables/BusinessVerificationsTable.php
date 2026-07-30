<?php

namespace App\Filament\Resources\BusinessVerifications\Tables;

use App\Domain\Trust\Actions\ReviewBusinessVerification;
use App\Domain\Trust\Models\BusinessVerification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BusinessVerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')->label('Negocio')->searchable(),
                TextColumn::make('level')->label('Nivel')->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        BusinessVerification::VERIFICADA => 'success',
                        BusinessVerification::REQUIERE_AJUSTES => 'warning',
                        BusinessVerification::VENCIDA, BusinessVerification::REVOCADA => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('expires_at')->label('Vence')->dateTime()->placeholder('Sin fecha'),
                TextColumn::make('created_at')->label('Solicitado')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        BusinessVerification::SIN_INICIAR => 'Sin iniciar',
                        BusinessVerification::EN_REVISION => 'En revisión',
                        BusinessVerification::REQUIERE_AJUSTES => 'Requiere ajustes',
                        BusinessVerification::VERIFICADA => 'Verificada',
                        BusinessVerification::VENCIDA => 'Vencida',
                        BusinessVerification::REVOCADA => 'Revocada',
                    ]),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Revisar')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->form([
                        Select::make('level')
                            ->options(['basica' => 'Básica', 'avanzada' => 'Avanzada'])
                            ->required(),
                        Select::make('status')
                            ->options([
                                BusinessVerification::EN_REVISION => 'En revisión',
                                BusinessVerification::REQUIERE_AJUSTES => 'Requiere ajustes',
                                BusinessVerification::VERIFICADA => 'Verificada',
                                BusinessVerification::VENCIDA => 'Vencida',
                                BusinessVerification::REVOCADA => 'Revocada',
                            ])
                            ->required(),
                        DateTimePicker::make('expires_at')->label('Vence en'),
                        Textarea::make('review_note')->label('Nota de revisión'),
                    ])
                    ->action(function (BusinessVerification $record, array $data) {
                        app(ReviewBusinessVerification::class)->handle(
                            $record,
                            Auth::user(),
                            $data['status'],
                            $data['review_note'] ?? null,
                            $data['expires_at'] ?? null,
                            $data['level'],
                        );

                        Notification::make()->title('Verificación actualizada')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}
