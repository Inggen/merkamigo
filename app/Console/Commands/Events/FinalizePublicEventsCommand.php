<?php

namespace App\Console\Commands\Events;

use App\Domain\Events\Models\PublicEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5: un evento
 * `publicado` cuya hora de fin (o de inicio, si no tiene fin) ya pasó
 * deja de aparecer como "próximo" — se marca `finalizado` en vez de
 * quedar publicado indefinidamente. Programado en `routes/console.php`.
 */
#[Signature('events:finalize-public-events')]
#[Description('Marca como finalizados los eventos públicos publicados cuya fecha ya pasó.')]
class FinalizePublicEventsCommand extends Command
{
    public function handle(): int
    {
        $finalized = PublicEvent::query()
            ->where('status', PublicEvent::PUBLICADO)
            ->where(fn ($q) => $q->where('ends_at', '<', now())
                ->orWhere(fn ($q2) => $q2->whereNull('ends_at')->where('starts_at', '<', now())))
            ->update(['status' => PublicEvent::FINALIZADO]);

        $this->info("Eventos públicos finalizados: {$finalized}.");

        return self::SUCCESS;
    }
}
