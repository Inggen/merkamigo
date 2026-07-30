<?php

namespace App\Domain\Needs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NeedMedia extends Model
{
    protected $fillable = ['need_id', 'path', 'position'];

    /**
     * @return BelongsTo<Need, $this>
     */
    public function need(): BelongsTo
    {
        return $this->belongsTo(Need::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
