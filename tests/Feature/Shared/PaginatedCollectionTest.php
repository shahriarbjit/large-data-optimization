<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Shared\Http\Resources\PaginatedCollection;

it('renders data, meta and links in the standard shape', function (): void {
    $items = [['id' => 1], ['id' => 2]];
    $paginator = new LengthAwarePaginator($items, total: 5, perPage: 2, currentPage: 1, options: ['path' => 'http://localhost/api/v1/things']);

    $response = (new PaginatedCollection($paginator, JsonResource::class))->toResponse(Request::create('/api/v1/things'));
    $body = json_decode((string) $response->getContent(), true);

    expect($body)->toHaveKeys(['data', 'meta', 'links'])
        ->and(array_keys($body))->toBe(['data', 'meta', 'links'])
        ->and($body['data'])->toBe($items)
        ->and($body['meta'])->toBe(['current_page' => 1, 'per_page' => 2, 'total' => 5, 'last_page' => 3])
        ->and($body['links']['prev'])->toBeNull()
        ->and($body['links']['next'])->toBe('http://localhost/api/v1/things?page=2');
});
