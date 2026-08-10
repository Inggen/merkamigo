<?php

namespace App\Filament\Resources\ImmersiveExperiences\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ImmersiveExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('municipality_id')
                    ->label('Municipio')
                    ->relationship('municipality', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $set('slug', filled($state) ? Str::slug($state) : null);
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('route_name')
                    ->label('Escena inmersiva')
                    ->options(config('immersive.available_scenes'))
                    ->helperText('Zipaquirá y Cajicá son escenas con geometría fija ya construida a mano. "Escena genérica" arma el mundo caminable en vivo a partir de esta plaza (zonas, elementos y stands que crees en la sección Plazas) — no requiere código, pero primero debes crear una plaza con sus límites, punto de aparición y elementos.'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'validando' => 'Validando',
                        'publicada' => 'Publicada',
                        'pausada' => 'Pausada',
                        'archivada' => 'Archivada',
                    ])
                    ->disableOptionWhen(fn (string $value): bool => $value === 'publicada')
                    ->default('borrador')
                    ->required()
                    ->helperText('Para publicar usa el botón "Publicar versión" del encabezado — así queda un historial en experience_versions (IMM-014).'),
                FileUpload::make('thumbnail_path')
                    ->label('Miniatura')
                    ->image()
                    ->disk('public')
                    ->directory('immersive-experiences')
                    ->maxSize(config('media.immersive_experience_thumbnail.max_kb')),
            ]);
    }
}
