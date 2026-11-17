<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * A cursor-paginated list, for very large tables (millions of rows) where
 * counting rows and deep OFFSETs are too slow:
 *
 *   {
 *     "data":  [ ... ],
 *     "meta":  { "per_page": 20, "next_cursor": "eyJpZCI6...", "prev_cursor": null },
 *     "links": { "prev": null, "next": "...?cursor=eyJpZCI6..." }
 *   }
 *
 * Send ?cursor={next_cursor} for the next page. There is no total.
 * Usage: new CursorPaginatedCollection($cursorPaginator, ProductResource::class)
 */
final class CursorPaginatedCollection extends ResourceCollection
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
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array{meta: array<string, mixed>, links: array<string, mixed>}
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'per_page' => $paginated['per_page'] ?? null,
                'next_cursor' => $paginated['next_cursor'] ?? null,
                'prev_cursor' => $paginated['prev_cursor'] ?? null,
            ],
            'links' => [
                'prev' => $paginated['prev_page_url'] ?? null,
                'next' => $paginated['next_page_url'] ?? null,
            ],
        ];
    }
}
