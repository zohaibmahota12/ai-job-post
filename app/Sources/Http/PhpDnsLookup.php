<?php

namespace App\Sources\Http;

class PhpDnsLookup implements DnsLookup
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);

        if (is_array($records) && $records !== []) {
            $ips = [];

            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (is_string($ip) && $ip !== '') {
                    $ips[] = $ip;
                }
            }

            return array_values(array_unique($ips));
        }

        $ipv4 = @gethostbyname($host);

        if (! is_string($ipv4) || $ipv4 === $host || filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return [];
        }

        return [$ipv4];
    }
}
