<?php

declare(strict_types=1);

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

it('adds X-Request-Id to every response', function (string $method, string $uri, int $status): void {
    $response = $this->json($method, $uri)->assertStatus($status);

    expect(Str::isUuid($response->headers->get('X-Request-Id')))->toBeTrue();
})->with([
    'success' => ['GET', '/api/v1/health', 200],
    'framework health' => ['GET', '/up', 200],
    '404' => ['GET', '/api/v1/nope', 404],
    '405' => ['DELETE', '/api/v1/health', 405],
]);

it('echoes a valid client-supplied UUID', function (): void {
    $id = (string) Str::uuid();

    $this->getJson('/api/v1/health', ['X-Request-Id' => $id])->assertHeader('X-Request-Id', $id);
});

it('echoes a valid client-supplied ULID', function (): void {
    $id = (string) Str::ulid();

    $this->getJson('/api/v1/health', ['X-Request-Id' => $id])->assertHeader('X-Request-Id', $id);
});

it('replaces an invalid client-supplied request id', function (string $invalid): void {
    $response = $this->getJson('/api/v1/health', ['X-Request-Id' => $invalid]);

    $id = $response->headers->get('X-Request-Id');
    expect($id)->not->toBe($invalid)
        ->and(Str::isUuid($id))->toBeTrue();
})->with(['not-a-uuid', 'DROP TABLE users', str_repeat('a', 300)]);

it('puts the same request id in the error body and the header', function (): void {
    $id = (string) Str::uuid();

    $this->getJson('/api/v1/nope', ['X-Request-Id' => $id])
        ->assertHeader('X-Request-Id', $id)
        ->assertJsonPath('request_id', $id);
});

it('adds the request id to every log line', function (): void {
    Route::get('/api/v1/_test/logs', function () {
        Log::info('hello from a test route');

        return response()->json(['data' => null]);
    });

    $logged = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
        $logged[] = $event;
    });

    $id = (string) Str::uuid();
    $this->getJson('/api/v1/_test/logs', ['X-Request-Id' => $id])->assertOk();

    expect($logged)->toHaveCount(1)
        ->and($logged[0]->context)->toMatchArray(['request_id' => $id]);
});
