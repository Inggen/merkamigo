<?php

namespace App\Domain\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostMedia extends Model
{
    protected $fillable = ['post_id', 'path', 'position', 'alt_text'];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Reels (Fase 4 del TODO social): no hay columna de tipo en
     * `post_media` a propósito — la extensión del archivo ya basta para
     * decidir si se renderiza como `<video>` o `<img>`.
     */
    public function isVideo(): bool
    {
        return in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov'], true);
    }
}
