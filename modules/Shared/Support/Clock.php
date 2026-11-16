<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Carbon\CarbonImmutable;

/**
 * The current time. Inject this instead of calling now() in services,
 * so tests can use a fixed time.
 */
interface Clock
{
    /** Always in UTC. */
    public function now(): CarbonImmutable;
}
