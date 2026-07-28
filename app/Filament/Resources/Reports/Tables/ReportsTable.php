<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reportable')
                    ->label('Contenido reportado')
                    ->state(fn (Report $record) => $record->reportableLabel()),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable(),
                TextColumn::make('reporter_email')
                    ->label('Correo del reportante')
                    ->placeholder('Anónimo'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Report::RESUELTO => 'success',
                        Report::DESCARTADO => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Reportado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Report::PENDIENTE => 'Pendiente',
                        Report::RESUELTO => 'Resuelto',
                        Report::DESCARTADO => 'Descartado',
                    ]),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Resolver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Report $record) => $record->status === Report::PENDIENTE)
                    ->form([
                        Textarea::make('note')->label('Nota de resolución'),
                    ])
                    ->action(function (Report $record, array $data) {
                        app(ResolveReport::class)->handle($record, Auth::user(), Report::RESUELTO, $data['note'] ?? null);

                        Notification::make()->title('Reporte resuelto')->success()->send();
                    }),
                Action::make('dismiss')
                    ->label('Descartar')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (Report $record) => $record->status === Report::PENDIENTE)
                    ->form([
                        Textarea::make('note')->label('Nota (opcional)'),
                    ])
                    ->action(function (Report $record, array $data) {
                        app(ResolveReport::class)->handle($record, Auth::user(), Report::DESCARTADO, $data['note'] ?? null);

                        Notification::make()->title('Reporte descartado')->success()->send();
                    }),
            ]);
    }
}
