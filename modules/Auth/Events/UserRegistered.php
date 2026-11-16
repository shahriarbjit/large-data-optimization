<?php

declare(strict_types=1);

namespace Modules\Auth\Events;

use Modules\Auth\DTOs\UserData;

/**
 * Dispatched after a user has registered. Other modules may listen to it
 * (for example Customers, to create a customer profile).
 */
final readonly class UserRegistered
{
    public function __construct(
        public UserData $user,
    ) {}
}
