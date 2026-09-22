<?php

namespace App\Models;

use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'driver',
        'type',
        'name',
        'description',
        'is_enabled',
        'config',
        'last_run_at',
        'last_success_at',
        'last_error',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        // Adapter config may contain operator-supplied endpoints; never expose secrets if added later.
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'array',
            'last_run_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    /**
     * Public-safe config view for admin UI (never includes credential keys).
     *
     * @return array<string, mixed>
     */
    public function safeConfig(): array
    {
        $config = $this->config ?? [];
        $blocked = ['api_key', 'token', 'secret', 'password', 'authorization', 'auth'];

        return collect($config)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $blocked, true))
            ->all();
    }

    /**
     * @return HasMany<SourceRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(SourceRun::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
