<?php

namespace App\Services\Opportunities;

use App\JobType;
use App\Models\Source;
use App\OpportunityStatus;
use App\Sources\NormalizedOpportunity;
use App\Sources\RawOpportunity;
use App\Workplace;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class OpportunityNormalizer
{
    public function normalize(RawOpportunity $raw, Source $source): NormalizedOpportunity
    {
        $title = trim(preg_replace('/\s+/u', ' ', $raw->title) ?? '');

        if ($title === '') {
            throw new InvalidArgumentException('Opportunity title is required.');
        }

        [$budgetMin, $budgetMax] = $this->budgets($raw->budgetMin, $raw->budgetMax);

        return new NormalizedOpportunity(
            sourceId: $source->id,
            title: $title,
            description: $this->nullableText($raw->description),
            company: $this->nullableText($raw->company),
            sourceUrl: $this->url($raw->sourceUrl),
            externalId: $this->limited($raw->externalId, 191),
            location: $this->limited($raw->location, 255),
            jobType: $this->jobType($raw->jobType),
            workplace: $this->workplace($raw->workplace),
            budgetMin: $budgetMin,
            budgetMax: $budgetMax,
            currency: $this->currency($raw->currency),
            postedAt: $this->timestamp($raw->postedAt),
            deadlineAt: $this->timestamp($raw->deadlineAt),
            requiredExperienceYears: $this->years($raw->requiredExperienceYears),
            status: OpportunityStatus::Open,
            skillNames: $this->skills($raw->skills),
            raw: $raw->raw,
        );
    }

    private function nullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);

        return $text === '' ? null : $text;
    }

    private function limited(?string $value, int $max): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        return Str::limit($text, $max, '');
    }

    private function url(?string $value): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        if (! str_starts_with($text, 'https://') && ! str_starts_with($text, 'http://')) {
            return null;
        }

        if (filter_var($text, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return Str::limit($text, 2048, '');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function budgets(?string $min, ?string $max): array
    {
        $minimum = $this->money($min);
        $maximum = $this->money($max);

        if ($minimum !== null && $maximum !== null && bccomp($minimum, $maximum, 2) === 1) {
            return [$maximum, $minimum];
        }

        return [$minimum, $maximum];
    }

    private function money(?string $value): ?string
    {
        if ($value === null || trim($value) === '' || ! is_numeric($value)) {
            return null;
        }

        if ((float) $value < 0) {
            return null;
        }

        return bcadd($value, '0', 2);
    }

    private function currency(?string $value): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        $currency = Str::upper($text);

        return preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $currency : null;
    }

    private function timestamp(?string $value): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function years(?int $years): ?int
    {
        if ($years === null || $years < 0 || $years > 80) {
            return null;
        }

        return $years;
    }

    private function jobType(?string $value): ?JobType
    {
        $normalized = $this->token($value);

        return match ($normalized) {
            'freelance', 'gig' => JobType::Freelance,
            'contract', 'contractor' => JobType::Contract,
            'full_time', 'fulltime', 'permanent' => JobType::FullTime,
            'part_time', 'parttime' => JobType::PartTime,
            default => null,
        };
    }

    private function workplace(?string $value): ?Workplace
    {
        $normalized = $this->token($value);

        return match ($normalized) {
            'remote' => Workplace::Remote,
            'hybrid' => Workplace::Hybrid,
            'onsite', 'on_site', 'office' => Workplace::Onsite,
            default => $normalized === null ? null : Workplace::Unspecified,
        };
    }

    private function token(?string $value): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        return Str::of($text)->lower()->replace([' ', '-'], '_')->toString();
    }

    /**
     * @param  list<string>  $skills
     * @return list<string>
     */
    private function skills(array $skills): array
    {
        $names = [];

        foreach ($skills as $skill) {
            if (! is_string($skill)) {
                continue;
            }

            $name = trim(preg_replace('/\s+/u', ' ', $skill) ?? '');

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }
}
