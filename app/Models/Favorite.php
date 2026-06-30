<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = ['connection_id', 'path', 'label'];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: basename($this->path);
    }
}
