<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

use Carbon\CarbonImmutable;
use Modules\Shared\Support\Money;

/**
 * An order as other modules see it.
 */
final readonly class OrderData
{
    /**
     * @param  list<OrderItemData>  $items
     */
    public function __construct(
        public int $id,
        public OrderChannel $channel,
        public OrderStatus $status,
        public int $locationId,
        public Money $total,
        public array $items,
        public ?string $notes,
        public CarbonImmutable $placedAt,
        public ?CarbonImmutable $cancelledAt,
    ) {}
}
