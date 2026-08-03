<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MoveProductToBusiness
{
    public function handle(Product $product, Business $targetBusiness, User $actor): Product
    {
        if ($product->business_id === $targetBusiness->id) {
            return $product->refresh()->load(['media', 'variants']);
        }

        return DB::transaction(function () use ($product, $targetBusiness, $actor) {
            $fromBusinessId = $product->business_id;

            $product->update([
                'business_id' => $targetBusiness->id,
                'slug' => $this->uniqueSlug($targetBusiness, $product->name),
                'position' => ((int) $targetBusiness->products()->max('position')) + 1,
            ]);

            app(RecordAuditLog::class)->handle($actor, 'product.moved', $product, [
                'from_business_id' => $fromBusinessId,
                'to_business_id' => $targetBusiness->id,
            ]);

            return $product->refresh()->load(['media', 'variants']);
        });
    }

    private function uniqueSlug(Business $business, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $attempt = 1;

        while ($business->products()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".(++$attempt);
        }

        return $slug;
    }
}
