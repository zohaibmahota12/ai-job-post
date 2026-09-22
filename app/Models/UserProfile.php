<?php

namespace App\Models;

use App\JobType;
use App\RemotePreference;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'experience',
        'years_of_experience',
        'location',
        'preferred_job_type',
        'remote_preference',
        'minimum_budget',
        'preferred_currency',
        'keywords',
        'excluded_keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'preferred_job_type' => JobType::class,
            'remote_preference' => RemotePreference::class,
            'minimum_budget' => 'decimal:2',
            'keywords' => 'array',
            'excluded_keywords' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
