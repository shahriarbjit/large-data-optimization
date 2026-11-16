<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use SensitiveParameter;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $password,
    ) {}
}
