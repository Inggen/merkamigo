<?php

namespace App\Domain\Businesses\Events;

class BusinessRestored
{
    public function __construct(public readonly int $businessId) {}
}
