<?php

namespace App\Sources;

use App\Models\Source;
use App\Sources\Http\SafeHttpFetcher;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Generic public JSON job API adapter.
 *
 * This is a configuration-driven template. It does not claim support for a
 * specific commercial provider. Operators must supply a permitted public URL
 * and a field map that matches that API's response shape.
 *
 * Expected source config:
 * - url (required): public JSON endpoint
 * - items_path (optional): dot path to the list of jobs (default: empty = root array)
 * - field_map (optional): map of RawOpportunity fields to item paths
 * - rate_limit_hint (optional): operator-facing rate expectation
 *
 * Secrets must not be stored in config. Prefer credential-free public feeds.
 */
class JsonApiSourceAdapter implements SourceAdapter
{
    public function __construct(private SafeHttpFetcher $http) {}

    public function driver(): string
    {
        return 'json_api';
    }

    public function type(): string
    {
        return 'json_api';
    }

    public function collect(Source $source): CollectionResult
    {
        $url = $source->config['url'] ?? null;

        if (! is_string($url) || trim($url) === '') {
            throw new SourceCollectionException(
                'JSON API source ['.$source->key.'] requires config.url to be a public JSON endpoint.',
            );
        }

        $fetched = $this->http->get($url);
        $decoded = json_decode($fetched['body'], true);

        if (! is_array($decoded)) {
            throw new SourceCollectionException('JSON API response was not valid JSON.');
        }

        return new CollectionResult($this->mapPayload($decoded, $source));
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<RawOpportunity>
     */
    public function mapPayload(array $payload, Source $source): array
    {
        $itemsPath = $source->config['items_path'] ?? null;
        $items = $itemsPath === null || $itemsPath === ''
            ? $payload
            : Arr::get($payload, (string) $itemsPath);

        if (! is_array($items)) {
            throw new SourceCollectionException('JSON API items_path did not resolve to a list.');
        }

        if ($items !== [] && ! array_is_list($items)) {
            throw new SourceCollectionException('JSON API items_path must resolve to a JSON array.');
        }

        /** @var array<string, string> $fieldMap */
        $fieldMap = is_array($source->config['field_map'] ?? null)
            ? $source->config['field_map']
            : $this->defaultFieldMap();

        $opportunities = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            try {
                $raw = $this->mapItem($item, $fieldMap, $source);

                if ($raw !== null) {
                    $opportunities[] = $raw;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $opportunities;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, string>  $fieldMap
     */
    private function mapItem(array $item, array $fieldMap, Source $source): ?RawOpportunity
    {
        $title = $this->stringValue($item, $fieldMap['title'] ?? 'title');

        if ($title === null) {
            return null;
        }

        $skills = $this->stringList($item, $fieldMap['skills'] ?? 'skills');

        return new RawOpportunity(
            title: $title,
            description: $this->stringValue($item, $fieldMap['description'] ?? 'description'),
            company: $this->stringValue($item, $fieldMap['company'] ?? 'company') ?? $source->name,
            sourceUrl: $this->stringValue($item, $fieldMap['source_url'] ?? 'url'),
            externalId: $this->stringValue($item, $fieldMap['external_id'] ?? 'id'),
            location: $this->stringValue($item, $fieldMap['location'] ?? 'location'),
            jobType: $this->stringValue($item, $fieldMap['job_type'] ?? 'job_type'),
            workplace: $this->workplaceValue($item, $fieldMap),
            budgetMin: $this->numericString($item, $fieldMap['budget_min'] ?? 'budget_min'),
            budgetMax: $this->numericString($item, $fieldMap['budget_max'] ?? 'budget_max'),
            currency: $this->stringValue($item, $fieldMap['currency'] ?? 'currency'),
            postedAt: $this->stringValue($item, $fieldMap['posted_at'] ?? 'posted_at'),
            deadlineAt: $this->stringValue($item, $fieldMap['deadline_at'] ?? 'deadline_at'),
            requiredExperienceYears: $this->intValue($item, $fieldMap['required_experience_years'] ?? 'required_experience_years'),
            listingStatus: $this->stringValue($item, $fieldMap['listing_status'] ?? 'listing_status'),
            skills: $skills,
            raw: [
                'source_key' => $source->key,
                'item' => $item,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function defaultFieldMap(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'company' => 'company',
            'source_url' => 'url',
            'external_id' => 'id',
            'location' => 'location',
            'job_type' => 'job_type',
            'workplace' => 'workplace',
            'budget_min' => 'budget_min',
            'budget_max' => 'budget_max',
            'currency' => 'currency',
            'posted_at' => 'posted_at',
            'deadline_at' => 'deadline_at',
            'required_experience_years' => 'required_experience_years',
            'listing_status' => 'listing_status',
            'skills' => 'skills',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, string>  $fieldMap
     */
    private function workplaceValue(array $item, array $fieldMap): ?string
    {
        $workplace = $this->stringValue($item, $fieldMap['workplace'] ?? 'workplace');

        if ($workplace !== null) {
            return $workplace;
        }

        $remotePath = $fieldMap['remote'] ?? null;

        if (! is_string($remotePath) || $remotePath === '') {
            return null;
        }

        $remote = Arr::get($item, $remotePath);

        if ($remote === true || $remote === 1 || $remote === '1' || $remote === 'true') {
            return 'remote';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function stringValue(array $item, string $path): ?string
    {
        $value = Arr::get($item, $path);

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $text = trim($value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function numericString(array $item, string $path): ?string
    {
        $value = Arr::get($item, $path);

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value) || trim($value) === '' || ! is_numeric($value)) {
            return null;
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function intValue(array $item, string $path): ?int
    {
        $value = Arr::get($item, $path);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function stringList(array $item, string $path): array
    {
        $value = Arr::get($item, $path);

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        if (! is_array($value)) {
            return [];
        }

        $skills = [];

        foreach ($value as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $skills[] = trim($entry);
            }
        }

        return $skills;
    }
}
