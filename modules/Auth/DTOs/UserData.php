<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Carbon\CarbonImmutable;

/**
 * A user as other modules see it. Never contains the password.
 */
final readonly class UserData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $phone,
        public CarbonImmutable $createdAt,
    ) {}
}
