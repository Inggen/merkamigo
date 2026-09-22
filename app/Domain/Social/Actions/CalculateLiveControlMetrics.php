<?php

namespace App\Domain\Social\Actions;

use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Models\LiveStream;

class CalculateLiveControlMetrics
{
    /**
     * @return array{
     *     duration_seconds: int,
     *     current_viewers: int,
     *     unique_viewers: int,
     *     comments: int,
     *     orders: int,
     *     products_sold: int,
     *     sales_cents: int
     * }
     */
    public function handle(LiveStream $stream): array
    {
        $paidOrders = $stream->orders()->where('status', Order::PAGADO);

        return [
            'duration_seconds' => $stream->started_at
                ? (int) $stream->started_at->diffInSeconds($stream->ended_at ?? now())
                : 0,
            'current_viewers' => $stream->currentViewersCount(),
            'unique_viewers' => $stream->views()->count(),
            'comments' => $stream->messages()->where('status', 'publicado')->count(),
            'orders' => (clone $paidOrders)->count(),
            'products_sold' => (int) (clone $paidOrders)
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->sum('order_items.quantity'),
            'sales_cents' => (int) (clone $paidOrders)->sum('amount_cents'),
        ];
    }
}
