<?php

namespace App\Filament\Resources\Municipalities\Schemas;

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
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
