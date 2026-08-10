<?php

namespace App\Filament\Resources\ImmersivePlazaProps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class ImmersivePlazaPropForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('immersive_plaza_id')
                    ->label('Plaza')
                    ->relationship('plaza', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('object_template_id')
                    ->label('Objeto del catálogo')
                    ->relationship('template', 'name', fn ($query) => $query->where('category', '!=', 'stand'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Fieldset::make('Posición de mundo')
                    ->columns(3)
                    ->schema([
                        TextInput::make('world_position.x')->label('X')->numeric()->default(0)->required(),
                        TextInput::make('world_position.y')->label('Y')->numeric()->default(0)->required(),
                        TextInput::make('world_position.z')->label('Z')->numeric()->default(0)->required(),
                    ]),
                Fieldset::make('Rotación')
                    ->columns(3)
                    ->schema([
                        TextInput::make('rotation.x')->label('X')->numeric()->default(0),
                        TextInput::make('rotation.y')->label('Y')->numeric()->default(0),
                        TextInput::make('rotation.z')->label('Z')->numeric()->default(0),
                    ]),
                TextInput::make('scale')
                    ->label('Escala')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Toggle::make('collision_enabled')
                    ->label('Validar colisiones')
                    ->helperText('Solo aplica a objetos con modelo 3D (.glb).')
                    ->default(false),
                Select::make('status')
                    ->label('Estado')
                    ->options(['borrador' => 'Borrador', 'confirmado' => 'Confirmado'])
                    ->default('borrador')
                    ->required(),
            ]);
    }
}
