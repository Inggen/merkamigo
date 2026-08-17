<?php

namespace App\Filament\Resources\Recommendations\Tables;

use App\Domain\Trust\Actions\ModerateRecommendation;
use App\Domain\Trust\Models\Recommendation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RecommendationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('business.name')->label('Negocio')->searchable(),
                TextColumn::make('authorUser.name')->label('Autor')->placeholder('Sin cuenta'),
                TextColumn::make('rating')->label('Calificación')->suffix('/5')->placeholder('Sin calificar')->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Recommendation::PUBLICADA => 'success',
                        Recommendation::OCULTA => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('body')->label('Texto')->limit(80),
                TextColumn::make('created_at')->label('Creada')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Recommendation::PENDIENTE => 'Pendiente',
                        Recommendation::PUBLICADA => 'Publicada',
                        Recommendation::OCULTA => 'Oculta',
                    ]),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publicar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Recommendation $record) => $record->status !== Recommendation::PUBLICADA)
                    ->action(function (Recommendation $record) {
                        app(ModerateRecommendation::class)->handle($record, Auth::user(), Recommendation::PUBLICADA);

                        Notification::make()->title('Recomendación publicada')->success()->send();
                    }),
                Action::make('hide')
                    ->label('Ocultar')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (Recommendation $record) => $record->status !== Recommendation::OCULTA)
                    ->form([
                        Textarea::make('business_response')->label('Nota interna o respuesta del negocio'),
                    ])
                    ->action(function (Recommendation $record, array $data) {
                        app(ModerateRecommendation::class)->handle(
                            $record,
                            Auth::user(),
                            Recommendation::OCULTA,
                            $data['business_response'] ?? null,
                        );

                        Notification::make()->title('Recomendación oculta')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}
