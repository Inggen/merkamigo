<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    /**
     * Pedido del usuario: poder elegir el ícono de cada categoría desde el
     * admin, en vez de que quede fijo en el seeder. Nombres de icono
     * Heroicon (outline) de los que trae Flux en
     * vendor/livewire/flux/stubs/resources/views/flux/icon — mismo
     * vocabulario que ya usa `<x-category-icons>` para pintarlos.
     *
     * @var array<string, string>
     */
    public const ICON_OPTIONS = [
        'cake' => 'Pastel (alimentos y bebidas)',
        'shopping-bag' => 'Bolsa de compras',
        'shopping-cart' => 'Carrito de compras',
        'sparkles' => 'Destellos (belleza)',
        'scissors' => 'Tijeras (peluquería/belleza)',
        'heart' => 'Corazón (salud/bienestar)',
        'home' => 'Casa (hogar)',
        'home-modern' => 'Casa moderna',
        'wrench-screwdriver' => 'Herramientas (servicios para el hogar)',
        'briefcase' => 'Maletín (servicios profesionales)',
        'device-phone-mobile' => 'Celular (tecnología)',
        'cpu-chip' => 'Chip (electrónica)',
        'computer-desktop' => 'Computador',
        'hand-raised' => 'Mano (mascotas/cuidado)',
        'truck' => 'Camión (transporte)',
        'academic-cap' => 'Birrete (educación)',
        'ticket' => 'Boleta (eventos/entretenimiento)',
        'gift' => 'Regalo (artesanías/regalos)',
        'face-smile' => 'Cara feliz (niños/bebés)',
        'trophy' => 'Trofeo (deportes)',
        'sun' => 'Sol (campo/aire libre)',
        'building-office-2' => 'Edificio (inmuebles)',
        'building-office' => 'Oficina',
        'building-storefront' => 'Local comercial',
        'map' => 'Mapa (turismo)',
        'globe-alt' => 'Globo terráqueo',
        'musical-note' => 'Nota musical',
        'film' => 'Película',
        'paint-brush' => 'Pincel (arte)',
        'book-open' => 'Libro abierto',
        'beaker' => 'Matraz (belleza/salud)',
        'star' => 'Estrella',
        'tag' => 'Etiqueta (otros)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('icon')
                    ->label('Ícono')
                    ->options(self::ICON_OPTIONS)
                    ->searchable()
                    ->default('tag')
                    ->required()
                    ->helperText('Se usa en la fila de categorías del sitio público.'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
