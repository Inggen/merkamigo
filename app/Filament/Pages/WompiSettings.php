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

    /**
     * Claves privadas y secretos: `WompiSetting::$hidden` las excluye de
     * `attributesToArray()`, así que nunca se rellenan en el formulario
     * (se quedan en null aunque ya estén guardadas) — mismo patrón que
     * `OpenAiSettings::mount()` para no exponer secretos de vuelta al
     * admin. `save()` conserva el valor guardado si el campo llega vacío.
     *
     * @var array<int, string>
     */
    private const SECRET_FIELDS = [
        'sandbox_private_key',
        'sandbox_integrity_secret',
        'sandbox_events_secret',
        'production_private_key',
        'production_integrity_secret',
        'production_events_secret',
    ];

    public function mount(): void
    {
        $setting = WompiSetting::current();

        $this->form->fill([
            'active_env' => $setting->active_env,
            'sandbox_public_key' => $setting->sandbox_public_key,
            'sandbox_private_key' => null,
            'sandbox_integrity_secret' => null,
            'sandbox_events_secret' => null,
            'production_public_key' => $setting->production_public_key,
            'production_private_key' => null,
            'production_integrity_secret' => null,
            'production_events_secret' => null,
        ]);
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
                    ->description('Nunca mueven dinero real. Las llaves públicas de Wompi en sandbox empiezan con "pub_test_". Por seguridad, los secretos guardados no se muestran de vuelta: deja el campo vacío para conservar el actual, o llénalo para reemplazarlo.')
                    ->columns(2)
                    ->components([
                        TextInput::make('sandbox_public_key')->label('Llave pública'),
                        TextInput::make('sandbox_private_key')->label('Llave privada')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva la actual.'),
                        TextInput::make('sandbox_integrity_secret')->label('Secreto de integridad')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva el actual.'),
                        TextInput::make('sandbox_events_secret')->label('Secreto de eventos (webhook)')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva el actual.'),
                    ]),

                Section::make('Credenciales de producción')
                    ->description('Estas sí procesan cobros reales. Las llaves públicas de Wompi en producción empiezan con "pub_prod_". Por seguridad, los secretos guardados no se muestran de vuelta: deja el campo vacío para conservar el actual, o llénalo para reemplazarlo.')
                    ->columns(2)
                    ->components([
                        TextInput::make('production_public_key')->label('Llave pública'),
                        TextInput::make('production_private_key')->label('Llave privada')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva la actual.'),
                        TextInput::make('production_integrity_secret')->label('Secreto de integridad')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva el actual.'),
                        TextInput::make('production_events_secret')->label('Secreto de eventos (webhook)')->password()->revealable()->placeholder('Sin cambios')->helperText('Vacío = se conserva el actual.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $setting = WompiSetting::current();
        $data = $this->form->getState();

        foreach (self::SECRET_FIELDS as $field) {
            $value = is_string($data[$field] ?? null) ? trim($data[$field]) : $data[$field];
            $data[$field] = blank($value) ? $setting->getRawOriginal($field) : $value;
        }

        $setting->fill($data)->save();

        Notification::make()
            ->title('Configuración de Wompi guardada')
            ->success()
            ->send();
    }
}
