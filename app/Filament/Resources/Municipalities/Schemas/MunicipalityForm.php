<?php

namespace App\Filament\Resources\Municipalities\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MunicipalityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('department')
                    ->required()
                    ->default('Cundinamarca'),
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
                    ->required(),
            ]);
    }
}
