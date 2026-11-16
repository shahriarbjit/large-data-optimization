<?php

declare(strict_types=1);

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Orders\Contracts\OrderServiceContract;
use Modules\Orders\Http\Requests\ListOrdersRequest;
use Modules\Orders\Http\Requests\PlaceOrderRequest;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Shared\Http\Resources\PaginatedCollection;

/**
 * Thin controller: FormRequest -> DTO -> Service -> API Resource.
 *
 * TODO(auth): authorize with an OrderPolicy once authentication exists.
 */
final class OrderController
{
    public function __construct(
        private readonly OrderServiceContract $orders,
    ) {}

    /** GET /api/v1/orders */
    public function index(ListOrdersRequest $request): PaginatedCollection
    {
        return new PaginatedCollection($this->orders->list($request->toData()), OrderResource::class);
    }

    /** POST /api/v1/orders (Idempotency-Key header supported) */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request->toData());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    /** GET /api/v1/orders/{order} */
    public function show(int $order): OrderResource
    {
        return new OrderResource($this->orders->find($order));
    }

    /** POST /api/v1/orders/{order}/cancel */
    public function cancel(int $order): OrderResource
    {
        return new OrderResource($this->orders->cancel($order));
    }
}
