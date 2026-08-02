<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analytics\Notifications\StorefrontNeedsAttention;
use App\Domain\Analytics\Notifications\WeeklyBusinessReport;
use App\Domain\Billing\Notifications\PaymentFailed;
use App\Domain\Needs\Notifications\OfferSubmitted;
use App\Domain\Needs\Notifications\OfferWithdrawn;
use App\Domain\Trust\Notifications\OrderConfirmedByBusiness;
use App\Domain\Trust\Notifications\OrderPendingYourConfirmation;
use App\Domain\Trust\Notifications\VerificationExpiringSoon;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Preferencias de canal por tipo de notificación (5.2 del TODO): un
 * usuario puede desactivar push para tipos específicos sin afectar el
 * canal `database` (Centro de actividad), que siempre queda disponible.
 */
class NotificationPreferencesController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    private const PUSH_TYPES = [
        'offer_submitted' => OfferSubmitted::class,
        'offer_withdrawn' => OfferWithdrawn::class,
        'order_confirmed_by_business' => OrderConfirmedByBusiness::class,
        'order_pending_your_confirmation' => OrderPendingYourConfirmation::class,
        'verification_expiring_soon' => VerificationExpiringSoon::class,
        'weekly_business_report' => WeeklyBusinessReport::class,
        'storefront_needs_attention' => StorefrontNeedsAttention::class,
        'payment_failed' => PaymentFailed::class,
    ];

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'push_disabled' => ['array'],
            'push_disabled.*' => ['string', 'in:'.implode(',', array_keys(self::PUSH_TYPES))],
        ]);

        $disabledClasses = $this->classesFor($data['push_disabled'] ?? []);

        $user = $request->user();
        $user->update(['notification_channel_preferences' => ['push_disabled' => $disabledClasses]]);

        return ApiResponse::response(['push_disabled' => array_values($data['push_disabled'] ?? [])]);
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, string>
     */
    private function classesFor(array $types): array
    {
        return collect($types)->map(fn (string $type) => self::PUSH_TYPES[$type])->values()->all();
    }
}
