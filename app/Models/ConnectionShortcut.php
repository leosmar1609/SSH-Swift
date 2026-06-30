<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionShortcut extends Model
{
    protected $fillable = ['connection_id', 'name', 'path', 'icon', 'sort_order'];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
