<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'host',
        'port',
        'username',
        'auth_type',
        'password',
        'ssh_key_path',
        'startup_script',
    ];

    protected $casts = [
        'port'           => 'integer',
        'password'       => 'encrypted',
        'startup_script' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $query) {
            if (auth()->check()) {
                $query->where('user_id', auth()->id());
            }
        });
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
