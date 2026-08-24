<?php

namespace App\Domain\Businesses\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAttribute extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
