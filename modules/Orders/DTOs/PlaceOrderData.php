<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

final readonly class PlaceOrderData
{
    /**
     * @param  list<PlaceOrderItemData>  $items
     */
    public function __construct(
        public OrderChannel $channel,
        public int $locationId,
        public array $items,
        public ?string $notes,
        /** From the Idempotency-Key header; null when the client sent none. */
        public ?string $idempotencyKey,
    ) {}
}
