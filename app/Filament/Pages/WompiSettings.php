<?php

namespace App\Filament\Pages;

use App\Domain\Billing\Models\WompiSetting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Configuración de Wompi editable desde el admin (4.2 del TODO): permite
 * guardar credenciales de sandbox y de producción a la vez, y elegir cuál
 * está activa — sin tocar `.env` ni desplegar de nuevo. Exclusivo
 * admin/superadmin.
 *
 * @property-read Schema $form
 */
class WompiSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $navigationLabel = 'Configuración de Wompi';

    protected static ?string $title = 'Configuración de Wompi';

    protected string $view = 'filament.pages.settings-form';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(WompiSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('active_env')
                    ->label('Ambiente activo')
                    ->options([
                        'sandbox' => 'Pruebas (sandbox)',
                        'production' => 'Producción',
                    ])
                    ->helperText('Decide cuál de los dos juegos de credenciales de abajo usa Merkamigo ahora mismo.')
                    ->required(),

                Section::make('Credenciales de pruebas (sandbox)')
                    ->description('Nunca mueven dinero real. Las llaves públicas de Wompi en sandbox empiezan con "pub_test_".')
                    ->columns(2)
                    ->components([
                        TextInput::make('sandbox_public_key')->label('Llave pública'),
                        TextInput::make('sandbox_private_key')->label('Llave privada')->password()->revealable(),
                        TextInput::make('sandbox_integrity_secret')->label('Secreto de integridad')->password()->revealable(),
                        TextInput::make('sandbox_events_secret')->label('Secreto de eventos (webhook)')->password()->revealable(),
                    ]),

                Section::make('Credenciales de producción')
                    ->description('Estas sí procesan cobros reales. Las llaves públicas de Wompi en producción empiezan con "pub_prod_".')
                    ->columns(2)
                    ->components([
                        TextInput::make('production_public_key')->label('Llave pública'),
                        TextInput::make('production_private_key')->label('Llave privada')->password()->revealable(),
                        TextInput::make('production_integrity_secret')->label('Secreto de integridad')->password()->revealable(),
                        TextInput::make('production_events_secret')->label('Secreto de eventos (webhook)')->password()->revealable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        WompiSetting::current()->fill($data)->save();

        Notification::make()
            ->title('Configuración de Wompi guardada')
            ->success()
            ->send();
    }
}
