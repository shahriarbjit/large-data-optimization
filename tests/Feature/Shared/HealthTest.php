<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Support\Clock;

it('returns 200 with the documented shape', function (): void {
    config(['app.name' => 'urban-nest-api', 'api.version' => '9.9.9']);

    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJsonStructure(['data' => ['status', 'app', 'version', 'time']])
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('data.app', 'urban-nest-api')
        ->assertJsonPath('data.version', '9.9.9');
});

it('returns the time in ISO 8601 UTC', function (): void {
    $this->app->instance(Clock::class, new class implements Clock
    {
        public function now(): CarbonImmutable
        {
            return CarbonImmutable::parse('2026-01-02 03:04:05', 'UTC');
        }
    });

    $this->getJson('/api/v1/health')->assertJsonPath('data.time', '2026-01-02T03:04:05Z');
});

it('does not touch the database', function (): void {
    DB::listen(fn () => throw new RuntimeException('The health check must not query the database.'));

    $this->getJson('/api/v1/health')->assertOk();
});
