<?php

namespace App\Filament\Resources\Businesses\Tables;

use App\Domain\Businesses\Models\Business;
use App\Domain\Moderation\Actions\RestoreBusiness;
use App\Domain\Moderation\Actions\SuspendBusiness;
use App\Domain\Storefronts\Jobs\DeleteProductFromGoogleMerchant;
use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * 1.9 del TODO: revisión, publicación, suspensión y restauración de
 * vitrinas, y configuración de destacados manuales.
 */
class BusinessesTable
{
    private const SUSPENSION_REASONS = [
        'contenido_inapropiado' => 'Contenido inapropiado',
        'informacion_falsa' => 'Información falsa o engañosa',
        'incumple_reglas' => 'Incumple las reglas de comunidad',
        'solicitud_propietario' => 'Solicitud del propietario',
        'otro' => 'Otro',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Negocio')
                    ->searchable(),
                TextColumn::make('municipality.name')
                    ->label('Municipio'),
                TextColumn::make('municipalities.name')
                    ->label('Municipios adicionales')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category.name')
                    ->label('Categoría'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'publicado' => 'success',
                        'suspendido' => 'danger',
                        'archivado' => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->state(fn (Business $record) => $record->isFeatured())
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('google_merchant_enabled')
                    ->label('Google Shopping')
                    ->tooltip('Habilita el envío automático de los productos de este negocio a Google Merchant Center.')
                    ->afterStateUpdated(function (Business $record, bool $state) {
                        $record->products()
                            ->where('type', 'producto')
                            ->pluck('id')
                            ->each(fn (int $productId) => $state
                                ? SyncProductToGoogleMerchant::dispatch($productId)
                                : DeleteProductFromGoogleMerchant::dispatch($productId));
                    }),
                TextColumn::make('google_merchant_status')
                    ->label('Estado Google')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'activo' => 'success',
                        'pendiente' => 'info',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'activo' => 'Activo',
                        'pendiente' => 'Pendiente',
                        'error' => 'Error',
                        default => 'No configurado',
                    })
                    ->tooltip(fn (Business $record) => $record->google_merchant_error)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'pendiente_revision' => 'Pendiente de revisión',
                        'publicado' => 'Publicado',
                        'suspendido' => 'Suspendido',
                        'archivado' => 'Archivado',
                    ]),
                SelectFilter::make('municipality')
                    ->label('Municipio')
                    ->relationship('municipality', 'name'),
                TernaryFilter::make('google_merchant_enabled')
                    ->label('Google Shopping'),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Business $record) => $record->isPublished())
                    ->form([
                        Select::make('reason')
                            ->label('Motivo')
                            ->options(self::SUSPENSION_REASONS)
                            ->required(),
                    ])
                    ->action(function (Business $record, array $data) {
                        app(SuspendBusiness::class)->handle($record, Auth::user(), self::SUSPENSION_REASONS[$data['reason']]);

                        Notification::make()->title('Negocio suspendido')->success()->send();
                    }),
                Action::make('restore')
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Business $record) => $record->isSuspended())
                    ->requiresConfirmation()
                    ->action(function (Business $record) {
                        app(RestoreBusiness::class)->handle($record, Auth::user());

                        Notification::make()->title('Negocio restaurado')->success()->send();
                    }),
                Action::make('feature')
                    ->label('Destacar')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Business $record) => $record->isPublished() && ! $record->isFeatured())
                    ->form([
                        DateTimePicker::make('featured_until')
                            ->label('Destacado hasta')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(function (Business $record, array $data) {
                        $record->update(['featured_until' => $data['featured_until']]);

                        Notification::make()->title('Negocio destacado')->success()->send();
                    }),
                Action::make('unfeature')
                    ->label('Quitar destacado')
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->visible(fn (Business $record) => $record->isFeatured())
                    ->action(fn (Business $record) => $record->update(['featured_until' => null])),
                EditAction::make(),
            ]);
    }
}
