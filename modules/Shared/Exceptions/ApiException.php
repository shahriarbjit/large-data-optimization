<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for every error the API returns on purpose.
 *
 * Throw a subclass from a service or controller. ExceptionRenderer turns it
 * into the standard error body. Do not build error JSON by hand.
 *
 * - status():    HTTP status code
 * - title():     short, fixed summary of the problem type
 * - errorCode(): stable, machine-readable code, e.g. "not_found"
 * - getMessage() is used as the human-readable "detail"
 *
 * Do not add a render() method here: rendering belongs to ExceptionRenderer.
 */
abstract class ApiException extends RuntimeException
{
    public function __construct(
        string $detail = '',
        private readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($detail !== '' ? $detail : $this->defaultDetail(), 0, $previous);
    }

    abstract public function status(): int;

    abstract public function title(): string;

    abstract protected function defaultErrorCode(): string;

    abstract protected function defaultDetail(): string;

    final public function errorCode(): string
    {
        return $this->errorCode ?? $this->defaultErrorCode();
    }
}
