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
        return BusinessConversation::firstOrCreate([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'context_key' => $context['key'],
        ], [
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'context_label' => $context['label'],
            'context_url' => $context['url'],
        ]);
    }
}
