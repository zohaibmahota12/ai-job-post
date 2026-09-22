<?php

namespace App\Services\Ai;

use App\Services\Ai\Dto\GeneratedProposalContent;
use App\Services\Ai\Exceptions\AiResponseException;

class GeneratedProposalParser
{
    public function parse(string $raw): GeneratedProposalContent
    {
        $decoded = $this->decodeJson($raw);

        if ($decoded === null) {
            $plain = trim($raw);

            if ($plain === '') {
                throw new AiResponseException('AI response was empty.');
            }

            return new GeneratedProposalContent(proposal: $this->sanitizeText($plain, 20000));
        }

        $proposal = $decoded['proposal'] ?? $decoded['content'] ?? $decoded['body'] ?? null;

        if (! is_string($proposal) || trim($proposal) === '') {
            throw new AiResponseException('AI response did not include proposal text.');
        }

        $subject = $decoded['subject'] ?? null;
        $subject = is_string($subject) ? $this->sanitizeText($subject, 255) : null;

        $keyPoints = [];

        if (isset($decoded['key_points']) && is_array($decoded['key_points'])) {
            foreach ($decoded['key_points'] as $point) {
                if (! is_string($point)) {
                    continue;
                }

                $clean = $this->sanitizeText($point, 500);

                if ($clean !== '') {
                    $keyPoints[] = $clean;
                }

                if (count($keyPoints) >= 8) {
                    break;
                }
            }
        }

        return new GeneratedProposalContent(
            proposal: $this->sanitizeText($proposal, 20000),
            subject: $subject !== '' ? $subject : null,
            keyPoints: $keyPoints,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $raw): ?array
    {
        $candidate = trim($raw);

        if ($candidate === '') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $candidate, $matches) === 1) {
            $candidate = $matches[1];
        }

        $start = strpos($candidate, '{');
        $end = strrpos($candidate, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $candidate = substr($candidate, $start, $end - $start + 1);
        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function sanitizeText(string $value, int $max): string
    {
        $value = str_replace("\0", '', $value);
        $value = strip_tags($value);
        $value = trim($value);

        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }

        return $value;
    }
}
