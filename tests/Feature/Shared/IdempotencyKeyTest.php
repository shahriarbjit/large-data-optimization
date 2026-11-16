<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Shared\Http\Middleware\IdempotencyKeyStub;

beforeEach(function (): void {
    Route::post('/api/v1/_test/idempotent', fn (Request $request) => response()->json([
        'data' => ['idempotency_key' => IdempotencyKeyStub::keyFor($request)],
    ]))->middleware('idempotency');
});

it('accepts a request without an Idempotency-Key (optional for now)', function (): void {
    $this->postJson('/api/v1/_test/idempotent')
        ->assertOk()
        ->assertJsonPath('data.idempotency_key', null);
});

it('attaches a valid Idempotency-Key to the request', function (): void {
    $key = (string) Str::uuid();

    $this->postJson('/api/v1/_test/idempotent', [], ['Idempotency-Key' => $key])
        ->assertOk()
        ->assertJsonPath('data.idempotency_key', $key);
});

it('rejects an Idempotency-Key with an invalid format with 422', function (): void {
    $this->postJson('/api/v1/_test/idempotent', [], ['Idempotency-Key' => 'not valid!'])
        ->assertUnprocessable()
        ->assertExactJsonStructure([...ERROR_KEYS, 'errors' => ['idempotency_key']])
        ->assertJsonPath('code', 'validation_failed');
});
