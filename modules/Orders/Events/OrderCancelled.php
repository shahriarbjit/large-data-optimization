<?php

declare(strict_types=1);

namespace Modules\Orders\Events;

use Modules\Orders\DTOs\OrderData;

/**
 * Dispatched after an order is cancelled (for example Inventory releases stock,
 * Payments refunds).
 */
final readonly class OrderCancelled
{
    public function __construct(
        public OrderData $order,
    ) {}
}
