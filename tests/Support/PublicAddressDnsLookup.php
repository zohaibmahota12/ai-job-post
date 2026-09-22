<?php

namespace Tests\Support;

use App\Sources\Http\DnsLookup;

/**
 * Deterministic DNS for offline HTTP fakes.
 *
 * Returns a documentation (TEST-NET-3) address that is not private/blocked,
 * so SafeHttpFetcher SSRF checks pass without real DNS or internet access.
 */
class PublicAddressDnsLookup implements DnsLookup
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        return ['203.0.113.10'];
    }
}
