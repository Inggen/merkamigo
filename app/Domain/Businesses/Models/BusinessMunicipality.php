<?php

namespace App\Domain\Businesses\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Fila de la tabla pivote `business_municipalities` (0.2.2 del TODO: una
 * vitrina puede estar en varios municipios, además del principal).
 *
 * @property string|null $zone
 */
class BusinessMunicipality extends Pivot
{
    protected $table = 'business_municipalities';
}
