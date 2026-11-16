<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Modules\Orders\DTOs\OrderChannel;
use Modules\Orders\DTOs\OrderData;
use Modules\Orders\DTOs\OrderItemData;
use Modules\Orders\DTOs\OrderStatus;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Shared\Support\Money;

/*
 * The resource documents the response contract before the endpoints work.
 */

it('renders an order with money as strings and dates in UTC', function (): void {
    $order = new OrderData(
        id: 7,
        channel: OrderChannel::WhatsApp,
        status: OrderStatus::Paid,
        locationId: 1,
        total: Money::of('1250', 'BDT'),
        items: [
            new OrderItemData(variantId: 10, qty: 2, unitPrice: Money::of('625'), lineTotal: Money::of('1250')),
        ],
        notes: null,
        placedAt: CarbonImmutable::parse('2026-01-02 09:04:05', 'Asia/Dhaka'),
        cancelledAt: null,
    );

    $json = (new OrderResource($order))->response(new Request)->getData(true);

    expect($json)->toBe(['data' => [
        'id' => 7,
        'channel' => 'whatsapp',
        'status' => 'paid',
        'location_id' => 1,
        'total' => '1250.00',
        'currency' => 'BDT',
        'items' => [
            ['variant_id' => 10, 'qty' => 2, 'unit_price' => '625.00', 'line_total' => '1250.00'],
        ],
        'notes' => null,
        'placed_at' => '2026-01-02T03:04:05Z',
        'cancelled_at' => null,
    ]]);
});
