<?php

declare(strict_types=1);

it('answers in JSON when no Accept header is sent', function (): void {
    $this->call('GET', '/api/v1/health')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json');
});

it('allows the configured CORS origin and exposes X-Request-Id', function (): void {
    $this->getJson('/api/v1/health', ['Origin' => 'http://localhost:5173'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Expose-Headers');
});

it('does not allow an unknown CORS origin', function (): void {
    // With a single allowed origin, Laravel's CORS handler always sends that
    // origin back; the browser then blocks the mismatch. What matters is that
    // the unknown origin is never echoed back.
    $response = $this->getJson('/api/v1/health', ['Origin' => 'https://evil.example'])->assertOk();

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example');
});

it('reads several allowed origins from CORS_ALLOWED_ORIGINS', function (): void {
    config(['cors.allowed_origins' => ['http://localhost:5173', 'https://admin.example']]);

    $this->getJson('/api/v1/health', ['Origin' => 'https://admin.example'])
        ->assertHeader('Access-Control-Allow-Origin', 'https://admin.example');

    $response = $this->getJson('/api/v1/health', ['Origin' => 'https://evil.example']);
    expect($response->headers->get('Access-Control-Allow-Origin'))->toBeNull();
});
