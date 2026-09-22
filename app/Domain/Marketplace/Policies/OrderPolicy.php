<?php

namespace App\Domain\Marketplace\Policies;

use App\Domain\Marketplace\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * El comprador o cualquier miembro del negocio pueden ver el pedido.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->id === $order->buyer_user_id) {
            return true;
        }

        return $order->business->members()->where('users.id', $user->id)->exists();
    }
}
