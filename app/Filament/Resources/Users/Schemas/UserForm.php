<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * 1.9 del TODO: solo se gestiona el rol de plataforma desde aquí. Nombre,
 * correo y teléfono quedan de solo lectura — cambiarlos sin las reglas de
 * validación/unicidad de `ProfileValidationRules` podría romper el acceso
 * del usuario, y no es el propósito de este panel.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->disabled(),
                TextInput::make('email')->label('Correo')->disabled(),
                TextInput::make('phone')->label('Teléfono')->disabled(),
                Select::make('platform_role')
                    ->label('Rol de plataforma')
                    ->options([
                        '' => 'Ninguno',
                        'moderator' => 'Moderador',
                        'admin' => 'Administrador',
                        'superadmin' => 'Superadministrador',
                    ])
                    ->dehydrated(false)
                    ->helperText('Moderador o Administrador según el rol de plataforma en spatie/laravel-permission (team 0).'),
            ]);
    }
}
