<?php

namespace App\Sources\Http;

use App\Sources\SourceCollectionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SafeHttpFetcher
{
    public function __construct(private DnsLookup $dns) {}

    /**
     * @return array{body: string, final_url: string, status: int}
     *
     * @throws SourceCollectionException
     */
    public function get(string $url): array
    {
        $this->assertSafeUrl($url);

        $maxRedirects = max(0, (int) config('opportunity.http.max_redirects', 3));
        $maxBytes = max(1, (int) config('opportunity.http.max_bytes', 2_000_000));
        $connectTimeout = (float) config('opportunity.http.connect_timeout', 5);
        $timeout = (float) config('opportunity.http.timeout', 15);
        $userAgent = (string) config('opportunity.http.user_agent');

        $current = $url;
        $redirects = 0;

        while (true) {
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/rss+xml, application/atom+xml, application/json, application/xml, text/xml, text/plain;q=0.9, */*;q=0.8',
                    'User-Agent' => $userAgent,
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'http_errors' => false,
                    ])
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->get($current);
            } catch (ConnectionException $exception) {
                throw new SourceCollectionException(
                    'HTTP request failed: '.$exception->getMessage(),
                    previous: $exception,
                );
            }

            if ($this->isRedirect($response)) {
                $location = $response->header('Location');

                if (! is_string($location) || trim($location) === '') {
                    throw new SourceCollectionException('Redirect response was missing a Location header.');
                }

                $next = $this->resolveRedirect($current, $location);
                $this->assertSafeUrl($next);

                if ($redirects >= $maxRedirects) {
                    throw new SourceCollectionException('Too many redirects while fetching the source URL.');
                }

                $redirects++;
                $current = $next;

                continue;
            }

            if ($response->failed()) {
                throw new SourceCollectionException(
                    'HTTP '.$response->status().' while fetching the source URL.',
                );
            }

            $body = $response->body();

            if (strlen($body) > $maxBytes) {
                throw new SourceCollectionException(
                    'Source response exceeded the configured size limit of '.$maxBytes.' bytes.',
                );
            }

            return [
                'body' => $body,
                'final_url' => $current,
                'status' => $response->status(),
            ];
        }
    }

    /**
     * @throws SourceCollectionException
     */
    public function assertSafeUrl(string $url): void
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            throw new SourceCollectionException('Source URL is required.');
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            throw new SourceCollectionException('Source URL is not a valid URL.');
        }

        $parts = parse_url($trimmed);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new SourceCollectionException('Source URL must include a scheme and host.');
        }

        $scheme = Str::lower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new SourceCollectionException('Source URL must use http or https.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new SourceCollectionException('Source URL must not include credentials.');
        }

        $host = Str::lower($parts['host']);

        if ($host === 'localhost' || Str::endsWith($host, '.localhost') || Str::endsWith($host, '.local')) {
            throw new SourceCollectionException('Source URL must not target localhost or local hostnames.');
        }

        if ($this->isBlockedIpLiteral($host) || $this->resolvesToBlockedAddress($host)) {
            throw new SourceCollectionException('Source URL must not target private or internal network addresses.');
        }
    }

    private function isRedirect(Response $response): bool
    {
        return in_array($response->status(), [301, 302, 303, 307, 308], true);
    }

    private function resolveRedirect(string $current, string $location): string
    {
        if (filter_var($location, FILTER_VALIDATE_URL) !== false) {
            return $location;
        }

        $base = parse_url($current);

        if ($base === false || ! isset($base['scheme'], $base['host'])) {
            throw new SourceCollectionException('Unable to resolve redirect Location against the current URL.');
        }

        if (str_starts_with($location, '//')) {
            return $base['scheme'].':'.$location;
        }

        if (str_starts_with($location, '/')) {
            $port = isset($base['port']) ? ':'.$base['port'] : '';

            return $base['scheme'].'://'.$base['host'].$port.$location;
        }

        $path = $base['path'] ?? '/';
        $directory = Str::beforeLast($path, '/');
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        return $base['scheme'].'://'.$base['host'].$port.$directory.'/'.$location;
    }

    private function isBlockedIpLiteral(string $host): bool
    {
        $candidate = $host;

        if (str_starts_with($candidate, '[') && str_ends_with($candidate, ']')) {
            $candidate = substr($candidate, 1, -1);
        }

        if (filter_var($candidate, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return $this->isBlockedIp($candidate);
    }

    private function resolvesToBlockedAddress(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        $ips = $this->dns->resolve($host);

        if ($ips === []) {
            throw new SourceCollectionException('Source host could not be resolved.');
        }

        foreach ($ips as $ip) {
            if ($this->isBlockedIp($ip)) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);

            if ($long === false) {
                return true;
            }

            $ranges = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['100.64.0.0', '100.127.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.0.0.0', '192.0.0.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['198.18.0.0', '198.19.255.255'],
                ['224.0.0.0', '255.255.255.255'],
            ];

            foreach ($ranges as [$start, $end]) {
                if ($long >= ip2long($start) && $long <= ip2long($end)) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $normalized = Str::lower($ip);

            return $normalized === '::1'
                || str_starts_with($normalized, 'fc')
                || str_starts_with($normalized, 'fd')
                || str_starts_with($normalized, 'fe8')
                || str_starts_with($normalized, 'fe9')
                || str_starts_with($normalized, 'fea')
                || str_starts_with($normalized, 'feb');
        }

        return true;
    }
}
