<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventDish;

/**
 * CRUD de platos elegibles para eventos (Fase 2 · pestaña Configuración →
 * Tarifas y menú). El precio es propio del contexto de evento, no el del
 * catálogo normal del negocio (puede diferir), aunque puede enlazar a un
 * producto existente cuando aplica.
 */
class ManageEventDish
{
    /**
     * @param  array{name: string, description?: ?string, price_cents: int, product_id?: ?int}  $data
     */
    public function create(Business $business, array $data): EventDish
    {
        if ($data['price_cents'] < 0) {
            throw new EventActionException('El precio no puede ser negativo.');
        }

        $position = (int) $business->eventDishes()->max('position') + 1;

        // Mismo motivo que en `ManageEventSpace::create()`: fijar el
        // default explícito para que el objeto recién creado en memoria
        // ya quede disponible, sin depender de una relectura de la fila.
        return $business->eventDishes()->create([...$data, 'position' => $position, 'is_available' => true]);
    }

    /**
     * @param  array{name?: string, description?: ?string, price_cents?: int, is_available?: bool}  $data
     */
    public function update(EventDish $dish, array $data): EventDish
    {
        if (isset($data['price_cents']) && $data['price_cents'] < 0) {
            throw new EventActionException('El precio no puede ser negativo.');
        }

        $dish->update($data);

        return $dish;
    }

    public function delete(EventDish $dish): void
    {
        $dish->delete();
    }
}
