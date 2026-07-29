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
                    ->maxSize(config('media.municipality_cover.max_kb')),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
