<?php

namespace App\Sources;

use App\Models\Source;
use App\Sources\Http\SafeHttpFetcher;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches and parses public RSS 2.0 and Atom feeds.
 *
 * Expected source config:
 * - url (required): public feed URL
 * - rate_limit_hint (optional): human-readable expectation for operators
 */
class RssAtomSourceAdapter implements SourceAdapter
{
    public function __construct(private SafeHttpFetcher $http) {}

    public function driver(): string
    {
        return 'rss';
    }

    public function type(): string
    {
        return 'rss';
    }

    public function collect(Source $source): CollectionResult
    {
        $url = $source->config['url'] ?? null;

        if (! is_string($url) || trim($url) === '') {
            throw new SourceCollectionException(
                'RSS source ['.$source->key.'] requires config.url to be a public feed URL.',
            );
        }

        $fetched = $this->http->get($url);

        return new CollectionResult($this->parse($fetched['body'], $source));
    }

    /**
     * @return list<RawOpportunity>
     */
    public function parse(string $xml, Source $source): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            if (! $loaded) {
                throw new SourceCollectionException('Feed XML could not be parsed.');
            }

            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
            $xpath->registerNamespace('content', 'http://purl.org/rss/1.0/modules/content/');

            $items = $xpath->query('//item');
            $entries = $xpath->query('//atom:entry');

            $nodes = [];

            if ($items !== false) {
                foreach ($items as $item) {
                    $nodes[] = $item;
                }
            }

            if ($entries !== false) {
                foreach ($entries as $entry) {
                    $nodes[] = $entry;
                }
            }

            $opportunities = [];

            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                try {
                    $raw = $this->mapNode($node, $xpath, $source);

                    if ($raw !== null) {
                        $opportunities[] = $raw;
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            return $opportunities;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function mapNode(DOMElement $node, DOMXPath $xpath, Source $source): ?RawOpportunity
    {
        $isAtom = $node->namespaceURI === 'http://www.w3.org/2005/Atom' || $node->localName === 'entry';

        if ($isAtom) {
            $title = $this->firstText($xpath, $node, './atom:title');
            $description = $this->firstText($xpath, $node, './atom:summary')
                ?? $this->firstText($xpath, $node, './atom:content');
            $link = $this->atomLink($xpath, $node);
            $externalId = $this->firstText($xpath, $node, './atom:id');
            $postedAt = $this->firstText($xpath, $node, './atom:published')
                ?? $this->firstText($xpath, $node, './atom:updated');
            $company = $this->firstText($xpath, $node, './atom:author/atom:name')
                ?? $source->name;
        } else {
            $title = $this->firstText($xpath, $node, './title');
            $description = $this->firstText($xpath, $node, './description')
                ?? $this->firstText($xpath, $node, './content:encoded');
            $link = $this->firstText($xpath, $node, './link');
            $externalId = $this->firstText($xpath, $node, './guid') ?? $link;
            $postedAt = $this->firstText($xpath, $node, './pubDate');
            $company = $this->firstText($xpath, $node, './author')
                ?? $this->firstText($xpath, $node, './source')
                ?? $source->name;
        }

        $title = $this->plainText($title);

        if ($title === null) {
            return null;
        }

        $description = $this->plainText($description);
        $link = is_string($link) ? trim($link) : null;
        $externalId = is_string($externalId) ? trim($externalId) : null;

        [$workplace, $location, $jobType] = $this->inferWorkplaceFields($title.' '.($description ?? ''));

        return new RawOpportunity(
            title: $title,
            description: $description,
            company: $this->plainText($company),
            sourceUrl: $link,
            externalId: $externalId !== null && $externalId !== '' ? $externalId : null,
            location: $location,
            jobType: $jobType,
            workplace: $workplace,
            postedAt: is_string($postedAt) ? trim($postedAt) : null,
            raw: [
                'format' => $isAtom ? 'atom' : 'rss',
                'source_key' => $source->key,
            ],
        );
    }

    private function atomLink(DOMXPath $xpath, DOMElement $node): ?string
    {
        $links = $xpath->query('./atom:link', $node);

        if ($links === false) {
            return null;
        }

        $fallback = null;

        foreach ($links as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $href = $link->getAttribute('href');

            if ($href === '') {
                continue;
            }

            $rel = $link->getAttribute('rel');

            if ($rel === '' || $rel === 'alternate') {
                return $href;
            }

            $fallback ??= $href;
        }

        return $fallback;
    }

    private function firstText(DOMXPath $xpath, DOMNode $context, string $expression): ?string
    {
        $nodes = $xpath->query($expression, $context);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = $nodes->item(0)?->textContent;

        return is_string($value) ? $value : null;
    }

    private function plainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function inferWorkplaceFields(string $haystack): array
    {
        $lower = Str::lower($haystack);
        $workplace = null;
        $location = null;
        $jobType = null;

        if (str_contains($lower, 'remote')) {
            $workplace = 'remote';
            $location = 'Remote';
        } elseif (str_contains($lower, 'hybrid')) {
            $workplace = 'hybrid';
        } elseif (str_contains($lower, 'on-site') || str_contains($lower, 'onsite') || str_contains($lower, 'on site')) {
            $workplace = 'onsite';
        }

        if (str_contains($lower, 'full-time') || str_contains($lower, 'full time')) {
            $jobType = 'full_time';
        } elseif (str_contains($lower, 'part-time') || str_contains($lower, 'part time')) {
            $jobType = 'part_time';
        } elseif (str_contains($lower, 'contract')) {
            $jobType = 'contract';
        } elseif (str_contains($lower, 'freelance') || str_contains($lower, 'gig')) {
            $jobType = 'freelance';
        }

        return [$workplace, $location, $jobType];
    }
}
