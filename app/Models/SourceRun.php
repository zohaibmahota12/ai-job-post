<?php

namespace App\Models;

use App\SourceRunStatus;
use Database\Factories\SourceRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceRun extends Model
{
    /** @use HasFactory<SourceRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'status',
        'started_at',
        'finished_at',
        'items_found',
        'items_created',
        'items_skipped',
        'error_message',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SourceRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return HasMany<SystemError, $this>
     */
    public function systemErrors(): HasMany
    {
        return $this->hasMany(SystemError::class);
    }
}
