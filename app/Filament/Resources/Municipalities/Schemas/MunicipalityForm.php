<?php

namespace App\Filament\Resources\Municipalities\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MunicipalityForm
{
    /**
     * Catálogo base para el admin: evita errores de digitación y permite
     * que nombre/departamento dependan entre sí sin sobrearquitectura.
     *
     * @return array<string, array<int, string>>
     */
    protected static function municipalityCatalog(): array
    {
        return [
            'Bogotá, D.C.' => ['Bogotá'],
            'Cundinamarca' => [
                'Cajicá',
                'Chía',
                'Cogua',
                'Cota',
                'Gachancipá',
                'Nemocón',
                'Sopó',
                'Tabio',
                'Tenjo',
                'Tocancipá',
                'Zipaquirá',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function departmentOptions(): array
    {
        return collect(self::municipalityCatalog())
            ->keys()
            ->mapWithKeys(fn (string $department) => [$department => $department])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function municipalityOptions(?string $department): array
    {
        if (blank($department)) {
            return [];
        }

        return collect(self::municipalityCatalog()[$department] ?? [])
            ->mapWithKeys(fn (string $municipality) => [$municipality => $municipality])
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department')
                    ->label('Departamento')
                    ->options(self::departmentOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                        $name = $get('name');

                        if (blank($name)) {
                            return;
                        }

                        $availableMunicipalities = self::municipalityOptions($state);

                        if (! array_key_exists($name, $availableMunicipalities)) {
                            $set('name', null);
                            $set('slug', null);
                        }
                    }),
                Select::make('name')
                    ->label('Nombre')
                    ->options(fn (callable $get): array => self::municipalityOptions($get('department')))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        if (blank($state)) {
                            $set('slug', null);

                            return;
                        }

                        foreach (self::municipalityCatalog() as $department => $municipalities) {
                            if (in_array($state, $municipalities, true)) {
                                $set('department', $department);
                                break;
                            }
                        }

                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                FileUpload::make('cover_path')
                    ->label('Portada')
                    ->image()
                    ->disk('public')
                    ->directory('municipalities')
                    ->maxSize(config('media.municipality_cover.max_kb'))
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth((string) config('media.municipality_cover.max_width'))
                    ->imageResizeTargetHeight((string) config('media.municipality_cover.max_width')),
                FileUpload::make('hero_video_path')
                    ->label('Video del banner')
                    ->disk('public')
                    ->directory('municipalities/videos')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                    ->maxSize(config('media.municipality_hero_video.max_kb'))
                    ->helperText('Se usará como fondo del buscador y del acceso a la experiencia inmersiva cuando el municipio tenga video.'),
                TextInput::make('cover_alt_text')
                    ->label('Texto alternativo de la portada')
                    ->helperText('Describe la imagen para lectores de pantalla y buscadores.')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
            ]);
    }
}
