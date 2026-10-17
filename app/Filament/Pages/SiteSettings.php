<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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

                TextInput::make('meta_pixel_id')
                    ->label('Meta Pixel ID')
                    ->helperText('ID del Pixel de Meta (Facebook) para seguimiento de conversiones. Déjalo vacío para desactivarlo.')
                    ->maxLength(32)
                    ->regex('/^\d+$/')
                    ->validationMessages([
                        'regex' => 'El Meta Pixel ID debe contener solo números.',
                    ]),

                TextInput::make('social_facebook_url')
                    ->label('Facebook')
                    ->helperText('URL del perfil real de Merkamigo. Se usa en el pie de página y como dato de identidad de marca para buscadores e IA.')
                    ->url()
                    ->placeholder('https://www.facebook.com/merkamigo'),

                TextInput::make('social_instagram_url')
                    ->label('Instagram')
                    ->helperText('URL del perfil real de Merkamigo.')
                    ->url()
                    ->placeholder('https://www.instagram.com/merkamigo'),

                TextInput::make('social_tiktok_url')
                    ->label('TikTok')
                    ->helperText('Opcional.')
                    ->url()
                    ->placeholder('https://www.tiktok.com/@merkamigo'),

                TextInput::make('social_whatsapp_url')
                    ->label('WhatsApp Business')
                    ->helperText('Opcional, solo si existe un canal de WhatsApp propio de Merkamigo (no el de cada negocio).')
                    ->url()
                    ->placeholder('https://wa.me/57...'),

                FileUpload::make('create_vitrina_video_path')
                    ->label('Video de la tarjeta "Crea tu vitrina"')
                    ->helperText('Video de fondo de la tarjeta de Inicio que invita a crear la vitrina. Sin video, se muestra la imagen por defecto.')
                    ->disk('public')
                    ->directory('site/videos')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                    ->maxSize(config('media.site_create_vitrina_video.max_kb')),

                FileUpload::make('pidelo_video_path')
                    ->label('Video de la tarjeta "¿No encuentras lo que necesitas?"')
                    ->helperText('Video de fondo de la tarjeta de Inicio que invita a publicar una solicitud (Pídelo). Sin video, se muestra el fondo plano por defecto.')
                    ->disk('public')
                    ->directory('site/videos')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                    ->maxSize(config('media.site_pidelo_video.max_kb')),

                Section::make('Banner de Vitrinas (buscador principal)')
                    ->description('Se ve en Inicio y en Plaza cuando no hay un municipio específico seleccionado. Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('main_search_background_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_main_search_background.max_kb')),
                        TextInput::make('vitrinas_hero_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Descubre lo mejor de tu municipio. Compra local, apoya tu comunidad'),
                        Textarea::make('vitrinas_hero_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Miles de negocios, productos y servicios cerca de ti.'),
                    ]),

                Section::make('Banner de Eventos')
                    ->description('Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('eventos_banner_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_eventos_banner.max_kb')),
                        TextInput::make('eventos_banner_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Eventos cerca de ti'),
                        Textarea::make('eventos_banner_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Descubre experiencias de negocios locales: música en vivo, talleres, mercados y más.'),
                    ]),

                Section::make('Banner de Merkapuntos (clientes con sesión iniciada)')
                    ->description('Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('merkapuntos_banner_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_merkapuntos_banner.max_kb')),
                        TextInput::make('merkapuntos_banner_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Tu próxima compra puede acercarte a una recompensa.'),
                        Textarea::make('merkapuntos_banner_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Elige lo que te gustaría disfrutar y empieza a acumular comprando cerca de ti.'),
                    ]),

                Section::make('Banner de Merkamigo Premia (catálogo público, sin iniciar sesión)')
                    ->description('Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('premia_banner_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_premia_banner.max_kb')),
                        TextInput::make('premia_banner_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Apoyar lo local tiene recompensa'),
                        Textarea::make('premia_banner_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Descubre negocios adheridos a Merkamigo Premia, acumula Merkapuntos con tus compras verificadas y canjéalos por experiencias, productos y servicios únicos de tu comunidad.'),
                    ]),

                Section::make('Banner de Pídelo')
                    ->description('Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('pidelo_banner_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_pidelo_banner.max_kb')),
                        TextInput::make('pidelo_banner_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Cuéntanos qué necesitas'),
                        Textarea::make('pidelo_banner_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Compártelo una vez y recibe propuestas de negocios cercanos por acá mismo.'),
                    ]),

                Section::make('Banner de "Crear vitrina" (bienvenida a emprendedores)')
                    ->description('Deja un campo vacío para conservar el texto o la imagen original de esa sección.')
                    ->components([
                        FileUpload::make('bienvenida_banner_path')
                            ->label('Imagen de fondo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'])
                            ->disk('public')
                            ->directory('site')
                            ->maxSize(config('media.site_bienvenida_banner.max_kb')),
                        TextInput::make('bienvenida_banner_title')
                            ->label('Título')
                            ->maxLength(150)
                            ->placeholder('Tu negocio local, más cerca de todos'),
                        Textarea::make('bienvenida_banner_description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Crea tu vitrina digital en minutos, muestra lo que vendes y conecta con personas de tu comunidad.'),
                    ]),
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
