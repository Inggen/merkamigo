<?php

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\CancelLoyaltyRedemption;
use App\Domain\Loyalty\Actions\ReserveLoyaltyRedemption;
use App\Domain\Loyalty\Actions\SubmitLoyaltyReceiptClaim;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyReward;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Sección única Merkapuntos del cliente (TODO_Merkapuntos.md, F2.4/F2.5):
 * saldo por negocio, premios canjeables, canje activo y QR de
 * identificación en una sola vista — nada de billeteras separadas. El
 * canje y la solicitud excepcional se abren como modal dentro de esta
 * misma página (decisión de UX obligatoria del TODO).
 */
new #[Layout('layouts::cliente')] #[Title('Merkapuntos')] class extends Component
{
    use WithFileUploads;

    public ?string $reservedToken = null;

    public ?int $reservedRedemptionId = null;

    public ?int $receiptClaimBusinessId = null;

    public $receiptFile = null;

    public string $receiptDescription = '';

    #[Computed]
    public function accounts()
    {
        return Auth::user()->loyaltyAccounts()
            ->with('business')
            ->get()
            ->filter(fn ($account) => $account->business !== null)
            ->map(fn ($account) => (object) [
                'account' => $account,
                'business' => $account->business,
                'available' => $account->availablePoints(),
            ])
            ->sortBy(fn ($row) => $row->business->name)
            ->values();
    }

    #[Computed]
    public function rewardsByBusiness(): array
    {
        $businessIds = $this->accounts->pluck('business.id')->all();

        if ($businessIds === []) {
            return [];
        }

        return LoyaltyReward::whereIn('business_id', $businessIds)
            ->where('status', LoyaltyReward::PUBLICADO)
            ->with('product.media')
            ->orderBy('points_cost')
            ->get()
            ->groupBy('business_id')
            ->all();
    }

    #[Computed]
    public function activeRedemptions()
    {
        $accountIds = $this->accounts->pluck('account.id')->all();

        if ($accountIds === []) {
            return collect();
        }

        return LoyaltyRedemption::whereIn('account_id', $accountIds)
            ->where('status', LoyaltyRedemption::RESERVADO)
            ->with('account.business')
            ->latest()
            ->get();
    }

    #[Computed]
    public function movements()
    {
        $accountIds = $this->accounts->pluck('account.id')->all();

        if ($accountIds === []) {
            return collect();
        }

        return LoyaltyMovement::whereIn('account_id', $accountIds)
            ->with('account.business')
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function enrolledBusinesses()
    {
        return Business::whereHas('loyaltyEnrollment', fn ($q) => $q->where('status', 'activa'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function featuredRewards()
    {
        $accountBusinessIds = $this->accounts->pluck('business.id');

        return LoyaltyReward::query()
            ->where('status', LoyaltyReward::PUBLICADO)
            ->whereHas('business', fn ($query) => $query
                ->where('status', 'publicado')
                ->whereHas('loyaltyEnrollment', fn ($enrollment) => $enrollment->where('status', 'activa')))
            ->with(['business.municipality', 'business.category', 'product.media'])
            ->latest()
            ->limit(12)
            ->get()
            ->sortByDesc(fn (LoyaltyReward $reward) => $accountBusinessIds->contains($reward->business_id))
            ->take(6)
            ->values();
    }

    #[Computed]
    public function primaryGoal(): ?object
    {
        return $this->accounts
            ->flatMap(function ($row) {
                return collect($this->rewardsByBusiness[$row->business->id] ?? [])
                    ->filter(fn (LoyaltyReward $reward) => $reward->isRedeemable())
                    ->map(fn (LoyaltyReward $reward) => (object) [
                        'reward' => $reward,
                        'business' => $row->business,
                        'available' => $row->available,
                        'missing' => max(0, $reward->points_cost - $row->available),
                        'progress' => min(100, (int) round(($row->available / max(1, $reward->points_cost)) * 100)),
                    ]);
            })
            ->sortBy('missing')
            ->first();
    }

    public function reserve(int $rewardId): void
    {
        $reward = LoyaltyReward::findOrFail($rewardId);

        try {
            $result = app(ReserveLoyaltyRedemption::class)->handle(Auth::user(), $reward, (string) Str::uuid());
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->reservedToken = $result['token'];
        $this->reservedRedemptionId = $result['redemption']->id;

        app(RegisterAnalyticsEvent::class)->handle($reward->business, AnalyticsEvent::LOYALTY_REDEMPTION_RESERVED, $result['redemption'], request());

        unset($this->accounts, $this->rewardsByBusiness, $this->activeRedemptions, $this->movements);

        Flux::modal('canje-listo')->show();
    }

    public function viewCode(int $redemptionId): void
    {
        $redemption = LoyaltyRedemption::with('account')->findOrFail($redemptionId);

        abort_unless($redemption->account->user_id === Auth::id(), 403);
        abort_unless($redemption->status === LoyaltyRedemption::RESERVADO, 404);

        // F2.5, aceptación: "reabrir muestra el mismo canje activo
        // correspondiente" — `token_encrypted` (cast `encrypted`) permite
        // volver a mostrar el mismo código sin haberlo guardado en claro.
        $this->reservedToken = $redemption->token_encrypted;
        $this->reservedRedemptionId = $redemptionId;

        Flux::modal('canje-listo')->show();
    }

    public function cancelRedemption(int $redemptionId): void
    {
        $redemption = LoyaltyRedemption::with(['account', 'reward.business'])->findOrFail($redemptionId);

        abort_unless($redemption->account->user_id === Auth::id(), 403);

        try {
            app(CancelLoyaltyRedemption::class)->handle($redemption, CancelLoyaltyRedemption::RAZON_CLIENTE, Auth::user());
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        app(RegisterAnalyticsEvent::class)->handle($redemption->reward->business, AnalyticsEvent::LOYALTY_REDEMPTION_RELEASED, $redemption, request());

        if ($this->reservedRedemptionId === $redemptionId) {
            $this->reservedToken = null;
            $this->reservedRedemptionId = null;
            Flux::modal('canje-listo')->close();
        }

        unset($this->accounts, $this->rewardsByBusiness, $this->activeRedemptions, $this->movements);

        Flux::toast(text: __('Canje cancelado. Tus puntos están disponibles de nuevo.'));
    }

    public function submitReceiptClaim(): void
    {
        $this->validate([
            'receiptClaimBusinessId' => ['required', 'integer'],
            'receiptFile' => ['required', 'image', 'max:5120'],
            'receiptDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $business = Business::findOrFail($this->receiptClaimBusinessId);

        try {
            app(SubmitLoyaltyReceiptClaim::class)->handle($business, Auth::user(), $this->receiptFile, $this->receiptDescription ?: null);
        } catch (LoyaltyActionException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->reset(['receiptClaimBusinessId', 'receiptFile', 'receiptDescription']);
        Flux::modal('solicitud-excepcional')->close();
        Flux::toast(text: __('Solicitud enviada. El negocio la revisará pronto.'));
    }
}; ?>

@include('merkapuntos.customer-dashboard')
