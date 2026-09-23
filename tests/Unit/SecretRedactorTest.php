<?php

namespace Tests\Unit;

use App\Support\SecretRedactor;
use Tests\TestCase;

class SecretRedactorTest extends TestCase
{
    public function test_redacts_authorization_api_keys_and_credential_urls(): void
    {
        $redactor = app(SecretRedactor::class);

        $message = 'Authorization: Bearer secret-token api_key=abc123 https://user:pass@example.com/path?token=xyz&id=1';

        $redacted = $redactor->redact($message);

        $this->assertStringNotContainsString('secret-token', (string) $redacted);
        $this->assertStringNotContainsString('abc123', (string) $redacted);
        $this->assertStringNotContainsString('user:pass', (string) $redacted);
        $this->assertStringNotContainsString('xyz', (string) $redacted);
        $this->assertStringContainsString('[REDACTED]', (string) $redacted);
        $this->assertStringContainsString('id=1', (string) $redacted);
    }
}
