<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ImmersiveObjectTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /**
     * Componentes crudos, reutilizados por `configure()` y por el
     * `createOptionForm()` del Select de plantilla en
     * `PlazaLegendEntryForm` (IMM-013: crear la plantilla que falta sin
     * salir del flujo de mapear la leyenda).
     *
     * @return array<int, Component>
     */
    public static function components(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', filled($state) ? Str::slug($state) : null)),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true),
            Select::make('builder_key')
                ->label('Forma voxel (builder)')
                ->options([
                    'standBooth' => 'Stand — caseta de madera',
                    'standTable' => 'Stand — mesa exhibidora',
                    'marketStall' => 'Stand — toldo de mercado',
                    'colonialHouse' => 'Construcción / Edificio',
                    'tree' => 'Árbol',
                    'palm' => 'Palma',
                    'lamp' => 'Farol',
                    'bench' => 'Banca',
                    'planter' => 'Jardinera',
                    'fountain' => 'Fuente',
                    'statue' => 'Estatua/monumento',
                    'vehicle' => 'Vehículo',
                    'hedgeRect' => 'Seto',
                    'cloud' => 'Nube',
                ])
                ->helperText('Qué forma de "standardBuilders" (voxel-plaza-engine.js) renderiza este objeto. Vacío = sin modelo 3D todavía (reservable pero invisible en la escena).'),
            Select::make('category')
                ->label('Categoría')
                ->options([
                    'stand' => 'Stand',
                    'construccion' => 'Construcción',
                    'arbol' => 'Árbol',
                    'fuente' => 'Fuente',
                    'monumento' => 'Monumento',
                    'personaje' => 'Personaje',
                ])
                ->default('stand')
                ->required()
                ->helperText('"Stand" se reserva vía el editor de zonas/slots (flujo comercial); el resto se coloca directo como elemento de la plaza.'),
            Select::make('asset_input_mode')
                ->label('Tipo de recurso')
                ->options([
                    'model_3d' => 'Modelo 3D',
                    'ia_voxel' => 'Objeto Voxel',
                ])
                ->default('model_3d')
                ->live()
                ->afterStateUpdated(function (?string $state, $livewire): void {
                    if ($state !== 'ia_voxel' || ! is_object($livewire) || ! method_exists($livewire, 'mountAction')) {
                        return;
                    }

                    $livewire->mountAction('generarIa');
                })
                ->required(),
            FileUpload::make('model_path')
                ->label('Modelo 3D (.glb)')
                ->disk('public')
                ->directory('immersive-object-templates/models')
                ->acceptedFileTypes(['.glb', 'model/gltf-binary'])
                ->visible(fn (Get $get): bool => $get('asset_input_mode') === 'model_3d')
                ->helperText('Si hay un GLB cargado, se usa en la escena por encima de cualquier otra cosa.'),
            TextInput::make('max_width')
                ->label('Ancho máximo (m)')
                ->numeric()
                ->step('0.001')
                ->minValue(0.001)
                ->hidden(),
            TextInput::make('max_depth')
                ->label('Profundidad máxima (m)')
                ->numeric()
                ->step('0.001')
                ->minValue(0.001)
                ->hidden(),
            TextInput::make('max_height')
                ->label('Alto máximo (m)')
                ->numeric()
                ->step('0.001')
                ->minValue(0.001)
                ->hidden(),
            TextInput::make('max_boxes')
                ->label('Cantidad máxima de bloques')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->default(40)
                ->hidden()
                ->helperText('Límite de cajas voxel que la IA puede usar al generar/refinar este objeto.'),
            TextInput::make('front_axis_rotation')
                ->label('Eje frontal (radianes sobre Y)')
                ->numeric()
                ->default(0)
                ->helperText('Hacia dónde mira el modelo "de fábrica", para que el motor no lo deje al revés al rotar.')
                ->required(),
            FileUpload::make('thumbnail_path')
                ->label('Miniatura')
                ->image()
                ->disk('public')
                ->directory('immersive-object-templates'),
            Select::make('status')
                ->label('Estado')
                ->options(['borrador' => 'Borrador', 'publicada' => 'Publicada', 'archivada' => 'Archivada'])
                ->default('borrador')
                ->required(),
        ];
    }
}
