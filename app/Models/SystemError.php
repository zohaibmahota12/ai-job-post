<?php

namespace App\Models;

use Database\Factories\SystemErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemError extends Model
{
    /** @use HasFactory<SystemErrorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'level',
        'message',
        'context',
        'source_run_id',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SourceRun, $this>
     */
    public function sourceRun(): BelongsTo
    {
        return $this->belongsTo(SourceRun::class);
    }
}
