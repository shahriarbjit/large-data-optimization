<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * A paginated list in the standard shape:
 *
 *   {
 *     "data":  [ ... ],
 *     "meta":  { "current_page": 1, "per_page": 20, "total": 57, "last_page": 3 },
 *     "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
 *   }
 *
 * Usage: wrap a LengthAwarePaginator and name the item resource:
 *
 *   new PaginatedCollection($paginator, OrderResource::class)
 */
final class PaginatedCollection extends ResourceCollection
{
    /**
     * @param  class-string<JsonResource>  $itemResource
     */
    public function __construct(mixed $resource, string $itemResource)
    {
        $this->collects = $itemResource;

        parent::__construct($resource);
    }

    /**
     * Replaces Laravel's default pagination block with a smaller, fixed one.
     *
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array{meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'] ?? null,
                'per_page' => $paginated['per_page'] ?? null,
                'total' => $paginated['total'] ?? null,
                'last_page' => $paginated['last_page'] ?? null,
            ],
            'links' => [
                'first' => $paginated['first_page_url'] ?? null,
                'last' => $paginated['last_page_url'] ?? null,
                'prev' => $paginated['prev_page_url'] ?? null,
                'next' => $paginated['next_page_url'] ?? null,
            ],
        ];
    }
}
