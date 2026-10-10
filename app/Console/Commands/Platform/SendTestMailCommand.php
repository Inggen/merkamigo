<?php

namespace App\Console\Commands\Platform;

use App\Domain\Platform\Notifications\PlatformTestMail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

#[Signature('mail:test {recipient : Correo que recibirá la prueba}')]
#[Description('Envía un correo inmediato usando la plantilla general de Merkamigo.')]
class SendTestMailCommand extends Command
{
    public function handle(): int
    {
        $recipient = trim((string) $this->argument('recipient'));
        $validator = Validator::make(
            ['recipient' => $recipient],
            ['recipient' => ['required', 'email:rfc', 'max:255']],
        );

        if ($validator->fails()) {
            $this->error(__('Ingresa una dirección de correo válida.'));

            return self::FAILURE;
        }

        Notification::route('mail', $recipient)
            ->notifyNow(new PlatformTestMail(
                sentBy: auth()->user()?->name ?? __('Administrador de Merkamigo'),
            ));

        $this->info(__('Correo de prueba enviado a :recipient.', ['recipient' => $recipient]));

        return self::SUCCESS;
    }
}
