<?php

namespace App\Sources;

final class RawOpportunity
{
    /**
     * @param  list<string>  $skills
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $company = null,
        public ?string $sourceUrl = null,
        public ?string $externalId = null,
        public ?string $location = null,
        public ?string $jobType = null,
        public ?string $workplace = null,
        public ?string $budgetMin = null,
        public ?string $budgetMax = null,
        public ?string $currency = null,
        public ?string $postedAt = null,
        public ?string $deadlineAt = null,
        public ?int $requiredExperienceYears = null,
        public ?string $listingStatus = null,
        public array $skills = [],
        public array $raw = [],
    ) {}
}
