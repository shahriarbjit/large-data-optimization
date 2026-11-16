<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

/**
 * The request conflicts with the current state of a resource
 * (e.g. cancelling an order that has already shipped, or an Idempotency-Key
 * reused with a different payload).
 */
final class ConflictException extends ApiException
{
    public function status(): int
    {
        return 409;
    }

    public function title(): string
    {
        return 'Conflict';
    }

    protected function defaultErrorCode(): string
    {
        return 'conflict';
    }

    protected function defaultDetail(): string
    {
        return 'The request conflicts with the current state of the resource.';
    }
}
