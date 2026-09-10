<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Smoke tests for the critical revenue chain:
 *   public /_health           — uptime monitor target
 *   GET /login                — login page renders
 *   GET /                     — redirects to /dashboard
 *   GET /dashboard            — auth-protected (302 to login when anon)
 *   GET /cities/lookup        — JSON API surface
 *
 * Run with: `vendor/bin/phpunit --testsuite App --filter Smoke`
 *
 * These deliberately don't depend on a seeded DB or fixtures — they're
 * "does the framework still wire up correctly?" checks. Catch 80% of the
 * "I broke routing / config / dependency" regressions for ~50 lines of code.
 */
final class SmokeChainTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthEndpointReturnsJson(): void
    {
        $r = $this->get('_health');
        // 200 or 503 both acceptable — the test is that the endpoint responds + emits JSON
        $code = $r->response()->getStatusCode();
        $this->assertTrue(in_array($code, [200, 503], true), "Expected 200 or 503, got $code");
        $body = (string) $r->response()->getBody();
        $this->assertJson($body);
        $data = json_decode($body, true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('db', $data['checks']);
    }

    public function testLoginPageRenders(): void
    {
        $r = $this->get('login');
        $r->assertStatus(200);
        $r->assertSeeElement('input[name=email]');
        $r->assertSeeElement('input[name=password]');
        $r->assertSee('Sign in');
    }

    public function testRootRedirectsToDashboard(): void
    {
        $r = $this->get('/');
        $r->assertRedirect();
        $this->assertStringContainsString('dashboard', (string) $r->response()->getHeaderLine('Location'));
    }

    public function testDashboardRequiresAuth(): void
    {
        $r = $this->get('dashboard');
        $r->assertRedirect();
        $loc = (string) $r->response()->getHeaderLine('Location');
        $this->assertStringContainsString('login', $loc);
    }

    public function testCitiesLookupRequiresAuth(): void
    {
        $r = $this->get('cities/lookup?q=mum');
        $r->assertRedirect();
    }

    public function testStaticPublicEndpointsLoad(): void
    {
        // Driver track / e-POD / public quote forms should at minimum not 500
        // on a bad token — they should render a polite error page or redirect.
        foreach (['d/invalid-token', 'epod/invalid-token', 'quote/invalid-token'] as $path) {
            $r = $this->get($path);
            $code = $r->response()->getStatusCode();
            $this->assertNotEquals(500, $code, "Public path /$path returned 500 — check for unguarded null access");
        }
    }
}
