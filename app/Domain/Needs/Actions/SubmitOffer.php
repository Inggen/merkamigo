<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Exceptions\NeedClosedException;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Un negocio envía (o actualiza) su propuesta a una necesidad (2.2 del
 * TODO). Un negocio solo tiene una propuesta por necesidad — reenviar
 * después de retirarla actualiza la misma fila en vez de crear otra
 * (`offers` tiene un único índice `need_id`+`business_id`), lo que ya
 * limita el número de propuestas por negocio y necesidad sin reglas
 * adicionales.
 */
class SubmitOffer
{
    use ValidatesOfferData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, Need $need, array $data, User $actor): Offer
    {
        if (! $need->isOpenForOffers()) {
            throw new NeedClosedException;
        }

        $validated = Validator::make($data, $this->offerRules())->validate();

        if (! empty($validated['product_id']) && ! $business->products()->whereKey($validated['product_id'])->exists()) {
            unset($validated['product_id']);
        }

        return DB::transaction(function () use ($business, $need, $validated, $actor) {
            $offer = Offer::updateOrCreate(
                ['need_id' => $need->id, 'business_id' => $business->id],
                [
                    ...$validated,
                    'status' => Offer::ENVIADA,
                    'viewed_at' => null,
                    'withdrawn_at' => null,
                ],
            );

            if ($need->status === Need::PUBLICADA) {
                $need->update(['status' => Need::RECIBIENDO_OFERTAS]);
            }

            app(RecordAuditLog::class)->handle($actor, 'offer.submitted', $offer, ['need_id' => $need->id]);

            return $offer->fresh();
        });
    }
}
