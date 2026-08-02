<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Trust\Models\BusinessVerification;
use App\Domain\Trust\Notifications\VerificationExpiringSoon;

/**
 * Recordatorios de renovación (3.1 del TODO): avisa a los miembros de un
 * negocio cuando su verificación vigente está por vencer, una sola vez por
 * verificación (`expiry_reminder_sent_at` evita reenviar cada día).
 */
class SendVerificationExpiryReminders
{
    private const REMINDER_WINDOW_DAYS = 14;

    public function handle(): int
    {
        $verifications = BusinessVerification::query()
            ->where('status', BusinessVerification::VERIFICADA)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(self::REMINDER_WINDOW_DAYS)])
            ->whereNull('expiry_reminder_sent_at')
            ->with('business.members')
            ->get();

        foreach ($verifications as $verification) {
            $members = $verification->business->members;

            if ($members->isEmpty()) {
                continue;
            }

            foreach ($members as $member) {
                $member->notify(new VerificationExpiringSoon($verification));
            }

            $verification->update(['expiry_reminder_sent_at' => now()]);
        }

        return $verifications->count();
    }
}
