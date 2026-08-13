<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\ImmersivePlaza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registra un evento medible de una plaza inmersiva — misma disciplina
 * anti-ruido que `RegisterAnalyticsEvent` (hermana de esta acción, mismo
 * dominio): deduplica por ventana corta y filtra bots conocidos por
 * user-agent. Acción propia (no una abstracción compartida forzada con
 * `RegisterAnalyticsEvent`) porque escriben a modelos/tablas distintos con
 * sujetos principales distintos (negocio vs. plaza).
 */
class RegisterImmersiveEvent
{
    private const DEDUPE_MINUTES = 30;

    private const BOT_USER_AGENT_PATTERNS = [
        'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python-requests',
        'headlesschrome', 'facebookexternalhit', 'preview',
    ];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        ImmersivePlaza $plaza,
        string $type,
        Request $request,
        ?Business $business = null,
        ?Model $subject = null,
        ?array $metadata = null,
    ): void {
        $userAgent = (string) $request->userAgent();

        if ($this->looksLikeBot($userAgent)) {
            return;
        }

        $visitorHash = $this->visitorHash($request, $userAgent);

        $recentDuplicate = ImmersiveEvent::query()
            ->where('immersive_plaza_id', $plaza->id)
            ->where('type', $type)
            ->where('visitor_hash', $visitorHash)
            ->when($subject, fn ($query) => $query
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey()))
            ->when(! $subject, fn ($query) => $query->whereNull('subject_type'))
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        ImmersiveEvent::create([
            'immersive_plaza_id' => $plaza->id,
            'business_id' => $business?->id,
            'type' => $type,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'visitor_hash' => $visitorHash,
            'metadata' => $metadata,
        ]);
    }

    private function looksLikeBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        $userAgent = mb_strtolower($userAgent);

        foreach (self::BOT_USER_AGENT_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function visitorHash(Request $request, string $userAgent): string
    {
        return hash('sha256', $request->ip().'|'.$userAgent);
    }
}
