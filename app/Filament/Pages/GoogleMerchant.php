<?php

namespace App\Filament\Pages;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use App\Domain\Storefronts\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Panel de administración de la integración con Google Merchant Center:
 * estado general (tarjetas) + listado de productos elegibles con sus
 * problemas, filtros y acciones para reintentar/sincronizar sin tener
 * que usar la consola. Exclusivo admin/superadmin, igual que el resto de
 * páginas de configuración (`SiteSettings`, etc.).
 *
 * @property-read Table $table
 */
class GoogleMerchant extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static UnitEnum|string|null $navigationGroup = 'Plataforma';

    protected static ?string $navigationLabel = 'Google Merchant';

    protected static ?string $title = 'Google Merchant';

    protected string $view = 'filament.pages.google-merchant';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }

    /**
     * Tarjetas de resumen. "Elegibles" es una aproximación por SQL (tipo
     * producto + publicado + negocio habilitado y publicado) — la
     * elegibilidad exacta (imagen, precio, etc.) la decide
     * `GoogleMerchantProductValidator` por producto; para eso está la
     * columna "Estado" de la tabla de abajo.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $statusCounts = Product::query()
            ->where('type', 'producto')
            ->select('google_merchant_status', DB::raw('count(*) as total'))
            ->groupBy('google_merchant_status')
            ->pluck('total', 'google_merchant_status');

        return [
            'enabled' => config('services.google_merchant.enabled'),
            'negocios_habilitados' => Business::where('google_merchant_enabled', true)->count(),
            'elegibles' => Product::query()
                ->where('type', 'producto')
                ->where('status', 'publicado')
                ->whereHas('business', fn (Builder $query) => $query
                    ->where('google_merchant_enabled', true)
                    ->where('status', 'publicado'))
                ->count(),
            'publicados' => (int) ($statusCounts['publicado'] ?? 0),
            'requiere_ajustes' => (int) ($statusCounts['requiere_ajustes'] ?? 0),
            'error' => (int) ($statusCounts['error'] ?? 0),
            'ultima_sincronizacion' => Product::where('type', 'producto')->max('google_merchant_last_sync_at'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('type', 'producto')
                    ->with('business')
            )
            ->columns([
                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('google_merchant_status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'publicado' => 'success',
                        'pendiente', 'sincronizando' => 'info',
                        'requiere_ajustes' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'publicado' => 'Publicado',
                        'pendiente' => 'Pendiente',
                        'sincronizando' => 'Sincronizando',
                        'requiere_ajustes' => 'Requiere ajustes',
                        'error' => 'Error',
                        default => 'No publicado',
                    })
                    ->sortable(),
                TextColumn::make('google_merchant_last_error')
                    ->label('Motivo')
                    ->limit(60)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('google_merchant_last_sync_at')
                    ->label('Última sincronización')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('google_merchant_status')
                    ->label('Estado')
                    ->options([
                        'no_publicado' => 'No publicado',
                        'pendiente' => 'Pendiente',
                        'sincronizando' => 'Sincronizando',
                        'requiere_ajustes' => 'Requiere ajustes',
                        'publicado' => 'Publicado',
                        'error' => 'Error',
                    ]),
                SelectFilter::make('business_id')
                    ->label('Negocio')
                    ->relationship('business', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Product $record) {
                        SyncProductToGoogleMerchant::dispatch($record->id);

                        Notification::make()->title('Sincronización encolada')->success()->send();
                    }),
            ])
            ->bulkActions([
                // Cubre "sincronizar negocio": filtrar por negocio,
                // seleccionar todos sus productos y disparar esta acción.
                BulkAction::make('syncSelected')
                    ->label('Sincronizar seleccionados')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Collection $records) {
                        $records->each(fn (Product $product) => SyncProductToGoogleMerchant::dispatch($product->id));

                        Notification::make()->title("Se encolaron {$records->count()} productos")->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('retryFailed')
                    ->label('Reintentar fallidos')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        $count = 0;

                        Product::query()
                            ->where('type', 'producto')
                            ->where('google_merchant_status', 'error')
                            ->chunkById(100, function ($products) use (&$count): void {
                                foreach ($products as $product) {
                                    SyncProductToGoogleMerchant::dispatch($product->id);
                                    $count++;
                                }
                            });

                        Notification::make()->title("Se encolaron {$count} productos con error")->success()->send();
                    }),
                Action::make('syncAll')
                    ->label('Sincronización global')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Se reevalúa cada producto elegible; solo se reenvían a Google los que cambiaron desde el último envío.')
                    ->action(function () {
                        $count = 0;

                        Product::query()
                            ->where('type', 'producto')
                            ->whereHas('business', fn (Builder $query) => $query->where('google_merchant_enabled', true))
                            ->chunkById(100, function ($products) use (&$count): void {
                                foreach ($products as $product) {
                                    SyncProductToGoogleMerchant::dispatch($product->id);
                                    $count++;
                                }
                            });

                        Notification::make()->title("Se encolaron {$count} productos")->success()->send();
                    }),
            ])
            ->defaultSort('google_merchant_last_sync_at', 'desc');
    }
}
