# Project rules (read before every task)
- Laravel API modular monolith. Code lives in modules/{Module}. See docs/architecture.md and docs/api-conventions.md.
- A module imports another module's Contracts, DTOs and Events only. Never its Services, Http, Models or tables.
- Controllers are thin: FormRequest -> DTO -> Service -> API Resource. Business logic lives in Services.
- All errors use the standard error format. Throw ApiException subclasses; do not return ad-hoc JSON errors.
- Money is a string with two decimals in JSON and DECIMAL in the database, never float.
- Every write endpoint has a FormRequest. Authorization uses Policies once auth exists.
- New module: run `php artisan module:make Name`; do not copy folders by hand.
- Every change includes tests. Run `composer ci` before declaring work done.
- No new packages without asking. Check the installed version's docs before using an API; do not invent methods.
- Keep tasks small. If a task is ambiguous or touches money, auth, or stock in a new way, stop and ask.
- Record design decisions in docs/decisions/ (options, choice, reason, date).

## Quick reference
- Versions: Laravel 13, PHP 8.4, Sanctum 4, Pest 5, Larastan 3 (level 8), Deptrac 4 (`deptrac/deptrac`), Pint.
- Checks: `composer ci` = `composer lint` + `composer analyse` + `composer deptrac` + `composer test`. Fix style with `composer lint:fix`.
- Database: the MySQL container only (`docker compose up -d`; host `127.0.0.1:3307`, root/secret are the defaults in config/database.php, so `.env` has no `DB_*` lines; ADR 0009). MySQL (`urban_nest_api`; tests use `urban_nest_api_testing` with RefreshDatabase). Each module owns its tables: models in `Modules\{Module}\Models`, migrations in `modules/{Module}/Database/Migrations` (loaded by the module's provider). See docs/decisions/0006.
- Auth: Sanctum bearer tokens (`auth:sanctum`). Roles are `user`, `admin`, `guest` (`Modules\Auth\DTOs\UserRole`); never let a client choose its role.
- Frontend: React 19 + TypeScript + Vite in `frontend/` (ADR 0008). It calls `/api/...` on its own origin (Vite proxy in dev, nginx in Docker). Check: `npm run lint && npm run build` in `frontend/`.
- Huge tables (products, users) use cursor pagination: `CursorListRequest` + `CursorPaginatedCollection` (no COUNT, no OFFSET).
- Orders has no tables yet: keep its `TODO(db)` markers until Orders is implemented.
- Unfinished endpoints throw `Modules\Shared\Exceptions\NotImplementedException` (501).
- Markers: `TODO(db)` (needs tables), `TODO(auth)` (needs authentication). Search for them before starting either.
- Do not remove the `module:make` markers in config/modules.php and deptrac.yaml.
