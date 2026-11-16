<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

/**
 * A requested resource does not exist (or the caller may not know it exists).
 * Pass a specific code when useful, e.g. new NotFoundException('...', 'order_not_found').
 */
final class NotFoundException extends ApiException
{
    public function status(): int
    {
        return 404;
    }

    public function title(): string
    {
        return 'Not Found';
    }

    protected function defaultErrorCode(): string
    {
        return 'not_found';
    }

    protected function defaultDetail(): string
    {
        return 'The requested resource was not found.';
    }
}
