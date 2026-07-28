<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Favorite;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'password', 'experience', 'terms_accepted_at', 'terms_version'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const PLATFORM_TEAM_ID = 0;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Solo roles de plataforma (moderator/admin/superadmin) entran al panel
     * interno de Filament (0.2.1 del TODO: Filament es solo administración,
     * separado del panel del emprendedor).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']);
    }

    /**
     * Verifica roles de plataforma (sin team, spatie/permission) fijando
     * explícitamente el contexto de equipo antes y restaurándolo después.
     *
     * Reutilizable desde cualquier punto (Filament Resources, políticas,
     * `canAccessPanel()`...): cada llamada de Filament vía Livewire puede
     * llegar como una petición AJAX separada de la carga inicial de la
     * página, donde `setPermissionsTeamId()` ya no tiene el valor fijado
     * por un middleware — el mismo problema corregido para los paneles de
     * negocio (ver el commit del fix del 403 en el editor de vitrina).
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyPlatformRole(array $roles): bool
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(self::PLATFORM_TEAM_ID);
        $this->unsetRelation('roles');

        $result = $this->hasAnyRole($roles);

        setPermissionsTeamId($previousTeamId);
        $this->unsetRelation('roles');

        return $result;
    }

    /**
     * Nombre del rol de plataforma actual (moderator/admin/superadmin), o
     * null si no tiene ninguno. Usado por el panel Filament (1.9 del TODO).
     */
    public function platformRoleName(): ?string
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(self::PLATFORM_TEAM_ID);
        $this->unsetRelation('roles');

        $role = $this->getRoleNames()->first();

        setPermissionsTeamId($previousTeamId);
        $this->unsetRelation('roles');

        return $role;
    }

    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill(['phone_verified_at' => $this->freshTimestamp()])->save();
    }

    /**
     * Negocios a los que pertenece a través de una membresía activa.
     *
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_memberships')
            ->withPivot(['status'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
