<?php

namespace App\Domain\Immersive\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Immersive\Models\StandAssignment;
use App\Domain\Immersive\Models\StandSlot;
use App\Domain\Immersive\Models\StandZone;
use Illuminate\Support\Collection;

/**
 * IMM-022 del TODO inmersivo: "toda vitrina elegible recibe plaza y
 * ubicación automáticamente". Orden de §5 "Asignación automática":
 * resolver municipio → experiencia publicada → plazas activas en orden →
 * slot libre compatible (tamaño y, si aplica, categoría) → priorizar la
 * zona menos ocupada → crear asignación persistente.
 *
 * Idempotente: se puede llamar en cada guardado del negocio
 * (`BusinessStandObserver`) sin efecto si ya tiene un slot válido.
 */
class AssignBusinessToStand
{
    public function handle(Business $business): StandAssignment
    {
        $assignment = StandAssignment::firstOrCreate(
            ['business_id' => $business->id],
            ['status' => 'sin_configurar'],
        );

        if (! $business->isPublished()) {
            return app(ReleaseBusinessStand::class)->handle($business);
        }

        $template = $this->resolveTemplate($assignment);

        if (! $template) {
            return $this->save($assignment, ['status' => 'sin_cupo']);
        }

        if ($this->currentSlotStillValid($assignment, $template, $business)) {
            return $this->save($assignment, [
                'status' => 'publicado',
                'object_template_id' => $template->id,
            ]);
        }

        $hadInvalidSlot = filled($assignment->stand_slot_id);

        $municipality = $business->municipality;
        $experience = $municipality?->publishedImmersiveExperience;

        if (! $experience) {
            return $this->save($assignment, [
                'status' => $hadInvalidSlot ? 'reubicacion_requerida' : 'pendiente',
                'stand_slot_id' => null,
                'immersive_plaza_id' => null,
            ]);
        }

        $slot = $this->findPreferredSlot($assignment, $template, $business)
            ?? $this->findCompatibleSlot($experience, $template, $business);

        if (! $slot) {
            return $this->save($assignment, [
                'status' => $hadInvalidSlot ? 'reubicacion_requerida' : 'sin_cupo',
                'stand_slot_id' => null,
                'immersive_plaza_id' => null,
            ]);
        }

        $slot->update(['status' => 'ocupada']);

        return $this->save($assignment, [
            'status' => 'publicado',
            'object_template_id' => $template->id,
            'stand_slot_id' => $slot->id,
            'immersive_plaza_id' => $slot->zone->immersive_plaza_id,
            'previous_slot_id' => null,
            'assigned_at' => now(),
        ]);
    }

    private function resolveTemplate(StandAssignment $assignment): ?ImmersiveObjectTemplate
    {
        if ($assignment->object_template_id) {
            $template = ImmersiveObjectTemplate::find($assignment->object_template_id);

            if ($template) {
                return $template;
            }
        }

        return ImmersiveObjectTemplate::query()
            ->where('category', 'stand')
            ->where('status', 'publicada')
            ->orderBy('id')
            ->first();
    }

    private function currentSlotStillValid(StandAssignment $assignment, ImmersiveObjectTemplate $template, Business $business): bool
    {
        if (! $assignment->stand_slot_id) {
            return false;
        }

        $slot = StandSlot::find($assignment->stand_slot_id);

        if (! $slot || in_array($slot->status, ['bloqueada', 'invalida'], true)) {
            return false;
        }

        if ($slot->max_width < $template->max_width || $slot->max_depth < $template->max_depth) {
            return false;
        }

        if ($slot->allowed_category_id && $slot->allowed_category_id !== $business->category_id) {
            return false;
        }

        return true;
    }

    private function findPreferredSlot(StandAssignment $assignment, ImmersiveObjectTemplate $template, Business $business): ?StandSlot
    {
        if (! $assignment->previous_slot_id) {
            return null;
        }

        $slot = StandSlot::find($assignment->previous_slot_id);

        if (! $slot || $slot->status !== 'disponible') {
            return null;
        }

        if ($slot->max_width < $template->max_width || $slot->max_depth < $template->max_depth) {
            return null;
        }

        if ($slot->allowed_category_id && $slot->allowed_category_id !== $business->category_id) {
            return null;
        }

        return $slot;
    }

    /**
     * Recorre las plazas activas de la experiencia, en orden, y dentro de
     * cada una prioriza la zona con menos slots ocupados (distribución
     * equilibrada, §5: "evitando concentrar una misma categoría").
     */
    private function findCompatibleSlot(
        ImmersiveExperience $experience,
        ImmersiveObjectTemplate $template,
        Business $business,
    ): ?StandSlot {
        /** @var Collection<int, ImmersivePlaza> $plazas */
        $plazas = $experience->plazas()->where('status', 'activa')->orderBy('order')->get();

        foreach ($plazas as $plaza) {
            $zones = $plaza->zones()->withCount(['slots as occupied_count' => function ($query) {
                $query->where('status', 'ocupada');
            }])->orderBy('occupied_count')->orderBy('priority', 'desc')->get();

            foreach ($zones as $zone) {
                $slot = $this->findCompatibleSlotInZone($zone, $template, $business);

                if ($slot) {
                    return $slot;
                }
            }
        }

        return null;
    }

    private function findCompatibleSlotInZone(StandZone $zone, ImmersiveObjectTemplate $template, Business $business): ?StandSlot
    {
        return $zone->slots()
            ->where('status', 'disponible')
            ->where('max_width', '>=', $template->max_width)
            ->where('max_depth', '>=', $template->max_depth)
            ->where(function ($query) use ($business) {
                $query->whereNull('allowed_category_id')
                    ->orWhere('allowed_category_id', $business->category_id);
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function save(StandAssignment $assignment, array $attributes): StandAssignment
    {
        $assignment->update($attributes);

        return $assignment->fresh();
    }
}
