<?php

declare(strict_types=1);

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\DTOs\OrderData;

/**
 * Response shape of an order:
 *
 *   {
 *     "id": 1,
 *     "channel": "web",
 *     "status": "pending",
 *     "location_id": 1,
 *     "total": "1250.00",
 *     "currency": "BDT",
 *     "items": [ { ...OrderItemResource } ],
 *     "notes": null,
 *     "placed_at": "2026-01-02T03:04:05Z",
 *     "cancelled_at": null
 *   }
 *
 * @property OrderData $resource
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;

        return [
            'id' => $order->id,
            'channel' => $order->channel->value,
            'status' => $order->status->value,
            'location_id' => $order->locationId,
            'total' => $order->total->amount,
            'currency' => $order->total->currency,
            'items' => OrderItemResource::collection($order->items),
            'notes' => $order->notes,
            'placed_at' => $order->placedAt->toIso8601ZuluString(),
            'cancelled_at' => $order->cancelledAt?->toIso8601ZuluString(),
        ];
    }
}
