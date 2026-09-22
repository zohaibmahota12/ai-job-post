<?php

namespace App\Sources\Http;

interface DnsLookup
{
    /**
     * Resolve a hostname to IP addresses.
     *
     * @return list<string>
     */
    public function resolve(string $host): array;
}
