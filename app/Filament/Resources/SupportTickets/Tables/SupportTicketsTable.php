<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Domain\Moderation\Actions\ResolveSupportTicket;
use App\Domain\Moderation\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable(),
                TextColumn::make('contact')
                    ->label('Contacto')
                    ->state(fn (SupportTicket $record) => $record->contactLabel()),
                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        SupportTicket::RESUELTO => 'success',
                        SupportTicket::CERRADO => 'gray',
                        SupportTicket::EN_PROGRESO => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Recibida')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        SupportTicket::PENDIENTE => 'Pendiente',
                        SupportTicket::EN_PROGRESO => 'En progreso',
                        SupportTicket::RESUELTO => 'Resuelto',
                        SupportTicket::CERRADO => 'Cerrado',
                    ]),
            ])
            ->recordActions([
                Action::make('in_progress')
                    ->label('Marcar en progreso')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (SupportTicket $record) => $record->status === SupportTicket::PENDIENTE)
                    ->action(function (SupportTicket $record) {
                        app(ResolveSupportTicket::class)->handle($record, Auth::user(), SupportTicket::EN_PROGRESO, null);

                        Notification::make()->title('Solicitud marcada en progreso')->success()->send();
                    }),
                Action::make('resolve')
                    ->label('Resolver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SupportTicket $record) => in_array($record->status, [SupportTicket::PENDIENTE, SupportTicket::EN_PROGRESO], true))
                    ->form([
                        Textarea::make('note')->label('Nota de resolución'),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        app(ResolveSupportTicket::class)->handle($record, Auth::user(), SupportTicket::RESUELTO, $data['note'] ?? null);

                        Notification::make()->title('Solicitud resuelta')->success()->send();
                    }),
                Action::make('close')
                    ->label('Cerrar')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (SupportTicket $record) => ! in_array($record->status, [SupportTicket::CERRADO], true))
                    ->form([
                        Textarea::make('note')->label('Nota (opcional)'),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        app(ResolveSupportTicket::class)->handle($record, Auth::user(), SupportTicket::CERRADO, $data['note'] ?? null);

                        Notification::make()->title('Solicitud cerrada')->success()->send();
                    }),
            ]);
    }
}
