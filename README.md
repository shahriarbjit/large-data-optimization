# urban-nest-api

A Laravel 13 **API starter template**, organised as a modular monolith. The backend is JSON only, versioned under `/api/v1`, on MySQL. A separate React app in `frontend/` uses it.

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
php artisan db:seed          # accounts, 1,000,000 users (~1 min) and 10,000,000 products (~17 min, ~5 GB); see config/seed.php
composer ci                  # lint, static analysis, boundaries, tests
php artisan serve            # http://127.0.0.1:8000
```

Seeded accounts (local only; change them with the `SEED_*` variables in `.env`):

| Role | Email | Password |
| --- | --- | --- |
| admin | `admin@urban-nest.test` | `Admin@123456` |
| user | `user@urban-nest.test` | `User@1234567` |
| user / guest (bulk) | `user1@example.test` Ã¢â‚¬Â¦ `user1000000@example.test` (every 10th is a guest) | `password1234` |

Try it:

```bash
curl -i http://127.0.0.1:8000/api/v1/health                 # 200
curl -i -X POST http://127.0.0.1:8000/api/v1/auth/register  # 422, standard error format
curl -i http://127.0.0.1:8000/api/v1/orders/1               # 501 not_implemented
curl -i http://127.0.0.1:8000/api/v1/nope                   # 404, standard error format
```

### Product data (search practice)

The Catalog module seeds a `products` table with 10,000,000 realistic rows: `php artisan db:seed --class="Modules\Catalog\Database\Seeders\ProductSeeder"`. The seeder is resumable: run it again to continue after an interruption.

| Column | Example | Useful for |
| --- | --- | --- |
| `sku` | `SKU-0000000007` | exact lookup (unique) |
| `name` | `Padma Premium Cotton Panjabi PA-4821` | full-text search, relevance |
| `description` | 3 sentences from shared templates | full-text search, highlighting |
| `brand`, `category`, `color` | `Padma`, `Men Clothing`, `Navy` | filters and facets (20 categories, 40 brands, 16 colours) |
| `tags` | `["cotton","eid","navy"]` (JSON) | keyword filters, facets |
| `price`, `currency` | `"2459.00"`, `BDT` | range filters, sorting |
| `stock`, `rating`, `reviews_count` | `120`, `4.3`, `812` | filters, boosting |
| `image` | `https://picsum.photos/seed/urban-nest-7/640/640` | display |
| `is_active` | `true` (~97%) | filter |
| `created_at`, `updated_at` | spread over 3 years, oldest first | date ranges, incremental sync |

Product number *n* always gets the same data (`ProductRowGenerator`).

## Frontend (React)

`frontend/` is a React 19 + TypeScript app built with Vite ([ADR 0008](docs/decisions/0008-react-frontend.md)). It has these pages:
- products, with a search bar (UI only until Elasticsearch);
- product details;
- users, for admins only;
- login.

Run it next to `php artisan serve` (needs Node 20+):

```bash
cd frontend
npm install
npm run dev          # http://localhost:5173 ; /api is forwarded to http://127.0.0.1:8000
```

| Command (in `frontend/`) | What it does |
| --- | --- |
| `npm run dev` | Dev server with hot reload. Change the API address with `API_PROXY_TARGET=http://127.0.0.1:8081` in `frontend/.env.local`. |
| `npm run build` | Type-check and build to `frontend/dist`. |
| `npm run lint` | oxlint. |

Log in as `admin@urban-nest.test` / `Admin@123456` to see the users page.

## Run with Docker (any PC, no PHP or MySQL needed)

Needs only [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine with Compose v2).

```bash
git clone <repo-url> urban-nest-api && cd urban-nest-api
docker compose up -d --build
docker compose logs -f app       # wait for "[entrypoint] ready" (about 1 minute the first time)
```

Then open http://localhost:8080/api/v1/health.

On the **first start**, with an empty database, the app container:
- runs the migrations;
- seeds the admin and user accounts above;
- seeds 1,000,000 fake users (about 30 seconds);
- seeds 10,000,000 products for search practice (about 17 minutes, about 5 GB).

Later starts keep the data and skip seeding.

| What | Where |
| --- | --- |
| React app | http://localhost:3000 (`DOCKER_FRONTEND_PORT`) |
| API | http://localhost:8080 (`DOCKER_HTTP_PORT`) |
| MySQL | `localhost:3307`, user `root`, password `secret` (`DOCKER_DB_PORT`, `DOCKER_DB_PASSWORD`) |
| Databases | `urban_nest_api` (app), `urban_nest_api_testing` (tests) |

| Command | What it does |
| --- | --- |
| `docker compose up -d --build` | Build and start; rebuild after code changes. |
| `docker compose logs -f app` | Follow the app logs (JSON lines). |
| `docker compose exec app php artisan route:list` | Run any artisan command. |
| `docker compose --profile test run --rm --build test` | Run `composer ci` (lint, Larastan, Deptrac, Pest) in Docker. |
| `docker compose down` | Stop; data is kept. |
| `docker compose down -v` | Stop and **delete all data**; the next `up` migrates and seeds again. |

**One database for both:** to make `php artisan serve` (and `npm run dev`) use the Docker MySQL instead of a local one, set `DB_PORT=3307` and `DB_PASSWORD=secret` in `.env`. Then http://localhost:5173 and http://localhost:3000 show the same data. Tests then use `urban_nest_api_testing` in Docker too.

**Port already in use** (for example another project on 8080, so `web` fails to start): add `DOCKER_HTTP_PORT=8081` to `.env` (Compose reads it), then run `docker compose up -d web`. The same works for `DOCKER_DB_PORT`, `DOCKER_ES_PORT` and `DOCKER_KIBANA_PORT`.

To seed less, set `DOCKER_SEED_BULK_USERS=1000` and `DOCKER_SEED_PRODUCTS=100000` (or `0`) in your shell, or in a `.env` file next to `compose.yaml`, **before the first start**. Images:
- `app`: PHP 8.4 FPM, without dev packages; see `Dockerfile`.
- `web`: nginx in front of `app`; see `docker/nginx/default.conf`.
- `db`: MySQL 8.4 with a named volume.

### Elasticsearch and Kibana (search practice)

Elasticsearch 9 and Kibana run in Docker ([ADR 0007](docs/decisions/0007-elasticsearch-in-docker.md)). Security is off, so both listen on `127.0.0.1` only. Never expose them to a network.

```bash
docker compose up -d elasticsearch kibana        # only these two; `docker compose up -d` starts everything
curl http://localhost:9200                       # version info
curl "http://localhost:9200/_cluster/health?pretty"
```

| What | Where |
| --- | --- |
| Elasticsearch | http://localhost:9200 (`DOCKER_ES_PORT`) |
| Kibana | http://localhost:5601 (`DOCKER_KIBANA_PORT`); Dev Tools: menu Ã¢ËœÂ° Ã¢â€ â€™ Management Ã¢â€ â€™ **Dev Tools**, or http://localhost:5601/app/dev_tools#/console |
| From Laravel | `config('services.elasticsearch.host')`: `ELASTICSEARCH_HOST`, `http://elasticsearch:9200` inside Docker |

- Kibana takes 1-2 minutes to become ready after Elasticsearch is healthy.
- Change the version with `ELASTIC_VERSION` (keep both the same) and the heap with `DOCKER_ES_HEAP` (default `1g`).
- On Linux, run `sudo sysctl -w vm.max_map_count=262144` first. Docker Desktop (Windows, macOS) needs nothing.
- A single node shows **yellow** health for indexes with replicas; use `number_of_replicas: 0` for practice indexes.
- `docker compose down -v` also deletes the Elasticsearch data (`es-data` volume).

## Commands

| Command | What it does |
| --- | --- |
| `composer ci` | Runs everything below, as CI does. Run it before calling work done. |
| `composer lint` / `composer lint:fix` | Check / fix code style (Pint). |
| `composer analyse` | Static analysis (Larastan, level 8). |
| `composer deptrac` | Check module boundaries (Deptrac). |
| `composer test` | Pest: feature, unit, module and architecture tests. |
| `php artisan module:make Name` | Create a new module (see below). |
| `php artisan route:list` | Show the 12 API routes plus `/up`. |

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
  Catalog/                                   products table, list and details endpoints
    Contracts/ DTOs/ Events/                 public: other modules may use these
    Services/ Http/ Providers/ Policies/      internal: only this module uses these
    routes/api_v1.php
    tests/
tests/                                       app-level, Shared and architecture tests
frontend/                                    React app (Vite + TypeScript)
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
