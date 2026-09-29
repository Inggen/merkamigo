<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Models\User;

class StartBusinessConversation
{
    /**
     * @param  array{key: string, type: ?string, id: ?int, label: ?string, url: ?string}  $context
     */
    public function handle(Business $business, User $customer, array $context): BusinessConversation
    {
        $attributes = [
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'context_key' => $context['key'],
        ];

        // El índice único de (business_id, customer_user_id, context_key)
        // no distingue filas borradas en soft-delete — si no se restaura
        // acá, un firstOrCreate() normal chocaría con esa fila al intentar
        // insertar una nueva con las mismas llaves.
        $trashed = BusinessConversation::onlyTrashed()->where($attributes)->first();

        if ($trashed) {
            $trashed->restore();

            return $trashed;
        }

        return BusinessConversation::firstOrCreate($attributes, [
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'context_label' => $context['label'],
            'context_url' => $context['url'],
        ]);
    }
}
