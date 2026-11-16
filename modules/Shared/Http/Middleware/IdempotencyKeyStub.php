<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the Idempotency-Key header on POST requests.
 *
 * Today (stub):
 * - The header is optional.
 * - If present it must be a UUID or ULID, otherwise 422 validation_failed.
 * - A valid key is stored on the request: IdempotencyKeyStub::keyFor($request).
 * - Nothing is stored, so a repeated request is processed again.
 *
 * Later (see docs/api-conventions.md and docs/growth-path.md), a real
 * middleware replaces this one:
 * - The key becomes required on money-moving POSTs (orders, payments).
 * - Same key + same payload: return the stored response, do not run again.
 * - Same key + different payload: 409 with code "idempotency_key_reused".
 * - Keys expire after 24 hours.
 *
 * Route alias: "idempotency" (registered in bootstrap/app.php).
 */
final class IdempotencyKeyStub
{
    public const string HEADER = 'Idempotency-Key';

    private const string ATTRIBUTE = 'idempotency_key';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->headers->has(self::HEADER)) {
            $key = (string) $request->headers->get(self::HEADER);

            if (! Str::isUuid($key) && ! Str::isUlid($key)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => ['The Idempotency-Key header must be a UUID or ULID.'],
                ]);
            }

            $request->attributes->set(self::ATTRIBUTE, $key);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    public static function keyFor(Request $request): ?string
    {
        $key = $request->attributes->get(self::ATTRIBUTE);

        return is_string($key) ? $key : null;
    }
}
