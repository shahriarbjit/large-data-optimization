<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Http\Middleware\AssignRequestId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Turns every exception into the standard error body:
 *
 *   {
 *     "type": "urn:urban-nest:error:not_found",
 *     "title": "Not Found",
 *     "status": 404,
 *     "detail": "The requested resource was not found.",
 *     "code": "not_found",
 *     "request_id": "01J...",
 *     "errors": { "field": ["message"] }      // validation errors only
 *   }
 *
 * Registered in bootstrap/app.php. Laravel has already mapped some framework
 * exceptions before we get here (for example AuthorizationException becomes
 * a 403 AccessDeniedHttpException and ModelNotFoundException becomes a 404).
 *
 * See docs/api-conventions.md.
 */
final class ExceptionRenderer
{
    public const string CONTENT_TYPE = 'application/problem+json';

    /** Stable codes for plain HTTP errors. Other statuses fall back to "http_{status}". */
    private const array CODES_BY_STATUS = [
        400 => 'bad_request',
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        406 => 'not_acceptable',
        409 => 'conflict',
        413 => 'payload_too_large',
        415 => 'unsupported_media_type',
        419 => 'session_expired',
        422 => 'validation_failed',
        429 => 'rate_limited',
        500 => 'server_error',
        501 => 'not_implemented',
        503 => 'service_unavailable',
    ];

    public function __construct(private readonly bool $debug) {}

    /**
     * Returns null only for HttpResponseException, which already carries
     * a response built on purpose; Laravel then sends that response as is.
     */
    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if ($e instanceof HttpResponseException) {
            return null;
        }

        $headers = [];

        if ($e instanceof ApiException) {
            $body = $this->body($e->status(), $e->title(), $e->getMessage(), $e->errorCode(), $request);
        } elseif ($e instanceof ValidationException) {
            $body = $this->body(422, 'Validation Failed', $e->getMessage(), 'validation_failed', $request);
            $body['errors'] = $e->errors();
        } elseif ($e instanceof AuthenticationException) {
            $body = $this->body(401, 'Unauthenticated', 'Authentication is required.', 'unauthenticated', $request);
        } elseif ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $headers = $e->getHeaders();
            $body = $this->body($status, $this->statusText($status), $this->httpDetail($e, $status), $this->codeFor($status), $request);
        } else {
            $body = $this->body(500, 'Server Error', $this->serverErrorDetail($e), 'server_error', $request);

            if ($this->debug) {
                $body['debug'] = $this->debugInfo($e);
            }
        }

        return new JsonResponse($body, $body['status'], ['Content-Type' => self::CONTENT_TYPE] + $headers);
    }

    /**
     * @return array{type: string, title: string, status: int, detail: string, code: string, request_id: string}
     */
    private function body(int $status, string $title, string $detail, string $code, Request $request): array
    {
        return [
            'type' => 'urn:urban-nest:error:'.$code,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
            'request_id' => AssignRequestId::idFor($request),
        ];
    }

    private function codeFor(int $status): string
    {
        return self::CODES_BY_STATUS[$status] ?? 'http_'.$status;
    }

    private function statusText(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Error';
    }

    /**
     * Symfony's messages for 404/405 contain the raw URL and method list;
     * we use our own fixed sentences for the common cases.
     */
    private function httpDetail(HttpExceptionInterface $e, int $status): string
    {
        return match ($status) {
            404 => 'The requested resource was not found.',
            405 => 'This HTTP method is not allowed for this endpoint.',
            429 => 'Too many requests. Retry after the time given in the Retry-After header.',
            default => $e->getMessage() !== '' ? $e->getMessage() : $this->statusText($status).'.',
        };
    }

    private function serverErrorDetail(Throwable $e): string
    {
        return $this->debug ? $e->getMessage() : 'An unexpected error occurred.';
    }

    /**
     * Only when APP_DEBUG=true. Never enabled in production.
     *
     * @return array{exception: class-string, message: string, file: string, line: int, trace: list<string>}
     */
    private function debugInfo(Throwable $e): array
    {
        return [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
        ];
    }
}
