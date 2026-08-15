<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Configuración general del sitio editable desde el admin: empieza con la
 * imagen genérica para compartir en redes sociales, y es el lugar donde
 * irán más ajustes de plataforma a futuro. Exclusivo admin/superadmin.
 *
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Configuración del sitio';

    protected static ?string $title = 'Configuración del sitio';

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
        $this->form->fill(SiteSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('default_share_image_path')
                    ->label('Imagen genérica para compartir')
                    ->helperText('Se usa al compartir en Facebook, WhatsApp u otras redes cualquier página que todavía no tenga su propia imagen.')
                    ->image()
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_default_share_image.max_kb'))
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth((string) config('media.site_default_share_image.max_width'))
                    ->imageResizeTargetHeight((string) config('media.site_default_share_image.max_width')),

                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->helperText('Logo principal del sitio.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_logo.max_kb')),

                FileUpload::make('logo_mono_path')
                    ->label('Logo monocromático')
                    ->helperText('Versión de un solo color del logo, usada sobre fondos oscuros o de color.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_logo_mono.max_kb')),

                FileUpload::make('apple_touch_icon_path')
                    ->label('Apple touch icon')
                    ->helperText('Ícono usado al agregar el sitio a la pantalla de inicio en dispositivos Apple.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_apple_touch_icon.max_kb')),

                FileUpload::make('login_background_path')
                    ->label('Fondo del login de administración')
                    ->helperText('Imagen de fondo de la pantalla de acceso al panel de administración.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_login_background.max_kb')),

                FileUpload::make('footer_background_path')
                    ->label('Fondo del pie de página')
                    ->helperText('Imagen de fondo del pie de página del sitio.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_footer_background.max_kb')),

                FileUpload::make('main_search_background_path')
                    ->label('Fondo del buscador principal')
                    ->helperText('Imagen de fondo detrás del buscador principal del sitio.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                    ->disk('public')
                    ->directory('site')
                    ->maxSize(config('media.site_main_search_background.max_kb')),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::current()->fill($data)->save();

        Notification::make()
            ->title('Configuración del sitio guardada')
            ->success()
            ->send();
    }
}
