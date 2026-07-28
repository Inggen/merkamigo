<?php

namespace App\Filament\Resources\Products\Tables;

use App\Domain\Moderation\Actions\RestoreProduct;
use App\Domain\Moderation\Actions\SuspendProduct;
use App\Domain\Storefronts\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * 1.9 del TODO: moderación de productos e imágenes.
 */
class ProductsTable
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
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'publicado' => 'success',
                        'archivado' => 'gray',
                        'agotado' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('suspended_at')
                    ->label('Suspendido')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'publicado' => 'Publicado',
                        'agotado' => 'Agotado',
                        'archivado' => 'Archivado',
                    ]),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Product $record) => ! $record->isSuspended() && $record->isPublished())
                    ->form([
                        Select::make('reason')
                            ->label('Motivo')
                            ->options(self::SUSPENSION_REASONS)
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data) {
                        app(SuspendProduct::class)->handle($record, Auth::user(), self::SUSPENSION_REASONS[$data['reason']]);

                        Notification::make()->title('Producto suspendido')->success()->send();
                    }),
                Action::make('restore')
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Product $record) => $record->isSuspended())
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        app(RestoreProduct::class)->handle($record, Auth::user());

                        Notification::make()->title('Producto restaurado')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}
