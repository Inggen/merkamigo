<?php

namespace App\Filament\Resources\StandZones\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class StandZoneForm
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
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Repeater::make('polygon.points')
                    ->label('Polígono (puntos, en orden)')
                    ->schema([
                        TextInput::make('x')->label('X')->numeric()->required(),
                        TextInput::make('z')->label('Z')->numeric()->required(),
                    ])
                    ->columns(2)
                    ->minItems(3)
                    ->helperText('Coordenadas de mundo, mismo sistema que los límites navegables de la plaza. Deben caber dentro de ellos.'),
                Select::make('default_orientation')
                    ->label('Orientación por defecto')
                    ->options([
                        'TOWARD_CENTER' => 'Hacia el centro',
                        'AWAY_FROM_CENTER' => 'Hacia afuera',
                        'FOLLOW_PATH' => 'Según la ruta',
                        'MANUAL' => 'Manual por slot',
                    ])
                    ->default('TOWARD_CENTER')
                    ->required(),
                Fieldset::make('Centro de referencia')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference_center.x')->label('X')->numeric(),
                        TextInput::make('reference_center.z')->label('Z')->numeric(),
                    ]),
                TextInput::make('min_separation')
                    ->label('Separación mínima entre stands (m)')
                    ->numeric()
                    ->default(1.5)
                    ->required(),
                TextInput::make('priority')
                    ->label('Prioridad')
                    ->numeric()
                    ->default(1)
                    ->required(),
            ]);
    }
}
