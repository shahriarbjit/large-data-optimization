<?php

declare(strict_types=1);

namespace Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\DTOs\OrderItemData;

/**
 * { "variant_id": 10, "qty": 2, "unit_price": "625.00", "line_total": "1250.00" }
 *
 * @property OrderItemData $resource
 */
final class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'variant_id' => $this->resource->variantId,
            'qty' => $this->resource->qty,
            'unit_price' => $this->resource->unitPrice->amount,
            'line_total' => $this->resource->lineTotal->amount,
        ];
    }
}
