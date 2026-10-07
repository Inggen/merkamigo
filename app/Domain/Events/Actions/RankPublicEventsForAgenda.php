<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Support\Collection;

/**
 * Orden de la agenda pública de eventos
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 6): "Ordenar
 * primero por coincidencia de ubicación/fecha/interés; luego aplicar
 * impulso de visibilidad Negocios, Emprendedor, Básico. Añadir rotación,
 * diversidad de vitrinas y límite de repetición para que Básico reciba
 * exposición real." Capacidad configurable, no una decisión comercial ya
 * cerrada (ver "Decisiones de producto para arrancar" del documento) —
 * los pesos viven en `config('events.plan_weights')`, ajustables sin
 * tocar código.
 *
 * La relevancia por ubicación/fecha se resuelve ANTES de llegar aquí: el
 * controlador ya filtró por municipio cuando el visitante lo pidió (así
 * que, en esa vista, nunca hay eventos de "otra zona" con los que
 * competir) y agrupó por fecha — el plan SOLO reordena eventos del MISMO
 * día, nunca adelanta un evento de la semana próxima sobre uno de mañana.
 * Sin filtro de municipio activo no existe todavía una señal de
 * "cercanía" real (geolocalización del visitante) con la que comparar,
 * así que ahí el plan sí puede ordenar dentro del mismo día.
 */
class RankPublicEventsForAgenda
{
    /**
     * @param  Collection<int, PublicEvent>  $events  ya filtrados (publicado, futuro) con `business.subscription.plan` precargado
     * @return Collection<int, PublicEvent>
     */
    public function handle(Collection $events): Collection
    {
        return $events
            ->groupBy(fn (PublicEvent $event) => $event->starts_at->toDateString())
            ->sortKeys()
            ->flatMap(fn (Collection $dayEvents) => $this->rankDay($dayEvents))
            ->values();
    }

    /**
     * @param  Collection<int, PublicEvent>  $dayEvents
     * @return Collection<int, PublicEvent>
     */
    private function rankDay(Collection $dayEvents): Collection
    {
        $tiers = $dayEvents->groupBy(fn (PublicEvent $event) => $this->planWeight($event->business));

        return collect(array_keys(config('events.plan_weights')))
            ->map(fn ($slug) => $this->planWeight((string) $slug))
            ->unique()
            ->sortDesc()
            ->flatMap(fn (int $weight) => $this->interleaveByBusiness($tiers->get($weight, collect())))
            ->values();
    }

    /**
     * Mismo tipo de rotación horaria que ya usa la agenda de
     * oportunidades/impulso del negocio en otras partes del proyecto:
     * cambia la posición relativa dentro de un mismo nivel de plan cada
     * hora, para que no sea siempre el mismo negocio el primero del
     * bloque — sin eso, "rotación" sería solo una palabra en el código.
     *
     * @param  Collection<int, PublicEvent>  $events
     * @return Collection<int, PublicEvent>
     */
    private function interleaveByBusiness(Collection $events): Collection
    {
        // `->toBase()`: `groupBy()` sobre una Collection de Eloquent puede
        // devolver otra Collection de Eloquent como contenedor externo,
        // cuyo `merge()` asume que cada elemento es un Model (llama a
        // `getKey()`) — acá cada elemento es un grupo (Collection), no un
        // modelo, así que se fuerza la Collection base antes de
        // reordenar.
        $groups = $events->groupBy('business_id')->values()->toBase();

        if ($groups->count() <= 1) {
            return $events;
        }

        $seed = ((int) now()->format('H')) % $groups->count();
        $rotated = $groups->slice($seed)->merge($groups->slice(0, $seed))->values();

        $result = collect();
        $max = $rotated->map(fn (Collection $g) => $g->count())->max();

        for ($i = 0; $i < $max; $i++) {
            foreach ($rotated as $group) {
                $item = $group->values()->get($i);

                if ($item) {
                    $result->push($item);
                }
            }
        }

        return $result;
    }

    private function planWeight(Business|string $businessOrSlug): int
    {
        $slug = $businessOrSlug instanceof Business ? $businessOrSlug->activePlan()->slug : $businessOrSlug;

        return config("events.plan_weights.{$slug}", 1);
    }
}
