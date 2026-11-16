# 0005: One error format, close to RFC 9457

- **Status:** Accepted
- **Date:** 2026-11-16

## Context

Clients (web, POS, mobile, WhatsApp agents) need to handle errors in one way. By default, Laravel answers with different shapes depending on where the error came from: validation, authentication, HTTP exceptions, or debug pages.

## Options

1. **Laravel defaults.** `{ "message": ..., "errors": ... }` for validation and other shapes elsewhere. No stable codes and no request ID.
2. **Pure RFC 9457 problem details.** `type`, `title`, `status`, `detail`, `instance`.
3. **RFC 9457 fields plus a stable `code`, the `request_id` and validation `errors`.**

## Decision

Option 3, rendered for **every** exception by `Modules\Shared\Exceptions\ExceptionRenderer`:

```json
{ "type": "urn:urban-nest:error:not_found", "title": "Not Found", "status": 404,
  "detail": "The requested resource was not found.", "code": "not_found",
  "request_id": "...", "errors": { "field": ["message"] } }
```

- **Content type:** `application/problem+json`.
- **`type`:** `urn:urban-nest:error:{code}`. It is a valid URI that does not pretend a documentation page exists at a domain we do not own; it can become an `https://` link once public API docs exist.
- **`code`:** stable and snake_case. Clients switch on it, and existing codes never change.
- **How errors are raised:**
  - Expected errors are `ApiException` subclasses with their own status, title and code.
  - Validation errors come from FormRequests and add `errors`.
  - Anything else is a 500 `server_error`.
- **Debug output:**
  - With `APP_DEBUG=false`, `detail` is a fixed sentence and nothing internal leaks.
  - With `APP_DEBUG=true`, a `debug` block (exception, file, line, trace) is added to 500s only.
- **Logging:** expected API errors (4xx and 501) are not reported to the log; other 5xx errors are.

## Reason

- One shape for all errors means one error handler in every client.
- The `code` field survives changes to wording and translation.
- The `request_id` links a user's report to our logs.

## Consequences

- No code may return ad-hoc error JSON; the architecture rules and code review enforce it (AGENTS.md).
- A new error code is an API change: document it in [api-conventions.md](../api-conventions.md).
- An `HttpResponseException` (a response built on purpose) is passed through unchanged. Use it only for non-error responses.
