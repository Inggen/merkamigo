<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

#[Signature('app:make-superadmin {email} {--password=}')]
#[Description('Crea o promueve un usuario a rol de plataforma superadmin (acceso al panel /admin de Filament).')]
class MakeSuperadmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?: Str::password(16);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Superadmin', 'password' => $password],
        );

        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate('superadmin', 'web'));
        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');

        $this->info("Usuario {$user->email} ahora tiene el rol superadmin.");

        if (! $this->option('password')) {
            $this->warn("Contraseña generada: {$password}");
        }

        return self::SUCCESS;
    }
}
