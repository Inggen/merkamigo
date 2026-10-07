<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Models\Post;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Evento público de la agenda (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 5). `blocks_space` indica si reserva disponibilidad de
 * `event_space_id` — una publicación informativa (ej. "mercado
 * artesanal" en una plaza) no necesariamente bloquea nada.
 *
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 */
class PublicEvent extends Model
{
    protected $fillable = [
        'business_id', 'event_space_id', 'municipality_id', 'title', 'category', 'slug', 'description',
        'cover_path', 'location_text', 'starts_at', 'ends_at', 'capacity', 'price_cents', 'blocks_space',
        'status', 'created_by_user_id', 'post_id',
    ];

    public const BORRADOR = 'borrador';

    public const PUBLICADO = 'publicado';

    public const FINALIZADO = 'finalizado';

    public const CANCELADO = 'cancelado';

    /**
     * Lista curada que ofrece el panel del negocio (Fase 6: "filtros por
     * ... categoría") — sugerida, no forzada a nivel de BD.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'musica' => 'Música',
        'taller' => 'Taller',
        'mercado' => 'Mercado',
        'gastronomia' => 'Gastronomía',
        'arte' => 'Arte y cultura',
        'deporte' => 'Deporte',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'price_cents' => 'integer',
            'blocks_space' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<EventSpace, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(EventSpace::class, 'event_space_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<EventReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(EventReservation::class);
    }

    /**
     * @return HasOne<Post, $this>
     */
    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }

    /**
     * Reservas de CUPO/asistencia al evento (pedido del usuario,
     * distinto de `reservations()`: esa reserva el ESPACIO del negocio
     * para un evento privado propio; esta reserva un lugar PARA ESTE
     * evento público, con entrada por QR).
     *
     * @return HasMany<EventAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function isFree(): bool
    {
        return ! $this->price_cents;
    }

    /**
     * Cupos disponibles: `null` si el evento no tiene capacidad
     * definida (sin límite). Cuenta asistencia confirmada y pendiente de
     * pago todavía vigente (misma regla de retención que las reservas de
     * espacio) — nunca permite vender más cupo del real mientras un
     * pago sigue en curso.
     */
    public function spotsRemaining(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        $taken = $this->attendances()
            ->where(function ($q) {
                $q->where('status', EventAttendance::CONFIRMADA)
                    ->orWhere(function ($q2) {
                        $q2->where('status', EventAttendance::PENDIENTE_PAGO)->where('expires_at', '>', now());
                    });
            })
            ->sum('quantity');

        return max(0, $this->capacity - (int) $taken);
    }

    public function categoryLabel(): ?string
    {
        return $this->category ? (self::CATEGORIES[$this->category] ?? $this->category) : null;
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Momento en que el evento deja de ocupar el espacio/estar "próximo"
     * — usa `ends_at` si está definido; si no, el final de ese mismo día
     * (un evento sin hora de fin declarada se trata como que ocupa el
     * resto del día, nunca como un instante puntual — más seguro para
     * `blocks_space`, que es justamente para evitar dobles reservas).
     */
    public function effectiveEndsAt(): CarbonInterface
    {
        return $this->ends_at ?? $this->starts_at->copy()->endOfDay();
    }
}
