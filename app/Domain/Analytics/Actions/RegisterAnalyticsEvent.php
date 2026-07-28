<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registra un evento medible del negocio, compartida por
 * `RegisterStoreView` y `RegisterWhatsAppClick` (0.4 del TODO: no duplicar
 * reglas de negocio). Aplica dos reglas simples anti-ruido (1.8 del TODO:
 * "evitar duplicación evidente de eventos y tráfico automatizado"):
 *
 * - Deduplica: la misma combinación negocio+tipo+sujeto+visitante no cuenta
 *   dos veces dentro de una misma ventana corta.
 * - Filtra bots conocidos por user-agent. Es una heurística simple, no un
 *   detector de bots completo — suficiente para el piloto.
 */
class RegisterAnalyticsEvent
{
    private const DEDUPE_MINUTES = 30;

    private const BOT_USER_AGENT_PATTERNS = [
        'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python-requests',
        'headlesschrome', 'facebookexternalhit', 'preview',
    ];

    public function handle(Business $business, string $type, ?Model $subject, Request $request): void
    {
        $userAgent = (string) $request->userAgent();

        if ($this->looksLikeBot($userAgent)) {
            return;
        }

        $visitorHash = $this->visitorHash($request, $userAgent);

        $recentDuplicate = AnalyticsEvent::query()
            ->where('business_id', $business->id)
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

        AnalyticsEvent::create([
            'business_id' => $business->id,
            'type' => $type,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'visitor_hash' => $visitorHash,
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
