<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Favorite;
use App\Domain\Discovery\Models\RecentlyViewedBusiness;
use App\Domain\Identity\Models\SocialAccount;
use App\Domain\Identity\Models\UserDevice;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyIdentityToken;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Messaging\Models\BusinessMessage;
use App\Domain\Needs\Models\Need;
use App\Domain\Platform\Actions\StartUserImpersonation;
use App\Domain\Social\Models\Follow;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Domain\Trust\Models\BusinessVerification;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property string|null $avatar_path
 * @property string|null $avatar_preset
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property array<string, mixed>|null $notification_channel_preferences
 */
#[Fillable(['name', 'email', 'phone', 'password', 'experience', 'terms_accepted_at', 'terms_version', 'remember_recently_viewed', 'notification_channel_preferences'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const PLATFORM_TEAM_ID = 0;

    /** @return HasMany<SocialAccount, $this> */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

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
            'remember_recently_viewed' => 'boolean',
            'notification_channel_preferences' => 'array',
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
     * Bug real reportado por el usuario: al entrar "como" otro usuario
     * desde el admin (`StartUserImpersonation`), `Auth::user()` pasa a
     * ser ESE usuario, no el superadmin original — así que
     * `hasAnyPlatformRole(['superadmin'])` por sí solo ya no detecta
     * que quien está detrás sigue siendo un superadmin probando/
     * ayudando desde esa cuenta. Solo un superadmin puede iniciar una
     * impersonación (`StartUserImpersonation::handle()`), así que la
     * sola presencia de esa sesión activa ya es prueba suficiente.
     * Pensado para no bloquear funciones de plan (IA de vitrina/
     * productos, chatbot, plantillas de stand) mientras se impersona.
     */
    public function canBypassPlanGates(): bool
    {
        return $this->hasAnyPlatformRole(['superadmin'])
            || session()->has(StartUserImpersonation::SESSION_KEY);
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

    public function syncPlatformRole(?string $role): void
    {
        $role = in_array($role, ['moderator', 'admin', 'superadmin'], true) ? $role : null;
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(self::PLATFORM_TEAM_ID);
        $this->unsetRelation('roles');
        $this->syncRoles($role ? [Role::findOrCreate($role, 'web')] : []);

        setPermissionsTeamId($previousTeamId);
        $this->unsetRelation('roles');
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
     * @return HasMany<BusinessConversation, $this>
     */
    public function customerConversations(): HasMany
    {
        return $this->hasMany(BusinessConversation::class, 'customer_user_id');
    }

    /**
     * @return HasMany<BusinessMessage, $this>
     */
    public function sentBusinessMessages(): HasMany
    {
        return $this->hasMany(BusinessMessage::class, 'sender_user_id');
    }

    public function unreadBusinessMessagesCount(): int
    {
        if (! Schema::hasTable('business_messages') || ! Schema::hasTable('business_conversations')) {
            return 0;
        }

        return BusinessMessage::query()
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', $this->id)
            ->whereIn(
                'business_conversation_id',
                BusinessConversation::query()->accessibleTo($this)->select('id'),
            )
            ->count();
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Negocios que sigue (2.3 del TODO social, Sprint 2).
     *
     * @return BelongsToMany<Business, $this>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'follows')->withTimestamps();
    }

    public function isFollowing(Business $business): bool
    {
        return Follow::query()
            ->where('user_id', $this->id)
            ->where('business_id', $business->id)
            ->exists();
    }

    /**
     * "Mis compras" — pedidos hechos como comprador.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_user_id')->latest();
    }

    /**
     * "Mis suscripciones" — suscripciones a productos de negocios (Fase
     * 8.2 del TODO social).
     *
     * @return HasMany<CustomerSubscription, $this>
     */
    public function customerSubscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class, 'buyer_user_id')->latest();
    }

    /**
     * Historial básico de negocios vistos (1.1.1 del TODO), solo con
     * consentimiento (`remember_recently_viewed`).
     *
     * @return HasMany<RecentlyViewedBusiness, $this>
     */
    public function recentlyViewedBusinesses(): HasMany
    {
        return $this->hasMany(RecentlyViewedBusiness::class)->latest('viewed_at');
    }

    /**
     * Necesidades publicadas por este comprador ("Pídelo en Merkamigo",
     * Fase 2 del TODO).
     *
     * @return HasMany<Need, $this>
     */
    public function needs(): HasMany
    {
        return $this->hasMany(Need::class)->latest();
    }

    /**
     * @return HasMany<BusinessVerification, $this>
     */
    public function requestedBusinessVerifications(): HasMany
    {
        return $this->hasMany(BusinessVerification::class, 'requested_by');
    }

    /**
     * @return HasMany<OrderConfirmation, $this>
     */
    public function customerOrderConfirmations(): HasMany
    {
        return $this->hasMany(OrderConfirmation::class, 'customer_user_id');
    }

    /**
     * @return HasMany<OrderConfirmation, $this>
     */
    public function businessOrderConfirmations(): HasMany
    {
        return $this->hasMany(OrderConfirmation::class, 'business_user_id');
    }

    /**
     * @return HasMany<Recommendation, $this>
     */
    public function recommendationsAuthored(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'author_user_id');
    }

    /**
     * Dispositivos registrados para notificaciones push (5.2 del TODO).
     *
     * @return HasMany<UserDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Cuentas de Merkapuntos del cliente, una por negocio adherido
     * (TODO_Merkapuntos.md, reglas de producto: los puntos pertenecen al
     * ámbito del negocio emisor, no a un saldo universal).
     *
     * @return HasMany<LoyaltyAccount, $this>
     */
    public function loyaltyAccounts(): HasMany
    {
        return $this->hasMany(LoyaltyAccount::class);
    }

    /**
     * @return HasOne<LoyaltyIdentityToken, $this>
     */
    public function loyaltyIdentityToken(): HasOne
    {
        return $this->hasOne(LoyaltyIdentityToken::class);
    }

    /**
     * Un usuario desactivó las notificaciones push de un tipo específico
     * (5.2 del TODO) — `notification_channel_preferences.push_disabled` es
     * una lista de nombres de clase de notificación (`::class`).
     */
    public function hasDisabledPushFor(string $notificationClass): bool
    {
        $disabled = $this->notification_channel_preferences['push_disabled'] ?? [];

        return in_array($notificationClass, $disabled, true);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
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
