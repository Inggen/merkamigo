<?php

namespace App\Filament\Resources\StandSlots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class StandSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('stand_zone_id')
                    ->label('Zona')
                    ->relationship('zone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('code')
                    ->label('Código')
                    ->required(),
                Select::make('stand_template_id')
                    ->label('Plantilla de stand esperada')
                    ->relationship('template', 'name', fn ($query) => $query->where('category', 'stand'))
                    ->searchable()
                    ->preload(),
                Select::make('allowed_category_id')
                    ->label('Categoría permitida (opcional)')
                    ->relationship('allowedCategory', 'name')
                    ->searchable()
                    ->preload(),
                Fieldset::make('Posición de mundo')
                    ->columns(3)
                    ->schema([
                        TextInput::make('world_position.x')->label('X')->numeric()->required(),
                        TextInput::make('world_position.y')->label('Y')->numeric()->default(0)->required(),
                        TextInput::make('world_position.z')->label('Z')->numeric()->required(),
                    ]),
                Fieldset::make('Rotación')
                    ->columns(3)
                    ->schema([
                        TextInput::make('rotation.x')->label('X')->numeric()->default(0),
                        TextInput::make('rotation.y')->label('Y')->numeric()->default(0),
                        TextInput::make('rotation.z')->label('Z')->numeric()->default(0),
                    ]),
                TextInput::make('max_width')
                    ->label('Ancho (m)')
                    ->numeric()
                    ->required(),
                TextInput::make('max_depth')
                    ->label('Profundidad (m)')
                    ->numeric()
                    ->required(),
                Select::make('orientation_mode')
                    ->label('Orientación (excepción manual)')
                    ->options([
                        'TOWARD_CENTER' => 'Hacia el centro',
                        'AWAY_FROM_CENTER' => 'Hacia afuera',
                        'FOLLOW_PATH' => 'Según la ruta',
                        'MANUAL' => 'Manual',
                    ])
                    ->helperText('Vacío = usa la regla por defecto de la zona.'),
                Toggle::make('accessible')
                    ->label('Accesible')
                    ->default(true)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'ocupada' => 'Ocupada',
                        'bloqueada' => 'Bloqueada',
                        'invalida' => 'Inválida',
                    ])
                    ->default('disponible')
                    ->required(),
            ]);
    }
}
