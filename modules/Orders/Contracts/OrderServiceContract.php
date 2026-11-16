<?php

declare(strict_types=1);

namespace Modules\Orders\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\DTOs\ListOrdersData;
use Modules\Orders\DTOs\OrderData;
use Modules\Orders\DTOs\PlaceOrderData;
use Modules\Shared\Exceptions\ConflictException;
use Modules\Shared\Exceptions\NotFoundException;

/**
 * Public API of the Orders module. Other modules may depend on this interface
 * (and on Orders' DTOs and Events), never on Orders' Services or Http classes.
 */
interface OrderServiceContract
{
    /**
     * @return LengthAwarePaginator<int, OrderData>
     */
    public function list(ListOrdersData $query): LengthAwarePaginator;

    public function place(PlaceOrderData $data): OrderData;

    /**
     * @throws NotFoundException
     */
    public function find(int $orderId): OrderData;

    /**
     * @throws NotFoundException
     * @throws ConflictException when the order can no longer be cancelled
     */
    public function cancel(int $orderId): OrderData;
}
