<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Carbon\CarbonImmutable;
use SensitiveParameter;

/**
 * An issued access token. The token mechanism (opaque tokens, JWT, ...) is
 * decided when authentication is implemented; see docs/growth-path.md.
 */
final readonly class AuthTokenData
{
    public function __construct(
        #[SensitiveParameter]
        public string $accessToken,
        public ?CarbonImmutable $expiresAt,
        public UserData $user,
        public string $tokenType = 'Bearer',
    ) {}
}
