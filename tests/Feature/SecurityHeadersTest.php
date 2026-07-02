<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function responses_carry_a_content_security_policy(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('Content-Security-Policy-Report-Only');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('https://js.stripe.com', $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }
}
