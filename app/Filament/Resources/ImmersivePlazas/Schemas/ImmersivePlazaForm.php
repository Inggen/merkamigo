<?php

namespace App\Filament\Resources\ImmersivePlazas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ImmersivePlazaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('immersive_experience_id')
                    ->label('Experiencia')
                    ->relationship('experience', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->helperText('Define el orden de "Plaza 1 de N" que ve el visitante.'),
                TextInput::make('capacity')
                    ->label('Capacidad (slots)')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('category_rule')
                    ->label('Regla de categorías')
                    ->helperText('Ej. "gastronomia" si esta plaza es temática. Vacío = sin restricción.'),
                Select::make('status')
                    ->label('Estado')
                    ->options(['borrador' => 'Borrador', 'activa' => 'Activa', 'pausada' => 'Pausada', 'archivada' => 'Archivada'])
                    ->default('borrador')
                    ->required(),
                FileUpload::make('reference_image_path')
                    ->label('Imagen de referencia')
                    ->image()
                    ->disk('public')
                    ->directory('immersive-plazas')
                    ->helperText('Planta o vista aérea que el administrador usa como guía visual para ubicar reservas de stand (IMM-013). No se procesa automáticamente.'),
                FileUpload::make('legend_image_path')
                    ->label('Imagen de leyenda')
                    ->image()
                    ->disk('public')
                    ->directory('immersive-plazas')
                    ->helperText('El cuadro de convenciones (color → tipo de objeto) por separado del plano. La acción "Detectar leyenda" del encabezado lee los colores de esta imagen.'),
                Repeater::make('excluded_zones')
                    ->label('Zonas excluidas')
                    ->helperText('Rutas, monumentos, accesos o el punto de aparición: ningún slot puede invadir estos polígonos.')
                    // Oculto a pedido del usuario (2026-08-12): la validación
                    // real (StandZone/StandSlot::booted()) y el dibujo en
                    // rojo del editor espacial siguen funcionando igual con
                    // cualquier `excluded_zones` que ya exista en BD — esto
                    // solo quita el formulario manual de puntos X/Z de la
                    // vista, no la funcionalidad ni la columna.
                    ->hidden()
                    ->schema([
                        Repeater::make('points')
                            ->label('Puntos del polígono')
                            ->schema([
                                TextInput::make('x')->label('X')->numeric()->required(),
                                TextInput::make('z')->label('Z')->numeric()->required(),
                            ])
                            ->columns(2)
                            ->minItems(3),
                    ])
                    ->collapsible()
                    ->itemLabel(fn (array $state): string => 'Zona excluida ('.count($state['points'] ?? []).' puntos)'),
                Select::make('mobile_quality_profile')
                    ->label('Calidad móvil')
                    ->options(['ligero' => 'Ligero', 'equilibrado' => 'Equilibrado', 'alto' => 'Alto'])
                    ->default('ligero')
                    ->required(),
                Select::make('desktop_quality_profile')
                    ->label('Calidad escritorio')
                    ->options(['ligero' => 'Ligero', 'equilibrado' => 'Equilibrado', 'alto' => 'Alto'])
                    ->default('alto')
                    ->required(),
            ]);
    }
}
