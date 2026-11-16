<?php

declare(strict_types=1);

namespace Modules\Orders\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\OrderServiceContract;
use Modules\Orders\DTOs\ListOrdersData;
use Modules\Orders\DTOs\OrderData;
use Modules\Orders\DTOs\PlaceOrderData;
use Modules\Shared\Exceptions\NotImplementedException;

/**
 * Stub: every method answers 501 until Orders is implemented.
 */
final class OrderService implements OrderServiceContract
{
    public function list(ListOrdersData $query): LengthAwarePaginator
    {
        throw new NotImplementedException('Listing orders is not implemented yet.');
    }

    public function place(PlaceOrderData $data): OrderData
    {
        throw new NotImplementedException('Placing orders is not implemented yet.');
    }

    public function find(int $orderId): OrderData
    {
        throw new NotImplementedException('Showing an order is not implemented yet.');
    }

    public function cancel(int $orderId): OrderData
    {
        throw new NotImplementedException('Cancelling orders is not implemented yet.');
    }
}
