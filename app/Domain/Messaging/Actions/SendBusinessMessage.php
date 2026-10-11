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

        // Pedido del usuario (2026-10-11): enviar sin texto ni foto
        // mostraba una página de error de Laravel sin manejar (el
        // `abort_if` de antes lanzaba una excepción HTTP cruda que
        // Livewire no sabe convertir en un error del formulario). Ahora
        // esa misma regla ("hace falta texto o foto") vive en el
        // Validator, igual que el resto de reglas de `body` — así
        // Livewire la atrapa solo y la muestra con el `@error('body')`
        // que ya existía en la vista, sin tocar nada ahí.
        $validated = Validator::make(['body' => $body], [
            // Solo obligatorio si no hay foto adjunta: un mensaje puede
            // ir solo con foto.
            'body' => [$attachment ? 'nullable' : 'required', 'string', 'max:2000', new NoLinks],
        ], [
            'body.required' => __('Escribe un mensaje o adjunta una foto.'),
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
