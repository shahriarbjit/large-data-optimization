<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request an ID and returns it in the X-Request-Id header.
 *
 * - A client-supplied X-Request-Id is kept if it is a valid UUID or ULID.
 * - Otherwise a new UUID (version 7, time-ordered) is generated.
 * - The ID is added to every log line and to every error body.
 *
 * Runs first in the global middleware stack (see bootstrap/app.php), so it
 * also covers 404, 405 and errors thrown by other global middleware.
 */
final class AssignRequestId
{
    public const string HEADER = 'X-Request-Id';

    private const string ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $id = self::idFor($request);

        Log::withContext(['request_id' => $id]);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    /**
     * The request's ID. Assigned on first call and then stored on the request,
     * so the middleware and the exception renderer always agree.
     */
    public static function idFor(Request $request): string
    {
        $existing = $request->attributes->get(self::ATTRIBUTE);

        if (is_string($existing)) {
            return $existing;
        }

        $supplied = $request->headers->get(self::HEADER);

        $id = is_string($supplied) && self::isValid($supplied)
            ? $supplied
            : (string) Str::uuid7();

        $request->attributes->set(self::ATTRIBUTE, $id);

        return $id;
    }

    private static function isValid(string $value): bool
    {
        return Str::isUuid($value) || Str::isUlid($value);
    }
}
