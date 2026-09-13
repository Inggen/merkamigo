<?php

namespace App\Domain\Businesses\Events;

class BusinessSuspended
{
    public function __construct(public readonly int $businessId) {}
}
