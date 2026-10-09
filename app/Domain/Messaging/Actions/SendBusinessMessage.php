<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Messaging\Models\BusinessMessage;
use App\Domain\Messaging\Notifications\BusinessMessageReceived;
use App\Models\User;
use App\Support\Media\MediaUploader;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendBusinessMessage
{
    public function handle(BusinessConversation $conversation, User $sender, ?string $body, ?UploadedFile $attachment = null): BusinessMessage
    {
        abort_unless($conversation->canBeViewedBy($sender), 403);
        abort_if(blank($body) && ! $attachment, 422, __('Escribe un mensaje o adjunta una foto.'));

        $validated = Validator::make(['body' => $body], [
            // Sin `required`: un mensaje puede ir solo con foto adjunta.
            'body' => ['nullable', 'string', 'max:2000', new NoLinks],
        ])->validate();

        $attachmentPath = $attachment
            ? app(MediaUploader::class)->store($attachment, 'message_attachment', "messages/{$conversation->id}")
            : null;

        $message = $conversation->messages()->create([
            'sender_user_id' => $sender->id,
            'body' => $validated['body'] ?? '',
            'attachment_path' => $attachmentPath,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $recipients = $sender->id === $conversation->customer_user_id
            ? $conversation->business->members()
                ->wherePivot('status', 'activo')
                ->whereKeyNot($sender->id)
                ->get()
            : collect([$conversation->customer])->where('id', '!=', $sender->id);

        // La notificación de un mensaje debe existir apenas el mensaje se
        // guarda. No depende del worker de colas: en producción puede estar
        // reiniciándose y eso no debe ocultar el aviso ni retrasar el push.
        Notification::sendNow($recipients, new BusinessMessageReceived($conversation, $message));

        return $message;
    }
}
