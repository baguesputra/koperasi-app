<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_all_crawlers(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /', $content);
    }

    public function test_web_routes_send_defensive_security_and_anti_indexing_headers(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_html_includes_anti_indexing_meta_tags(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">', false);
        $response->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">', false);
    }

    public function test_api_documentation_requires_authentication(): void
    {
        $guestResponse = $this->get('/api/documentation');
        $guestResponse->assertStatus(401);

        $user = User::factory()->create();
        $authResponse = $this->actingAs($user)->get('/api/documentation');
        $authResponse->assertStatus(200);
        $authResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $authResponse->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">', false);
    }
}
