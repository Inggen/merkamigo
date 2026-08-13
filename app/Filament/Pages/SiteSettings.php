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
