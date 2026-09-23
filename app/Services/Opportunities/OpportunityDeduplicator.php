<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Sources\NormalizedOpportunity;
use Illuminate\Support\Str;

class OpportunityDeduplicator
{
    /**
     * Query parameters that are safe to drop for canonical identity.
     *
     * @var list<string>
     */
    private const TRACKING_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_id',
        'utm_reader',
        'fbclid',
        'gclid',
        'gbraid',
        'wbraid',
        'mc_cid',
        'mc_eid',
        'msclkid',
        '_ga',
        '_gl',
        'ref',
        'ref_src',
        'source',
        'campaign',
        'igshid',
        'si',
        'utm',
    ];

    public function findExisting(NormalizedOpportunity $opportunity): ?Opportunity
    {
        if ($opportunity->externalId !== null) {
            $byExternalId = Opportunity::query()
                ->where('source_id', $opportunity->sourceId)
                ->where('external_id', $opportunity->externalId)
                ->first();

            if ($byExternalId !== null) {
                return $byExternalId;
            }
        }

        $canonical = $this->canonicalUrl($opportunity->sourceUrl);

        if ($canonical !== '') {
            $byCanonicalUrl = Opportunity::query()
                ->where('canonical_url', $canonical)
                ->first();

            if ($byCanonicalUrl !== null) {
                return $byCanonicalUrl;
            }
        }

        return Opportunity::query()
            ->where('content_hash', $this->fingerprint($opportunity))
            ->first();
    }

    public function fingerprint(NormalizedOpportunity $opportunity): string
    {
        $payload = implode('|', [
            Str::lower(trim($opportunity->title)),
            Str::lower(trim((string) $opportunity->company)),
            $this->canonicalUrl($opportunity->sourceUrl),
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Build a stable, usable canonical URL identity.
     *
     * Tracking parameters are removed. Meaningful query parameters are kept
     * (sorted) so listings that rely on query identity remain distinct.
     */
    public function canonicalUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return Str::lower(trim($url));
        }

        $host = Str::lower($parts['host']);

        if (str_starts_with($host, 'www.')) {
            $host = Str::after($host, 'www.');
        }

        $path = rtrim($parts['path'] ?? '', '/');
        $query = $this->canonicalQuery($parts['query'] ?? null);

        return $host.$path.($query !== '' ? '?'.$query : '');
    }

    private function canonicalQuery(?string $query): string
    {
        if ($query === null || trim($query) === '') {
            return '';
        }

        parse_str($query, $params);

        if ($params === []) {
            return '';
        }

        $kept = [];

        foreach ($params as $key => $value) {
            $name = Str::lower((string) $key);

            if (in_array($name, self::TRACKING_PARAMS, true) || str_starts_with($name, 'utm_')) {
                continue;
            }

            if (is_array($value)) {
                $kept[$name] = $value;

                continue;
            }

            $kept[$name] = (string) $value;
        }

        if ($kept === []) {
            return '';
        }

        ksort($kept);

        return http_build_query($kept);
    }
}
