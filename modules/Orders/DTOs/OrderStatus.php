<?php

declare(strict_types=1);

namespace Modules\Orders\DTOs;

/**
 * Order lifecycle. Provisional: the full state machine (and which
 * transitions are allowed) is decided when Orders is implemented.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
}
