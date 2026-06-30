<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'ssh_key_path',
        'startup_script',
    ];

    protected $casts = [
        'port' => 'integer',
    ];

    public function getStartupScriptLinesAttribute(): array
    {
        if (empty($this->startup_script)) {
            return [];
        }

        return array_filter(
            explode("\n", str_replace("\r\n", "\n", $this->startup_script)),
            fn(string $line) => trim($line) !== ''
        );
    }

    public function getShortHostAttribute(): string
    {
        return strlen($this->host) > 30
            ? substr($this->host, 0, 27) . '...'
            : $this->host;
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)->orderBy('label');
    }

    public function shortcuts(): HasMany
    {
        return $this->hasMany(ConnectionShortcut::class)->orderBy('sort_order')->orderBy('name');
    }
}
