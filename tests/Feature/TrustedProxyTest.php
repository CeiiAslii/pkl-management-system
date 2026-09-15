<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Vite::useHotFile(Storage::disk('local')->path('vite.hot'));
    }

    public function test_local_reverse_proxy_headers_generate_https_routes_and_vite_assets(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'Host' => 'school-tunnel.example',
                'X-Forwarded-For' => '203.0.113.10',
                'X-Forwarded-Host' => 'school-tunnel.example',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/login');

        $response->assertOk()
            ->assertSee('action="https://school-tunnel.example/login"', false)
            ->assertSee('https://school-tunnel.example/build/assets/', false)
            ->assertDontSee('http://school-tunnel.example/build/assets/', false);
    }

    public function test_forwarded_headers_from_an_untrusted_client_are_ignored(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders([
                'Host' => 'application.example',
                'X-Forwarded-For' => '198.51.100.20',
                'X-Forwarded-Host' => 'spoofed.example',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/login');

        $response->assertOk()
            ->assertSee('action="http://localhost:8000/login"', false)
            ->assertSee('http://localhost:8000/build/assets/', false)
            ->assertDontSee('spoofed.example', false);
    }
}
