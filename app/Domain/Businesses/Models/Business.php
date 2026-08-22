<?php

namespace App\Domain\Businesses\Models;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Discovery\Concerns\Favoritable;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\StandAssignment;
use App\Domain\Needs\Models\Offer;
use App\Domain\Storefronts\Models\BusinessChatbotProfile;
use App\Domain\Storefronts\Models\BusinessChatConversation;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\Storefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @property array<string, mixed>|null $hours
 * @property array<string, string>|null $social_links
 * @property array<string, mixed>|null $attributes
 * @property array<string, string>|null $whatsapp_faq_answers
 * @property Carbon|null $suspended_at
 * @property Carbon|null $featured_until
 * @property float|null $distance_km Distancia calculada en tiempo de ejecución (no persistida) cuando la Plaza ordena por cercanía, ver `PlazaController`.
 */
class Business extends Model
{
    use Favoritable, SoftDeletes;

    /**
     * Días de la semana en español, en claves compatibles con
     * `now()->format('l')` (que siempre devuelve el nombre en inglés sin
     * traducir). Compartido por el editor del emprendedor y la vitrina
     * pública para no duplicar el mapeo de días (1.3 del TODO).
     *
     * @var array<string, string>
     */
    public const DAY_LABELS = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    protected $fillable = [
        'organization_id',
        'municipality_id',
        'category_id',
        'name',
        'slug',
        'zone',
        'address',
        'latitude',
        'longitude',
        'whatsapp_number',
        'logo_path',
        'logo_alt_text',
        'hours',
        'social_links',
        'payment_info',
        'attributes',
        'whatsapp_faq_answers',
        'status',
        'suspension_reason',
        'suspended_at',
        'featured_until',
        'wompi_payment_source_id',
        'card_brand',
        'card_last_four',
        'auto_renew_enabled',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'array',
            'social_links' => 'array',
            'attributes' => 'array',
            'whatsapp_faq_answers' => 'array',
            'suspended_at' => 'datetime',
            'featured_until' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'auto_renew_enabled' => 'boolean',
        ];
    }

    /**
     * Débito automático (4.2 del TODO): fuente de pago tokenizada en
     * Wompi validada con 3D Secure y lista para cobros recurrentes sin el
     * cliente presente. `wompi_payment_source_id` puede existir sin
     * `auto_renew_enabled` mientras la validación 3DS todavía está en
     * curso — ver `RefreshBusinessPaymentSourceStatus`.
     */
    public function hasAutoRenewCard(): bool
    {
        return $this->auto_renew_enabled && filled($this->wompi_payment_source_id);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * IMM-021/IMM-022 del TODO inmersivo: el stand de este negocio en la
     * plaza voxel de su municipio, si tiene uno asignado. La crea y
     * mantiene `App\Domain\Immersive\Observers\BusinessStandObserver` — no
     * se asigna a mano desde aquí.
     *
     * @return HasOne<StandAssignment, $this>
     */
    public function standAssignment(): HasOne
    {
        return $this->hasOne(StandAssignment::class);
    }

    /**
     * Municipios adicionales donde también atiende este negocio, más allá
     * del principal (`municipality_id`) — una vitrina puede operar en más
     * de un municipio (0.2.2 del TODO). `zone` en la tabla pivote es la
     * zona propia de ESE municipio adicional; `businesses.zone` sigue
     * siendo la zona del municipio principal.
     *
     * @return BelongsToMany<Municipality, $this, BusinessMunicipality>
     */
    public function municipalities(): BelongsToMany
    {
        return $this->belongsToMany(Municipality::class, 'business_municipalities')
            ->using(BusinessMunicipality::class)
            ->withPivot('zone')
            ->withTimestamps();
    }

    /**
     * Todos los municipios donde atiende este negocio: el principal más
     * los adicionales.
     *
     * @return Collection<int, int>
     */
    public function allMunicipalityIds(): Collection
    {
        return collect([$this->municipality_id])
            ->merge($this->municipalities->pluck('id'))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Zona del negocio para un municipio dado: la propia (`zone`) si es el
     * principal, o la de la tabla pivote si es uno de los adicionales.
     */
    public function zoneFor(int $municipalityId): ?string
    {
        if ($this->municipality_id === $municipalityId) {
            return $this->zone;
        }

        return BusinessMunicipality::where('business_id', $this->id)
            ->where('municipality_id', $municipalityId)
            ->value('zone');
    }

    /**
     * Negocios que atienden un municipio dado, ya sea como principal o
     * como uno de sus municipios adicionales.
     *
     * @param  Builder<Business>  $query
     * @return Builder<Business>
     */
    public function scopeServesMunicipality(Builder $query, int $municipalityId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('municipality_id', $municipalityId)
            ->orWhereHas('municipalities', fn (Builder $m) => $m->where('municipalities.id', $municipalityId)));
    }

    /**
     * @return HasOne<Storefront, $this>
     */
    public function storefront(): HasOne
    {
        return $this->hasOne(Storefront::class);
    }

    /**
     * @return HasOne<BusinessChatbotProfile, $this>
     */
    public function chatbotProfile(): HasOne
    {
        return $this->hasOne(BusinessChatbotProfile::class);
    }

    /**
     * @return HasMany<BusinessChatConversation, $this>
     */
    public function chatConversations(): HasMany
    {
        return $this->hasMany(BusinessChatConversation::class)->latest('last_message_at');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('position');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest();
    }

    /**
     * @return HasMany<BusinessVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(BusinessVerification::class)->latest();
    }

    /**
     * @return HasMany<OrderConfirmation, $this>
     */
    public function orderConfirmations(): HasMany
    {
        return $this->hasMany(OrderConfirmation::class)->latest();
    }

    /**
     * @return HasMany<Recommendation, $this>
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class)->latest();
    }

    /**
     * Suscripción más reciente (4.1 del TODO). `CreateStorefront` siempre
     * crea una al plan "Gratis", así que en la práctica nunca es null.
     *
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Plan activo del negocio. Si por algún motivo no tiene una
     * suscripción utilizable, cae al plan "Gratis" sembrado (nunca null,
     * para que el resto del código no tenga que manejar la ausencia de
     * plan como caso especial).
     */
    public function activePlan(): Plan
    {
        $subscription = $this->subscription;

        if ($subscription && $subscription->isUsable()) {
            return $subscription->plan;
        }

        return Plan::where('slug', 'gratis')->firstOrFail();
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspendido';
    }

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    /**
     * @return HasMany<BusinessEntitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(BusinessEntitlement::class);
    }

    public function hasEntitlement(string $key): bool
    {
        return $this->entitlements
            ->where('key', $key)
            ->contains(fn (BusinessEntitlement $entitlement) => $entitlement->isActive());
    }

    /**
     * El chatbot con IA de la vitrina requiere el plan Emprendedor o el
     * add-on correspondiente comprado en "Impulsa tu negocio" (ver
     * `BusinessEntitlement::AI_CHATBOT`).
     */
    public function canUseAiChatbot(): bool
    {
        return $this->activePlan()->slug === Plan::EMPRENDEDOR || $this->hasEntitlement(BusinessEntitlement::AI_CHATBOT);
    }

    public function currentVerification(): ?BusinessVerification
    {
        if ($this->relationLoaded('verifications')) {
            return $this->verifications->first();
        }

        return $this->verifications()->first();
    }

    public function hasVerifiedBadge(): bool
    {
        return $this->currentVerification()?->isCurrentlyVerified() ?? false;
    }

    public function verifiedBadgeLabel(): ?string
    {
        $verification = $this->currentVerification();

        if (! $verification?->isCurrentlyVerified()) {
            return null;
        }

        return match ($verification->level) {
            'avanzada' => __('Verificación avanzada'),
            default => __('Verificación básica'),
        };
    }

    public function confirmedOrdersCount(): int
    {
        if ($this->relationLoaded('orderConfirmations')) {
            return $this->orderConfirmations
                ->where('is_reputation_eligible', true)
                ->where('status', OrderConfirmation::COMPLETADO)
                ->count();
        }

        return $this->orderConfirmations()
            ->where('is_reputation_eligible', true)
            ->where('status', OrderConfirmation::COMPLETADO)
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Recommendation>
     */
    public function publishedRecommendations(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->relationLoaded('recommendations')) {
            return $this->recommendations->where('status', Recommendation::PUBLICADA)->values();
        }

        return $this->recommendations()->where('status', Recommendation::PUBLICADA)->get();
    }

    /**
     * `latitude`/`longitude` son opcionales: el emprendedor los comparte
     * desde el editor con "Usar mi ubicación actual" (1.1.1/1.5 del TODO,
     * cercanía sin depender de geolocalización avanzada). Sin ellos, el
     * negocio sigue apareciendo en la Plaza, solo sin orden por distancia.
     */
    public function hasCoordinates(): bool
    {
        return filled($this->latitude) && filled($this->longitude);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Texto libre de horario guardado en `hours` (0.6/1.3 del TODO: sin un
     * horario estructurado por día todavía, solo una nota editable).
     */
    public function hoursNote(): ?string
    {
        return $this->hours['note'] ?? null;
    }

    /**
     * Respuesta editable que el negocio dejó para una pregunta frecuente del
     * Copiloto de WhatsApp (4.4 del TODO: "respuestas frecuentes
     * editables"). `null` cuando no hay override — el Copiloto usa el texto
     * generado automáticamente igual que antes.
     */
    public function faqAnswer(string $key): ?string
    {
        return $this->whatsapp_faq_answers[$key] ?? null;
    }

    public function hasStructuredSchedule(): bool
    {
        return ! empty($this->hours['schedule'] ?? []);
    }

    /**
     * Horario por día listo para mostrar (1.3 del TODO). No decide nada de
     * presentación en la vista — solo arma el texto por día.
     *
     * @return array<string, string>
     */
    public function scheduleForDisplay(): array
    {
        $schedule = $this->hours['schedule'] ?? [];
        $result = [];

        foreach (self::DAY_LABELS as $key => $label) {
            $day = $schedule[$key] ?? null;

            $result[$label] = match (true) {
                ! $day || ($day['closed'] ?? false) => __('Cerrado'),
                empty($day['open']) || empty($day['close']) => __('Sin definir'),
                default => "{$day['open']} - {$day['close']}",
            };
        }

        return $result;
    }

    /**
     * "Abierto ahora"/"Cerrado" calculado (1.3 del TODO). `null` cuando no
     * hay horario estructurado en absoluto — no es calculable, no se debe
     * asumir ni abierto ni cerrado.
     */
    public function isOpenNow(): ?bool
    {
        if (! $this->hasStructuredSchedule()) {
            return null;
        }

        $today = $this->hours['schedule'][strtolower(now()->format('l'))] ?? null;

        if (! $today || ($today['closed'] ?? false)) {
            return false;
        }

        if (empty($today['open']) || empty($today['close'])) {
            return null;
        }

        $now = now()->format('H:i');

        return $now >= $today['open'] && $now <= $today['close'];
    }

    /**
     * Etiquetas activas seleccionadas por el negocio (1.3 del TODO). Si un
     * moderador desactiva una etiqueta desde Filament, los negocios que la
     * tenían simplemente dejan de mostrarla, sin limpieza adicional.
     *
     * @return Collection<int, BusinessAttribute>
     */
    public function activeAttributes(): Collection
    {
        // $this->attributes (sin getAttribute()) es la propiedad interna de
        // Eloquent con el array crudo de TODOS los atributos del modelo, no
        // el valor casteado de la columna JSON `attributes` — hay que pasar
        // por el accessor explícitamente para evitar la colisión de nombre.
        $slugs = $this->getAttribute('attributes') ?? [];

        if ($slugs === []) {
            return collect();
        }

        return BusinessAttribute::where('is_active', true)
            ->whereIn('slug', $slugs)
            ->orderBy('name')
            ->get();
    }

    /**
     * Formas de pago que el negocio marcó como aceptadas (pedido del
     * usuario: mostrarlas con su logo en la vitrina).
     *
     * @return BelongsToMany<PaymentMethod, $this>
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class);
    }

    /**
     * Usuarios con una membresía en este negocio. El rol de cada uno vive en
     * spatie/laravel-permission (team = este negocio), no en la tabla pivote.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_memberships')
            ->withPivot(['status'])
            ->withTimestamps();
    }
}
