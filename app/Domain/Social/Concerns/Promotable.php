<?php

namespace App\Domain\Social\Concerns;

use App\Domain\Social\Models\ContentPromotion;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Promotable
{
    public function promotions(): MorphMany
    {
        return $this->morphMany(ContentPromotion::class, 'promotable');
    }

    public function activePromotion(): MorphOne
    {
        return $this->morphOne(ContentPromotion::class, 'promotable')
            ->where('status', ContentPromotion::ACTIVA)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latestOfMany();
    }
}
