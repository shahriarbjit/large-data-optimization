<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

/**
 * The endpoint exists and validates input, but has no implementation yet.
 */
final class NotImplementedException extends ApiException
{
    public function status(): int
    {
        return 501;
    }

    public function title(): string
    {
        return 'Not Implemented';
    }

    protected function defaultErrorCode(): string
    {
        return 'not_implemented';
    }

    protected function defaultDetail(): string
    {
        return 'This endpoint is not implemented yet.';
    }
}
