<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Shared\Exceptions\ApiException;

/**
 * Wrong email or password. The message never says which one was wrong.
 */
final class InvalidCredentialsException extends ApiException
{
    public function status(): int
    {
        return 401;
    }

    public function title(): string
    {
        return 'Invalid Credentials';
    }

    protected function defaultErrorCode(): string
    {
        return 'invalid_credentials';
    }

    protected function defaultDetail(): string
    {
        return 'The email or password is incorrect.';
    }
}
