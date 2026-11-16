<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

use Modules\Shared\Support\Money;

final readonly class OrderItemData
{
    public function __construct(
        public int $variantId,
        public int $qty,
        public Money $unitPrice,
        public Money $lineTotal,
    ) {}
}
