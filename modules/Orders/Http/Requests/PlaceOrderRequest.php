<?php

declare(strict_types=1);

namespace Modules\Orders\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Orders\DTOs\OrderChannel;
use Modules\Orders\DTOs\PlaceOrderData;
use Modules\Orders\DTOs\PlaceOrderItemData;
use Modules\Shared\Http\Middleware\IdempotencyKeyStub;
use Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * POST /api/v1/orders
 *
 *   {
 *     "channel": "web",
 *     "location_id": 1,
 *     "items": [ { "variant_id": 10, "qty": 2 } ],
 *     "notes": "Call before delivery"
 *   }
 */
final class PlaceOrderRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(OrderChannel::class)],
            // TODO(db): exists:locations,id
            'location_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'list', 'min:1'],
            'items.*' => ['required', 'array:variant_id,qty'],
            // TODO(db): exists:product_variants,id
            'items.*.variant_id' => ['required', 'integer', 'min:1'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toData(): PlaceOrderData
    {
        /** @var list<array{variant_id: int|string, qty: int|string}> $items */
        $items = $this->validated('items');

        return new PlaceOrderData(
            channel: OrderChannel::from($this->string('channel')->toString()),
            locationId: $this->integer('location_id'),
            items: array_map(
                fn (array $item): PlaceOrderItemData => new PlaceOrderItemData((int) $item['variant_id'], (int) $item['qty']),
                $items,
            ),
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            idempotencyKey: IdempotencyKeyStub::keyFor($this),
        );
    }
}
