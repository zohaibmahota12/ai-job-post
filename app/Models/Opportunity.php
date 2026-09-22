<?php

namespace App\Models;

use App\JobType;
use App\OpportunityStatus;
use App\Workplace;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'title',
        'description',
        'company',
        'source_url',
        'external_id',
        'location',
        'job_type',
        'workplace',
        'budget_min',
        'budget_max',
        'currency',
        'posted_at',
        'deadline_at',
        'required_experience_years',
        'raw_data',
        'normalized_data',
        'status',
        'content_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_type' => JobType::class,
            'workplace' => Workplace::class,
            'status' => OpportunityStatus::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'posted_at' => 'datetime',
            'deadline_at' => 'datetime',
            'raw_data' => 'array',
            'normalized_data' => 'array',
            'required_experience_years' => 'integer',
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
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'opportunity_skills')->withTimestamps();
    }

    /**
     * @return HasMany<OpportunityMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(OpportunityMatch::class);
    }

    /**
     * @return HasMany<SavedOpportunity, $this>
     */
    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedOpportunity::class);
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function safeSourceUrl(): ?string
    {
        $url = $this->source_url;

        if (! is_string($url)) {
            return null;
        }

        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
            return null;
        }

        return $url;
    }
}
