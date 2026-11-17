# API conventions

These rules apply to every endpoint. Tests in `tests/Feature/Shared` and in each module check them.

## Basics

- **Base path:** `/api/v1`. A breaking change goes into `/api/v2`; `/v1` keeps working until it is retired.
- **JSON only.** Every request is treated as `Accept: application/json` (`ForceJsonResponse`), and every response is JSON, including 404s for unknown URLs. There are no web pages.
- **Field names:** `snake_case`.
- **Dates:** ISO 8601 in UTC with a `Z` suffix, for example `"2026-01-02T03:04:05Z"`. Data is stored in UTC. `API_DISPLAY_TIMEZONE` (default `Asia/Dhaka`) is only for human-facing output such as receipts, emails and reports.
- **Money:**
  - A string with exactly two decimals plus a separate currency, for example `"total": "1250.00", "currency": "BDT"`. Never a float.
  - In the database it will be `DECIMAL`. In code, use `Modules\Shared\Support\Money`.
  - The client never sends prices; the server looks them up.
- **IDs:** integers for now ([ADR 0004](decisions/0004-integer-ids.md)). Route parameters are constrained to digits, so `/orders/abc` is a 404.
- **Health:**
  - `GET /api/v1/health` says the API process is up. It checks no database or other services.
  - Laravel's `GET /up` is kept for load balancers.

## Success responses

```json
{ "data": { "id": 7, "status": "paid", "total": "1250.00", "currency": "BDT" } }
```

- A single object or action result goes in `data`.
- `meta` is added when there is something to say about the response, such as pagination.
- A create returns `201`. An action without a body (for example logout) returns `204`.
- API Resources in `Http/Resources` define the shape. Never return a model or a raw array from a controller.

### Lists and pagination

```http
GET /api/v1/orders?page=2&per_page=20&sort=-placed_at&filter[status]=paid&filter[channel]=web
```

```json
{
  "data": [ { "id": 41 }, { "id": 40 } ],
  "meta": { "current_page": 2, "per_page": 20, "total": 57, "last_page": 3 },
  "links": { "first": "...?page=1", "last": "...?page=3", "prev": "...?page=1", "next": "...?page=3" }
}
```

- **Pagination:** `page` (default 1) and `per_page` (default 20, maximum 100; set in `config/api.php`). Out-of-range values are a 422. Use `Modules\Shared\Http\Resources\PaginatedCollection`.
- **Cursor pagination, for very large tables** (products, users): counting millions of rows and deep `OFFSET`s are too slow, so these lists take `per_page` and `cursor` instead of `page` ([ADR 0008](decisions/0008-react-frontend.md)). There is no total. Send `?cursor={meta.next_cursor}` for the next page and `meta.prev_cursor` for the previous one. Use `CursorListRequest` and `CursorPaginatedCollection` from Shared.

  ```json
  {
    "data": [ ... ],
    "meta": { "per_page": 20, "next_cursor": "eyJpZCI6...", "prev_cursor": null },
    "links": { "prev": null, "next": "...?per_page=20&cursor=eyJpZCI6..." }
  }
  ```
- **Sorting:** `sort=field` sorts ascending; `sort=-field` sorts descending. Each endpoint lists its sortable fields; any other value is a 422. Several fields will be comma-separated (`sort=-placed_at,id`) when needed.
- **Filtering:** `filter[field]=value`. Each endpoint lists its filters; an unknown filter or value is a 422.

## Errors

**Every** error has the same body, whatever the status: 400, 401, 403, 404, 405, 409, 422, 429, 500, 501 and the rest. The content type is `application/problem+json`, in the spirit of [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) ([ADR 0005](decisions/0005-error-format.md)).

```json
{
  "type": "urn:urban-nest:error:validation_failed",
  "title": "Validation Failed",
  "status": 422,
  "detail": "The email field must be a valid email address.",
  "code": "validation_failed",
  "request_id": "01a1e39a-7c20-702d-b8a1-23912439bb5e",
  "errors": { "email": ["The email field must be a valid email address."] }
}
```

| Field | Meaning |
| --- | --- |
| `type` | `urn:urban-nest:error:{code}`, a stable identifier for the problem type. |
| `title` | Short, fixed summary of the problem type. |
| `status` | The HTTP status, repeated in the body. |
| `detail` | Human-readable explanation of this occurrence. |
| `code` | Stable, machine-readable string; clients switch on this. Never change an existing code. |
| `request_id` | Same as the `X-Request-Id` header; quote it in bug reports. |
| `errors` | Validation errors only: `field => [messages]`. Nested fields use dots (`items.0.qty`). |

### Codes

| Status | `code` | When |
| --- | --- | --- |
| 400 | `bad_request` | Malformed request. |
| 401 | `unauthenticated` | Missing, invalid or expired bearer token. |
| 401 | `invalid_credentials` | Wrong email or password on login. |
| 403 | `forbidden` | Authenticated but not allowed (Policies). |
| 404 | `not_found` (or specific, e.g. `order_not_found`) | Unknown URL or resource. |
| 405 | `method_not_allowed` | Wrong HTTP method; the `Allow` header lists the right ones. |
| 409 | `conflict` (or specific, e.g. `idempotency_key_reused`) | Conflicts with the current state. |
| 422 | `validation_failed` | FormRequest validation; has `errors`. |
| 422 | specific, e.g. `insufficient_stock` | A business rule (`DomainRuleException`). |
| 429 | `rate_limited` | Too many requests; see the `Retry-After` header. |
| 500 | `server_error` | Unexpected error. |
| 501 | `not_implemented` | The endpoint exists but has no implementation yet. |

### Throwing errors

- **Expected errors:** throw an `ApiException` subclass, never build JSON by hand:

  ```php
  throw new NotFoundException("Order {$id} was not found.", 'order_not_found');
  throw new DomainRuleException('Only 2 left in stock.', 'insufficient_stock');
  ```

- **Validation errors:** these come from FormRequests.
- **Unexpected exceptions:** these become 500 `server_error`.
  - With `APP_DEBUG=false` (production), `detail` is a fixed sentence. No message, stack trace or SQL leaks.
  - With `APP_DEBUG=true`, a `debug` object is added to 500 responses only.
- **Logging:** expected API errors (4xx and 501) are not logged as incidents; other 5xx errors are.

## Request ID

- **Request header:** `X-Request-Id`, optional. It is kept if it is a valid UUID or ULID; otherwise the server generates a UUIDv7.
- **Where it appears:** the same ID is returned in the `X-Request-Id` response header on **every** response, appears in every error body as `request_id`, and is added to every log line as `request_id`.

## Idempotency

`POST /api/v1/orders` accepts an `Idempotency-Key` header (UUID or ULID).

**Today (stub):**
- The header is optional.
- An invalid format is a 422 (`errors.idempotency_key`).
- A valid key is passed to the service in `PlaceOrderData::$idempotencyKey`.
- Nothing is stored yet.

**Intended behaviour,** when the real store replaces `IdempotencyKeyStub` (see [growth-path.md](growth-path.md)):
- The key becomes required on money-moving POSTs (orders, payments).
- Same key with the same payload returns the stored response with the original status; the work is not repeated.
- Same key with a different payload returns `409 idempotency_key_reused`.
- A request still in progress with the same key returns `409`; the client retries later.
- Keys expire after 24 hours.

## Rate limiting

| Limiter | Limit | Applies to |
| --- | --- | --- |
| `api` | 60 per minute per IP | every `/api/v1` route |
| `auth` | 10 per minute per IP | `/api/v1/auth/*`, on top of `api` |

Responses carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`. A 429 response also carries `Retry-After`. The limits are set in `config/api.php`. Limits per user come with authentication.

## CORS

- Allowed origins come from `CORS_ALLOWED_ORIGINS`: comma-separated, default `http://localhost:5173`.
- Credentials (cookies) are off; the API uses token authentication.
- `X-Request-Id`, `Retry-After` and the rate-limit headers are exposed to browsers.

## Logging

- **Local:** the `stack` channel writes to `storage/logs`.
- **Every other environment:** JSON lines to stderr, using Laravel's built-in `stderr` channel:

  ```dotenv
  LOG_CHANNEL=stderr
  LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
  ```

- Every log line written during a request has `request_id` in its context.

## Endpoints (v1)

| Method and path | Request | Status today |
| --- | --- | --- |
| `GET /api/v1/health` | none | 200 `{ data: { status, app, version, time } }` |
| `POST /api/v1/auth/register` | `name` (2-100), `email` (max 190, unique), `phone` (`01XXXXXXXXX` or `+8801XXXXXXXXX`, unique, stored as `+880…`), `password` (10-72) + `password_confirmation` | 201 `{ data: { access_token, token_type, expires_at, user } }` with role `user`; 422 |
| `POST /api/v1/auth/login` | `email`, `password` | 200 same shape; 401 `invalid_credentials`; 422 |
| `POST /api/v1/auth/logout` | `Authorization: Bearer {token}` | 204 (token revoked); 401 `unauthenticated` |
| `GET /api/v1/auth/me` | `Authorization: Bearer {token}` | 200 `{ data: { id, name, email, phone, role, created_at } }`; 401 |
| `GET /api/v1/products` | `per_page` (1-100, default 20), `cursor` | 200 cursor list of active products, newest first; 422 |
| `GET /api/v1/products/{id}` | none | 200 `{ data: { id, sku, name, description, brand, category, color, tags, price, currency, stock, rating, reviews_count, image, is_active, created_at, updated_at } }`; 404 `product_not_found` (unknown or inactive) |
| `GET /api/v1/users` | `Authorization: Bearer {token}` of an **admin**; `per_page`, `cursor` | 200 cursor list of users, newest first; 401; 403 `forbidden` for `user` and `guest` |
| `GET /api/v1/orders` | `page`, `per_page`, `sort` (`placed_at`, `total`, either with `-`), `filter[status]`, `filter[channel]` | 422 or 501 |
| `POST /api/v1/orders` | `channel` (`web`, `pos`, `whatsapp`, `agent`), `location_id`, `items[]` (`variant_id`, `qty` >= 1), `notes` (max 500); optional `Idempotency-Key` header | 422 or 501 |
| `GET /api/v1/orders/{order}` | none | 501 |
| `POST /api/v1/orders/{order}/cancel` | none | 501 |

The intended response shapes are in each module's `Http/Resources`. For example, an order has `id, channel, status, location_id, total, currency, items[], notes, placed_at, cancelled_at`.
