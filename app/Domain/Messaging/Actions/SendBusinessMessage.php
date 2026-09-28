<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Messaging\Models\BusinessMessage;
use App\Domain\Messaging\Notifications\BusinessMessageReceived;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendBusinessMessage
{
    public function handle(BusinessConversation $conversation, User $sender, string $body): BusinessMessage
    {
        abort_unless($conversation->canBeViewedBy($sender), 403);

        $validated = Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:2000', new NoLinks],
        ])->validate();

        $message = $conversation->messages()->create([
            'sender_user_id' => $sender->id,
            'body' => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $recipients = $sender->id === $conversation->customer_user_id
            ? $conversation->business->members()
                ->wherePivot('status', 'activo')
                ->whereKeyNot($sender->id)
                ->get()
            : collect([$conversation->customer])->where('id', '!=', $sender->id);

        Notification::send($recipients, new BusinessMessageReceived($conversation, $message));

        return $message;
    }
}
