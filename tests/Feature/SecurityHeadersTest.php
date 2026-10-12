<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_http_responses_include_security_headers_without_hsts(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_https_responses_include_hsts(): void
    {
        $response = $this->get('https://localhost/');

        $response->assertOk();
        $response->assertHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );
    }

    public function test_api_json_responses_also_receive_security_headers(): void
    {
        $response = $this->postJson('/api/shared/login', [
            'email' => 'nobody@example.com',
            'password' => 'invalid',
        ]);

        // Auth failure is fine; headers must still be present and body unchanged in shape.
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }
}
