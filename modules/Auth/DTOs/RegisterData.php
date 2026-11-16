<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use SensitiveParameter;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        /** Bangladesh mobile number, 01XXXXXXXXX or +8801XXXXXXXXX. */
        public string $phone,
        #[SensitiveParameter]
        public string $password,
    ) {}
}
