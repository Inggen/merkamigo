<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Domain\Platform\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Acción')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')
                    ->label('ID'),
                TextColumn::make('ip_address')
                    ->label('IP'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Acción')
                    ->options(fn () => AuditLog::query()
                        ->distinct()
                        ->pluck('action', 'action')
                        ->all()),
            ]);
    }
}
