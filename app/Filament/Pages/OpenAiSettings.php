<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Models\OpenAiSetting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Configuración editable de OpenAI desde admin. Deja la API centralizada y
 * reutilizable para copilotos y asistentes futuros sin acoplar el front a
 * credenciales ni a un SDK específico.
 *
 * @property-read Schema $form
 */
class OpenAiSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Configuración de OpenAI';

    protected static ?string $title = 'Configuración de OpenAI';

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
        $setting = OpenAiSetting::current();

        $this->form->fill([
            'enabled' => $setting->enabled,
            'entrepreneur_copilot_enabled' => $setting->entrepreneur_copilot_enabled,
            'model' => $setting->model,
            'image_model' => $setting->image_model,
            'api_key' => $setting->getRawOriginal('api_key'),
            'base_url' => $setting->base_url ?: $setting->baseUrl(),
            'timeout_seconds' => $setting->timeout_seconds ?: $setting->timeoutSeconds(),
            'max_output_tokens' => $setting->max_output_tokens,
            'temperature' => $setting->temperature,
            'system_prompt' => $setting->system_prompt,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Estado')
                    ->description('Activa o apaga la integración sin tocar .env ni desplegar de nuevo.')
                    ->columns(2)
                    ->components([
                        Toggle::make('enabled')
                            ->label('Activar OpenAI')
                            ->helperText('Si está apagado, la app sigue usando sus flujos sin IA.'),
                        Toggle::make('entrepreneur_copilot_enabled')
                            ->label('Reservar para copiloto emprendedor')
                            ->helperText('Deja habilitada la bandera para conectar una experiencia conversacional futura.'),
                    ]),

                Section::make('Credenciales y modelo')
                    ->description('La llave API se guarda en base de datos; si dejas el campo vacío al editar, se conserva la actual.')
                    ->columns(2)
                    ->components([
                        TextInput::make('model')
                            ->label('Modelo (texto)')
                            ->placeholder('Ej. el modelo OpenAI que vayas a usar')
                            ->helperText('No se fija un modelo por defecto para no acoplar el código a una versión concreta.')
                            ->maxLength(120),
                        TextInput::make('image_model')
                            ->label('Modelo (imágenes)')
                            ->placeholder('gpt-image-2')
                            ->helperText('Modelo para generación de imágenes, separado del modelo de texto de arriba. Todavía no hay ninguna función que lo use.')
                            ->maxLength(120),
                        TextInput::make('api_key')
                            ->label('Clave API')
                            ->password()
                            ->revealable()
                            ->placeholder('sk-...')
                            ->helperText('Se muestra la llave actual. Si vacías el campo y guardas, se conserva; si lo cambias, se reemplaza. Debe ser una clave secreta de OpenAI y normalmente empieza por sk-.'),
                        TextInput::make('base_url')
                            ->label('URL base')
                            ->url()
                            ->placeholder('https://api.openai.com/v1'),
                        TextInput::make('timeout_seconds')
                            ->label('Tiempo de espera (segundos)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300)
                            ->default(30),
                        TextInput::make('max_output_tokens')
                            ->label('Máximo de tokens de salida')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(16000),
                        TextInput::make('temperature')
                            ->label('Temperatura')
                            ->numeric()
                            ->step('0.1')
                            ->minValue(0)
                            ->maxValue(2),
                    ]),

                Section::make('Prompt del sistema')
                    ->description('Instrucción global reutilizable para copilotos futuros. Nunca debe pedir inventar datos.')
                    ->components([
                        Textarea::make('system_prompt')
                            ->label('Prompt base')
                            ->rows(6)
                            ->placeholder('Ej. Ayuda a redactar textos claros usando solo los datos reales del negocio. No inventes precios, horarios ni condiciones.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $setting = OpenAiSetting::current();
        $data = $this->form->getState();
        $data['api_key'] = is_string($data['api_key'] ?? null)
            ? trim($data['api_key'])
            : $data['api_key'];

        if (blank($data['api_key'] ?? null)) {
            $data['api_key'] = $setting->getRawOriginal('api_key');
        }

        if (filled($data['api_key'] ?? null) && ! str_starts_with((string) $data['api_key'], 'sk-')) {
            Notification::make()
                ->title('La clave API no parece ser de OpenAI')
                ->body('La llave guardada debe ser una clave secreta de OpenAI. Las claves de OpenAI normalmente empiezan por "sk-".')
                ->danger()
                ->send();

            return;
        }

        $setting->fill($data)->save();

        Notification::make()
            ->title('Configuración de OpenAI guardada')
            ->success()
            ->send();
    }
}
