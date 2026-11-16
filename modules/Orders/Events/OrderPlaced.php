<?php

declare(strict_types=1);

namespace Modules\Orders\Events;

use Modules\Orders\DTOs\OrderData;

/**
 * Dispatched after an order is placed (for example Inventory reserves stock,
 * Payments starts a payment).
 */
final readonly class OrderPlaced
{
    public function __construct(
        public OrderData $order,
    ) {}
}
