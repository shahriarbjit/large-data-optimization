# urban-nest-api

A Laravel 13 **API starter template**, organised as a modular monolith. It is JSON only, versioned under `/api/v1`, and needs no database.

It already includes:
- the standard error format, request IDs and rate limiting;
- a module loader and a `module:make` generator;
- two skeleton modules, **Auth** and **Orders**. Their endpoints validate input and answer `501 Not Implemented` until the features are built.

Quality gates: Pint, Larastan (level 8), Deptrac (module boundaries) and Pest (feature and architecture tests), all run with `composer ci` and in GitHub Actions.

## Requirements

- PHP **8.4+** with the curl, fileinfo, intl, mbstring, openssl, zip and pdo_mysql extensions
- Composer 2
- MySQL 8+ ([ADR 0006](docs/decisions/0006-mysql-sanctum-roles.md))

## Setup

Create two databases first (app and tests):

```sql
CREATE DATABASE urban_nest_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE urban_nest_api_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
git clone <repo-url> urban-nest-api && cd urban-nest-api
composer install
cp .env.example .env         # then set DB_USERNAME / DB_PASSWORD
php artisan key:generate
php artisan migrate
php artisan db:seed          # admin + user accounts and 1,000,000 fake users (~70 s; SEED_BULK_USERS=0 to skip)
composer ci                  # lint, static analysis, boundaries, tests
php artisan serve            # http://127.0.0.1:8000
```

Seeded accounts (local only; change them with the `SEED_*` variables in `.env`):

| Role | Email | Password |
| --- | --- | --- |
| admin | `admin@urban-nest.test` | `Admin@123456` |
| user | `user@urban-nest.test` | `User@1234567` |
| user / guest (bulk) | `user1@example.test` … `user1000000@example.test` (every 10th is a guest) | `password1234` |

Try it:

```bash
curl -i http://127.0.0.1:8000/api/v1/health                 # 200
curl -i -X POST http://127.0.0.1:8000/api/v1/auth/register  # 422, standard error format
curl -i http://127.0.0.1:8000/api/v1/orders/1               # 501 not_implemented
curl -i http://127.0.0.1:8000/api/v1/nope                   # 404, standard error format
```

## Commands

| Command | What it does |
| --- | --- |
| `composer ci` | Runs everything below, as CI does. Run it before calling work done. |
| `composer lint` / `composer lint:fix` | Check / fix code style (Pint). |
| `composer analyse` | Static analysis (Larastan, level 8). |
| `composer deptrac` | Check module boundaries (Deptrac). |
| `composer test` | Pest: feature, unit, module and architecture tests. |
| `php artisan module:make Name` | Create a new module (see below). |
| `php artisan route:list` | Show the 9 API routes plus `/up`. |

## Folder structure

```text
app/Console/Commands/MakeModuleCommand.php   module:make
bootstrap/app.php                            API routing, global middleware, exception rendering
config/modules.php                           enabled modules (explicit list)
config/api.php, config/cors.php              API version, pagination, rate limits, CORS
routes/api.php                               /api/v1 group; loads each module's routes/api_v1.php
stubs/module/                                templates for module:make
modules/
  Shared/                                    shared kernel: errors, request id, base classes, health
  Auth/                                      skeleton: register, login, logout, me
  Orders/                                    skeleton: list, place, show, cancel
    Contracts/ DTOs/ Events/                 public: other modules may use these
    Services/ Http/ Providers/ Policies/      internal: only this module uses these
    routes/api_v1.php
    tests/
tests/                                       app-level, Shared and architecture tests
docs/                                        architecture, API conventions, growth path, ADRs
deptrac.yaml phpstan.neon pint.json          quality tool configs
.github/workflows/ci.yml                     composer ci on PHP 8.4, no database
```

Details: [docs/architecture.md](docs/architecture.md).

## Conventions in short

- **Controllers are thin:** FormRequest, then `toData()` builds the DTO, then the Service (through its Contract), then the API Resource.
- **Errors:** every error is `{ type, title, status, detail, code, request_id, errors? }`. Throw `ApiException` subclasses (`NotFoundException`, `ConflictException`, `DomainRuleException`, `NotImplementedException`).
- **Field formats:**
  - JSON fields use `snake_case`.
  - Dates are ISO 8601 in UTC (`2026-01-02T03:04:05Z`).
  - Money is a string with two decimals (`"1250.00"`) plus `currency` (`BDT`).
  - IDs are integers.
- **Lists:** `?page=&per_page=` (max 100), `sort=-field`, `filter[field]=value`.
- **Request IDs:** every response has an `X-Request-Id` header, which also appears in logs and errors.
- **Idempotency:** `POST /orders` accepts an `Idempotency-Key` (UUID or ULID).
- **Rate limits:** 60/min per IP on the API and 10/min on `/auth`.
- **Boundaries:** a module uses only other modules' `Contracts`, `DTOs` and `Events`. Deptrac and Pest fail the build otherwise.

Full rules: [docs/api-conventions.md](docs/api-conventions.md). Rules for coding agents: [AGENTS.md](AGENTS.md).

## Adding a module

```bash
php artisan module:make Payments
composer ci
```

The command:
- creates `modules/Payments/` with a provider, a contract, an example DTO, event, service stub, controller, request, resource, route and test;
- registers the provider in `config/modules.php`;
- adds the module's layer to `deptrac.yaml`.

It refuses to overwrite an existing module. Rename the `Example*` classes for your feature, keep `composer ci` green, and write an ADR for what the module owns ([growth-path.md](docs/growth-path.md#rule-for-adding-a-new-module)).

To remove a module, delete `modules/Name`, its line and `use` import in `config/modules.php`, and its two entries in `deptrac.yaml`.

## Environment

| Variable | Default | Purpose |
| --- | --- | --- |
| `API_VERSION` | `0.1.0` | Shown by `/api/v1/health`. |
| `API_DISPLAY_TIMEZONE` | `Asia/Dhaka` | For human-facing output only; data is UTC. |
| `API_DEFAULT_CURRENCY` | `BDT` | Default currency. |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | Comma-separated allowed origins. |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `array` / `file` / `sync` | Keep the app database-free. |
| `LOG_CHANNEL` | `stack` | Use `stderr` with `LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter` outside local. |

## Further reading

- [docs/architecture.md](docs/architecture.md): modules, layers, dependency rules
- [docs/api-conventions.md](docs/api-conventions.md): response and error formats, pagination, idempotency, rate limits
- [docs/growth-path.md](docs/growth-path.md): how this grows into a large application
- [docs/decisions/](docs/decisions/): architecture decision records
