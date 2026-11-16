<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use Illuminate\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Support\Clock;

/**
 * GET /api/v1/health
 *
 * Says the API process is up. It does not check the database or other
 * services; that becomes a separate readiness check when they exist.
 */
final class HealthController
{
    public function __construct(
        private readonly Config $config,
        private readonly Clock $clock,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'status' => 'ok',
                'app' => $this->config->string('app.name'),
                'version' => $this->config->string('api.version'),
                'time' => $this->clock->now()->toIso8601ZuluString(),
            ],
        ]);
    }
}
