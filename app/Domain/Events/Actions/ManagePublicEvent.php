<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventSpace;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * CRUD y transiciones de estado de un evento público
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 5): "Formulario de
 * evento público... Permitir marcar si el evento público bloquea un
 * espacio... Estados borrador, publicado, finalizado y cancelado; un
 * cancelado no aparece como próximo ni acepta nuevas acciones."
 */
class ManagePublicEvent
{
    /**
     * @param  array{title: string, category?: ?string, description?: ?string, cover_path?: ?string, municipality_id?: ?int, location_text?: ?string, starts_at: string, ends_at?: ?string, capacity?: ?int, blocks_space?: bool, event_space_id?: ?int}  $data
     */
    public function create(Business $business, array $data, User $actor): PublicEvent
    {
        $this->assertBlocksSpaceIsValid($business, $data);

        return $business->publicEvents()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['title']),
            'status' => PublicEvent::BORRADOR,
            'created_by_user_id' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PublicEvent $event, array $data): PublicEvent
    {
        $this->assertNotLocked($event);
        $this->assertBlocksSpaceIsValid($event->business, [...$event->only(['blocks_space', 'event_space_id']), ...$data]);

        $event->update($data);

        return $event;
    }

    public function publish(PublicEvent $event, ?User $actor = null): PublicEvent
    {
        $this->assertNotLocked($event);

        if ($event->starts_at->isPast()) {
            throw new EventActionException('No puedes publicar un evento cuya fecha ya pasó.');
        }

        $event->update(['status' => PublicEvent::PUBLICADO]);

        // Fase 7: "auditar cambios sensibles" — un evento publicado pasa
        // a ser visible en toda la agenda pública y el feed.
        app(RecordAuditLog::class)->handle($actor, 'public_event.published', $event, [
            'business_id' => $event->business_id,
        ]);

        return $event;
    }

    public function cancel(PublicEvent $event, ?User $actor = null): PublicEvent
    {
        if ($event->status === PublicEvent::FINALIZADO) {
            throw new EventActionException('Un evento finalizado no se puede cancelar.');
        }

        $event->update(['status' => PublicEvent::CANCELADO]);

        app(RecordAuditLog::class)->handle($actor, 'public_event.cancelled', $event, [
            'business_id' => $event->business_id,
        ]);

        return $event;
    }

    public function delete(PublicEvent $event): void
    {
        if ($event->status === PublicEvent::PUBLICADO) {
            throw new EventActionException('Cancela el evento antes de eliminarlo.');
        }

        $event->delete();
    }

    private function assertNotLocked(PublicEvent $event): void
    {
        if (in_array($event->status, [PublicEvent::FINALIZADO, PublicEvent::CANCELADO], true)) {
            throw new EventActionException('Este evento ya no acepta cambios.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertBlocksSpaceIsValid(Business $business, array $data): void
    {
        if (! ($data['blocks_space'] ?? false)) {
            return;
        }

        $spaceId = $data['event_space_id'] ?? null;

        if (! $spaceId || ! EventSpace::where('id', $spaceId)->where('business_id', $business->id)->exists()) {
            throw new EventActionException('Selecciona un espacio válido para bloquear su disponibilidad.');
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'evento';
        $slug = $base;
        $suffix = 1;

        while (PublicEvent::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
