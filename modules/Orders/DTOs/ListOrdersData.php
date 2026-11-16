<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

/**
 * GET /orders query: ?page=1&per_page=20&sort=-placed_at&filter[status]=paid&filter[channel]=web
 */
final readonly class ListOrdersData
{
    public function __construct(
        public int $page,
        public int $perPage,
        /** Column name; a leading "-" means descending. */
        public string $sort,
        public ?OrderStatus $status,
        public ?OrderChannel $channel,
    ) {}
}
