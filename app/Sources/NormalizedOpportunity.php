<?php

namespace App\Sources;

use App\JobType;
use App\OpportunityStatus;
use App\Workplace;

final class NormalizedOpportunity
{
    /**
     * @param  list<string>  $skillNames
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $sourceId,
        public string $title,
        public ?string $description,
        public ?string $company,
        public ?string $sourceUrl,
        public string $canonicalUrl,
        public ?string $externalId,
        public ?string $location,
        public ?JobType $jobType,
        public ?Workplace $workplace,
        public ?string $budgetMin,
        public ?string $budgetMax,
        public ?string $currency,
        public ?string $postedAt,
        public ?string $deadlineAt,
        public ?int $requiredExperienceYears,
        public OpportunityStatus $status,
        public array $skillNames,
        public array $raw,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_id' => $this->sourceId,
            'title' => $this->title,
            'description' => $this->description,
            'company' => $this->company,
            'source_url' => $this->sourceUrl,
            'canonical_url' => $this->canonicalUrl !== '' ? $this->canonicalUrl : null,
            'external_id' => $this->externalId,
            'location' => $this->location,
            'job_type' => $this->jobType?->value,
            'workplace' => $this->workplace?->value,
            'budget_min' => $this->budgetMin,
            'budget_max' => $this->budgetMax,
            'currency' => $this->currency,
            'posted_at' => $this->postedAt,
            'deadline_at' => $this->deadlineAt,
            'required_experience_years' => $this->requiredExperienceYears,
            'status' => $this->status->value,
            'skills' => $this->skillNames,
        ];
    }
}
