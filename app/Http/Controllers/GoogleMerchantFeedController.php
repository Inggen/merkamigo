<?php

namespace App\Http\Controllers;

use App\Domain\Storefronts\Models\Product;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use Illuminate\Http\Response;

class GoogleMerchantFeedController extends Controller
{
    public function index(GoogleMerchantProductMapper $mapper): Response
    {
        $items = [];

        Product::query()
            ->where('type', 'producto')
            ->where('status', 'publicado')
            ->whereHas('business', fn ($query) => $query->where('status', 'publicado'))
            ->with(['business.category', 'business.storefront', 'media'])
            ->chunk(200, function ($products) use (&$items, $mapper): void {
                foreach ($products as $product) {
                    $item = $mapper->map($product);

                    if ($item !== null) {
                        $items[] = $item;
                    }
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.view('feeds.google-merchant', [
            'items' => $items,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
