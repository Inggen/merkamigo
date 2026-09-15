<?php

namespace App\Filament\Resources\BusinessAttributes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BusinessAttributeForm
{
    /**
     * Catálogo cerrado de íconos, igual al patrón ya usado en
     * `CategoryForm::ICON_OPTIONS` — evita el bug real que tenía el campo
     * anterior (`TextInput` libre + `View::exists('flux::icon.'.$value)`):
     * Flux registra sus íconos como componentes anónimos
     * (`Blade::anonymousComponentPath`), no como vistas con nombre, así que
     * `View::exists()` siempre devolvía `false` y ningún ícono pasaba la
     * validación — de ahí que los 6 atributos ya sembrados tuvieran
     * `icon` vacío pese a que el campo llevaba tiempo existiendo.
     */
    public const ICON_OPTIONS = [
        'credit-card' => 'Tarjeta de crédito (pagos digitales)',
        'banknotes' => 'Billetes (efectivo)',
        'wallet' => 'Billetera',
        'truck' => 'Camión (domicilios)',
        'map-pin' => 'Ubicación (hecho en la región)',
        'clock' => 'Reloj (horarios/rapidez)',
        'shield-check' => 'Escudo (confianza/garantía)',
        'hand-raised' => 'Mano (atención cercana)',
        'users' => 'Personas (atención al cliente)',
        'sparkles' => 'Destellos (frescura/calidad)',
        'sun' => 'Sol (productos frescos/naturales)',
        'gift' => 'Regalo (artesanal)',
        'heart' => 'Corazón (hecho con cuidado)',
        'star' => 'Estrella (calidad)',
        'phone' => 'Teléfono (contacto directo)',
        'chat-bubble-left-right' => 'Chat (atención por mensajes)',
        'check-badge' => 'Insignia de verificación',
        'building-storefront' => 'Local comercial',
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
                    ->label('Ícono (opcional)')
                    ->options(self::ICON_OPTIONS)
                    ->searchable()
                    ->helperText('Se muestra sobre el nombre en la tarjeta de la vitrina pública. Si lo dejas vacío, la tarjeta se muestra solo con el nombre.'),
                TextInput::make('description')
                    ->label('Descripción corta (opcional)')
                    ->placeholder('Ej. Paga fácil y seguro con tus medios digitales favoritos.')
                    ->helperText('Una o dos frases que se muestran debajo del nombre en la vitrina pública.')
                    ->maxLength(160),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
