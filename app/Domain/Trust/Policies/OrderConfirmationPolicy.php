<?php

namespace App\Domain\Trust\Policies;

use App\Domain\Trust\Models\OrderConfirmation;
use App\Models\User;

/**
 * Un pedido confirmado tiene dos partes legítimas (5.1 del TODO): el
 * comprador (`customer_user_id`) o cualquier miembro del equipo del
 * negocio. Mismo criterio que ya usan `⚡pedidos.blade.php` (lado
 * cliente) y `⚡oportunidades.blade.php` (lado negocio) de forma inline,
 * unificado aquí para la API en una sola regla.
 */
class OrderConfirmationPolicy
{
    public function view(User $user, OrderConfirmation $order): bool
    {
        return $this->isParty($user, $order);
    }

    public function update(User $user, OrderConfirmation $order): bool
    {
        return $this->isParty($user, $order);
    }

    private function isParty(User $user, OrderConfirmation $order): bool
    {
        if ($order->customer_user_id === $user->id) {
            return true;
        }

        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($order->business_id);
        $user->unsetRelation('roles');
        $isMember = $user->hasAnyRole(['owner', 'admin', 'collaborator']);

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');

        return $isMember;
    }
}
