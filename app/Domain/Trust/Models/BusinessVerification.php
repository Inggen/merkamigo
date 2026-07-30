<?php

namespace App\Domain\Trust\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessVerification extends Model
{
    public const SIN_INICIAR = 'sin_iniciar';

    public const EN_REVISION = 'en_revision';

    public const REQUIERE_AJUSTES = 'requiere_ajustes';

    public const VERIFICADA = 'verificada';

    public const VENCIDA = 'vencida';

    public const REVOCADA = 'revocada';

    protected $fillable = [
        'business_id',
        'requested_by',
        'level',
        'status',
        'legal_name',
        'contact_name',
        'contact_document_type',
        'contact_document_number',
        'verification_document_path',
        'request_note',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isCurrentlyVerified(): bool
    {
        if ($this->status !== self::VERIFICADA) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function documentUrl(): ?string
    {
        return $this->verification_document_path
            ? Storage::disk('private')->url($this->verification_document_path)
            : null;
    }
}
