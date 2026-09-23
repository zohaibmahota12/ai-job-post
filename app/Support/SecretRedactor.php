<?php

namespace App\Support;

class SecretRedactor
{
    /**
     * Redact credentials and secret-like values from diagnostic text.
     */
    public function redact(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $redacted = $message;

        $redacted = preg_replace(
            '#(https?://)([^/\s:@]+):([^/\s:@]+)@#i',
            '$1[REDACTED]:[REDACTED]@',
            $redacted,
        ) ?? $redacted;

        $patterns = [
            '/(?i)(authorization\s*[:=]\s*bearer\s+)([^\s]+)/' => '$1[REDACTED]',
            '/(?i)(authorization\s*[:=]\s*)([^\s]+)/' => '$1[REDACTED]',
            '/(?i)(bearer\s+)([a-z0-9\-._~+\/=]+)/' => '$1[REDACTED]',
            '/(?i)(api[_-]?key\s*[:=]\s*)([^\s&]+)/' => '$1[REDACTED]',
            '/(?i)(token\s*[:=]\s*)([^\s&]+)/' => '$1[REDACTED]',
            '/(?i)(password\s*[:=]\s*)([^\s&]+)/' => '$1[REDACTED]',
            '/(?i)(secret\s*[:=]\s*)([^\s&]+)/' => '$1[REDACTED]',
            '/(?i)([?&](?:api[_-]?key|token|secret|password|access_token|auth)=)([^&\s]+)/' => '$1[REDACTED]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $redacted = preg_replace($pattern, $replacement, $redacted) ?? $redacted;
        }

        return $redacted;
    }
}
