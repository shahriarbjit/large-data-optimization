<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

/**
 * One requested line of a new order. Prices are never taken from the client;
 * the service looks them up.
 */
final readonly class PlaceOrderItemData
{
    public function __construct(
        public int $variantId,
        public int $qty,
    ) {}
}
