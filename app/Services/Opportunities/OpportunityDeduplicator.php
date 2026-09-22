<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Sources\NormalizedOpportunity;
use Illuminate\Support\Str;

class OpportunityDeduplicator
{
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

        return $host.$path;
    }
}
