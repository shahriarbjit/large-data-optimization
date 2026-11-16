# 0003: API only, no web layer, local disk serving off

- **Status:** Accepted
- **Date:** 2026-11-16

## Context

The Laravel 13 skeleton ships a web layer:
- `routes/web.php` with `GET /` and the welcome view;
- frontend tooling: `package.json`, `vite.config.js`, `resources/css` and `resources/js`;
- Composer `setup` and `dev` scripts that run npm and Vite.

It also sets `'serve' => true` on the local disk, which registers a `GET /storage/{path}` route for signed local file URLs.

This project is a JSON API for separate clients (web app, POS, mobile), so `php artisan route:list` should show only the API routes.

## Options

1. **Keep the skeleton as shipped.** `/` returns an HTML page, `/storage/{path}` exists, and npm is needed to run `composer setup`.
2. **Remove the web layer and turn off local disk serving.**

## Decision

Option 2:
- Remove `routes/web.php`, `resources/views/welcome.blade.php`, `resources/css`, `resources/js`, `package.json`, `vite.config.js` and `.npmrc`.
- `bootstrap/app.php` registers only `api` routes (prefix `api`), console routes, and the `/up` health route.
- `config/filesystems.php`: the local disk has `'serve' => false`.
- **Composer scripts:**
  - `setup` = `composer install`, copy `.env`, `key:generate`.
  - `dev` = `php artisan serve`.
  - Neither uses npm, Vite or `migrate`.
- `ForceJsonResponse` and the exception renderer make every response JSON. `GET /` and `GET /storage/anything` are 404s in the standard error format, which feature tests check.

## Reason

- There is no HTML surface to secure or maintain.
- Contributors need no Node.js toolchain.
- The route list matches the API documentation exactly.

## Consequences

- An admin UI, if needed later, is a separate client of this API. It is not built into this application.
- Files to download are served by the API through signed URLs on object storage (for example S3) when that need appears, not through the local disk route.
