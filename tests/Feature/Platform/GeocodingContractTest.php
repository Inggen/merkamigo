<?php

namespace Tests\Feature\Platform;

use App\Support\Geo\Contracts\GeocodesAddresses;
use App\Support\Geo\ManualGeocoder;
use Tests\TestCase;

/**
 * 5.4 del TODO: contrato de geocodificación, sin proveedor real todavía.
 */
class GeocodingContractTest extends TestCase
{
    public function test_the_contract_resolves_to_the_manual_geocoder_by_default(): void
    {
        $this->assertInstanceOf(ManualGeocoder::class, app(GeocodesAddresses::class));
    }

    public function test_the_manual_geocoder_never_resolves_an_address_automatically(): void
    {
        $this->assertNull(app(GeocodesAddresses::class)->geocode('Calle 10 # 5-20, Cajicá'));
    }
}
