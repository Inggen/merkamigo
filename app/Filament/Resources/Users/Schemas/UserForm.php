<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * En edición, los datos de acceso permanecen de solo lectura y únicamente
 * se gestiona el rol de plataforma. En creación, el administrador sí debe
 * poder diligenciar los datos mínimos de una cuenta nueva.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->maxLength(255)
                    ->requiredWithout('phone')
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtolower(trim($state)) : null)
                    ->disabledOn('edit'),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->requiredWithout('email')
                    ->rules(['regex:/^\+?[0-9]{7,15}$/'])
                    ->unique(User::class, 'phone', ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                    ->disabledOn('edit'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rules([Password::default()])
                    ->confirmed()
                    ->visibleOn('create'),
                TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false)
                    ->visibleOn('create'),
                Select::make('platform_role')
                    ->label('Rol de plataforma')
                    ->options([
                        '' => 'Ninguno',
                        'moderator' => 'Moderador',
                        'admin' => 'Administrador',
                        'superadmin' => 'Superadministrador',
                    ])
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Moderador o Administrador según el rol de plataforma en spatie/laravel-permission (team 0).'),
            ]);
    }
}
