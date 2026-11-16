<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function validOrder(array $overrides = []): array
{
    return array_merge([
        'channel' => 'web',
        'location_id' => 1,
        'items' => [
            ['variant_id' => 10, 'qty' => 2],
            ['variant_id' => 11, 'qty' => 1],
        ],
        'notes' => 'Call before delivery',
    ], $overrides);
}

beforeEach(function (): void {
    // These endpoints must work with no database at all.
    DB::listen(fn () => throw new RuntimeException('Orders stubs must not query the database.'));
});

describe('GET /api/v1/orders', function (): void {
    it('returns 501 for valid query parameters', function (string $query): void {
        $this->getJson('/api/v1/orders'.$query)
            ->assertStatus(501)
            ->assertJsonPath('code', 'not_implemented');
    })->with([
        'no parameters' => [''],
        'page and per_page' => ['?page=2&per_page=100'],
        'sort' => ['?sort=-placed_at'],
        'filters' => ['?filter[status]=paid&filter[channel]=pos'],
    ]);

    it('rejects invalid query parameters with 422', function (string $query, string $field): void {
        $this->getJson('/api/v1/orders'.$query)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field]);
    })->with([
        'page zero' => ['?page=0', 'page'],
        'page not a number' => ['?page=abc', 'page'],
        'per_page above max (100)' => ['?per_page=101', 'per_page'],
        'per_page zero' => ['?per_page=0', 'per_page'],
        'unknown sort' => ['?sort=password', 'sort'],
        'unknown status' => ['?filter[status]=lost', 'filter.status'],
        'unknown channel' => ['?filter[channel]=fax', 'filter.channel'],
        'unknown filter' => ['?filter[secret]=1', 'filter'],
    ]);
});

describe('POST /api/v1/orders', function (): void {
    it('returns 422 with errors when the body is empty', function (): void {
        $this->postJson('/api/v1/orders', [])
            ->assertUnprocessable()
            ->assertExactJsonStructure([...ERROR_KEYS, 'errors' => ['channel', 'location_id', 'items']]);
    });

    it('rejects invalid payloads', function (array $overrides, string $field): void {
        $this->postJson('/api/v1/orders', validOrder($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    })->with([
        'unknown channel' => [['channel' => 'fax'], 'channel'],
        'location_id not integer' => [['location_id' => 'main'], 'location_id'],
        'no items' => [['items' => []], 'items'],
        'items not a list' => [['items' => ['a' => ['variant_id' => 1, 'qty' => 1]]], 'items'],
        'variant_id missing' => [['items' => [['qty' => 1]]], 'items.0.variant_id'],
        'variant_id not integer' => [['items' => [['variant_id' => 'x', 'qty' => 1]]], 'items.0.variant_id'],
        'qty zero' => [['items' => [['variant_id' => 1, 'qty' => 0]]], 'items.0.qty'],
        'qty not integer' => [['items' => [['variant_id' => 1, 'qty' => 1.5]]], 'items.0.qty'],
        'item price from client' => [['items' => [['variant_id' => 1, 'qty' => 1, 'price' => '1.00']]], 'items.0'],
        'notes too long' => [['notes' => str_repeat('a', 501)], 'notes'],
    ]);

    it('accepts every channel', function (string $channel): void {
        $this->postJson('/api/v1/orders', validOrder(['channel' => $channel]))->assertStatus(501);
    })->with(['web', 'pos', 'whatsapp', 'agent']);

    it('returns 501 for a valid payload without notes', function (): void {
        $payload = validOrder();
        unset($payload['notes']);

        $this->postJson('/api/v1/orders', $payload)
            ->assertStatus(501)
            ->assertExactJsonStructure(ERROR_KEYS)
            ->assertJsonPath('code', 'not_implemented');
    });

    it('accepts a valid Idempotency-Key', function (): void {
        $this->postJson('/api/v1/orders', validOrder(), ['Idempotency-Key' => (string) Str::uuid()])
            ->assertStatus(501);
    });

    it('rejects an Idempotency-Key with an invalid format with 422', function (): void {
        $this->postJson('/api/v1/orders', validOrder(), ['Idempotency-Key' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['idempotency_key']);
    });
});

it('returns 501 for show and cancel', function (string $method, string $uri): void {
    $this->json($method, $uri)
        ->assertStatus(501)
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'not_implemented');
})->with([
    ['GET', '/api/v1/orders/1'],
    ['POST', '/api/v1/orders/1/cancel'],
]);

it('returns 404 for a non-numeric order id', function (string $method, string $uri): void {
    $this->json($method, $uri)
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
})->with([
    ['GET', '/api/v1/orders/abc'],
    ['POST', '/api/v1/orders/abc/cancel'],
]);
