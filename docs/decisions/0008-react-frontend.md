# 0008: React frontend in frontend/, product and user list endpoints

- **Status:** Accepted (amends [0003](0003-api-only-no-web-layer.md): Laravel stays API-only)
- **Date:** 2026-11-17

## Context

The project owner asked for a React UI to browse the 10,000,000 products (list and details page, with a search bar) and the users. Search itself waits for Elasticsearch, so for now the search bar is UI only. The UI needs read endpoints for products and users.

## Options

- **Where React lives:**
  - A separate Vite app in `frontend/` that calls the API: Laravel stays API-only (ADR 0003), and either side can be deployed alone.
  - Inside Laravel with Vite and Blade or Inertia: brings back the web layer that ADR 0003 removed.
- **Pagination for 10M products and 1M users:**
  - Page numbers with a total (`PaginatedCollection`): `COUNT(*)` takes seconds, and deep `OFFSET`s scan millions of rows.
  - Cursor (keyset) pagination on `id`: every page costs the same. There is no total and no jumping to page N.
- **Who may list users:** anyone, any signed-in user, or admins only.

## Decision

- **`frontend/`** holds React 19 + TypeScript, built with Vite.
  - `react-router` handles the pages: `/products`, `/products/:id`, `/users`, `/login`.
  - There is no UI library and no data-fetching library; styling is plain CSS with light and dark modes.
  - The browser calls `/api/...` on its own origin, so no CORS is needed:
    - in development, the Vite dev server forwards it to Laravel (`API_PROXY_TARGET`, default `http://127.0.0.1:8000`);
    - in Docker, the frontend's nginx forwards it to the `web` service.
  - The Sanctum token is kept in `localStorage`.
- **Endpoints:**
  - `GET /api/v1/products` and `GET /api/v1/products/{id}` are public, read-only and show only active products. They are in the Catalog module, behind `ProductCatalogContract`.
  - `GET /api/v1/users` is in the Auth module, behind `UserDirectoryContract`, and only admins may use it (`UserPolicy::viewAny`, registered as the `users.viewAny` ability).
- **Cursor pagination** for both lists: `CursorListRequest` and `CursorPaginatedCollection` in Shared, ordered by `id` descending.
- **Search bar:** keeps `?q=` in the URL and shows a notice. No search endpoint until the Elasticsearch work.
- **Policies may use their module's models.** Laravel's Gate passes the user model to every policy, so the architecture test now allows Policies to use models.
- **Quality gates:** CI runs `npm run lint` (oxlint) and `npm run build` (TypeScript check and build).

## Reason

A separate SPA keeps the backend's API-only design and module rules. Keyset pagination is the only way to page through 10M rows with constant speed. User data (emails, phones) is personal, so only admins can list it.

## Consequences

- Run two dev servers: `php artisan serve` and `npm run dev` in `frontend/`. Docker adds a `frontend` service on port 3000.
- Lists show Previous and Next, not page numbers or a total.
- On machines where Windows Application Control blocks unsigned native binaries, oxlint's binding cannot load. Run the lint in CI or Docker there.
- There are no frontend unit tests yet. Adding Vitest needs approval for the package.
