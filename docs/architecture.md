# Architecture

urban-nest-api is a **modular monolith**: one Laravel 13 application and one deployment, with the code split into modules that have hard boundaries. The boundaries are checked on every `composer ci` by Deptrac and by Pest architecture tests. See [ADR 0001](decisions/0001-modular-monolith.md).

## Request flow

```text
HTTP request
  -> global middleware: AssignRequestId -> ForceJsonResponse -> Laravel (CORS, trim strings, ...)
  -> routes/api.php: prefix /api/v1, throttle:api
  -> module route file (modules/{Module}/routes/api_v1.php), e.g. throttle:auth, idempotency
  -> Controller (thin)
       FormRequest validates      -> 422 on failure
       $request->toData()         -> readonly DTO
       Service (via Contract)     -> business logic; throws ApiException subclasses
       API Resource               -> { "data": ... }
  <- any exception -> ExceptionRenderer -> standard error body (docs/api-conventions.md)
```

## Folder layout

```text
app/Console/Commands/MakeModuleCommand.php   php artisan module:make {Name}
bootstrap/app.php                            routing (api + /up), middleware, exception rendering
bootstrap/providers.php                      AppServiceProvider + ModulesServiceProvider
config/modules.php                           enabled modules: name => provider (explicit list)
config/api.php                               version, display timezone, currency, pagination, rate limits
config/cors.php                              allowed origins from CORS_ALLOWED_ORIGINS
routes/api.php                               /v1 group: health + each enabled module's routes/api_v1.php
stubs/module/                                templates used by module:make
modules/Shared/                              shared kernel (depends on no module)
modules/{Module}/                            one folder per module (below)
tests/Feature, tests/Unit                    app-level tests (Shared, console)
tests/Architecture                           Pest architecture and boundary tests
modules/{Module}/tests                       module tests (picked up automatically)
deptrac.yaml                                 layer rules
```

## Module layout

```text
modules/Orders/
├── Providers/OrdersServiceProvider.php   binds contracts to implementations (listed in config/modules.php)
├── Contracts/                            PUBLIC  interfaces other modules may call (OrderServiceContract)
├── DTOs/                                 PUBLIC  readonly data objects and their enums (OrderData, OrderStatus)
├── Events/                               PUBLIC  domain events, readonly classes carrying DTOs (OrderPlaced)
├── Services/                             internal business logic, implements the contracts
├── Http/Controllers/                     internal thin controllers, final
├── Http/Requests/                        internal FormRequests (extend BaseFormRequest), each with toData()
├── Http/Resources/                       internal API Resources: the documented response shape
├── Policies/                             internal authorization (when auth exists)
├── routes/api_v1.php                     routes, loaded inside /api/v1
└── tests/Feature, tests/Unit             module tests
```

Later, when a module gets tables: `Models/` (internal) and the module's migrations.

## Shared kernel (`modules/Shared`)

| Part | What it is |
| --- | --- |
| `Providers/ModulesServiceProvider` | Registers SharedServiceProvider, then each provider in config/modules.php. No discovery. |
| `Providers/SharedServiceProvider` | Binds `Clock` and `ExceptionRenderer`; defines the `api` and `auth` rate limiters. |
| `Http/Middleware/AssignRequestId` | `X-Request-Id`: accepts a client UUID/ULID, otherwise generates a UUIDv7; adds it to logs and errors. |
| `Http/Middleware/ForceJsonResponse` | Every request is treated as `Accept: application/json`. |
| `Http/Middleware/IdempotencyKeyStub` | Validates `Idempotency-Key` on POST; no storage yet (alias `idempotency`). |
| `Http/Requests/BaseFormRequest` | Base for all FormRequests. `authorize()` returns true until Policies exist. |
| `Http/Resources/PaginatedCollection` | Standard list shape: `data`, `meta`, `links`. |
| `Http/Controllers/HealthController` | `GET /api/v1/health`. |
| `Exceptions/ApiException` + subclasses | `NotImplemented` (501), `NotFound` (404), `Conflict` (409), `DomainRule` (422). |
| `Exceptions/ExceptionRenderer` | Turns every exception into the standard error body. |
| `Support/Clock`, `SystemClock` | Current time in UTC; inject it instead of calling `now()`. |
| `Support/Money` | Decimal-string money value object (placeholder; no arithmetic yet). |

## Dependency rules

| From \ may use | Shared | Other module's Contracts, DTOs, Events | Other module's Services, Http, Providers, Policies, Models | Own internals |
| --- | --- | --- | --- | --- |
| `Shared` | yes | **no** | **no** | yes |
| A module's internals | yes | yes | **no** | yes |
| A module's Contracts, DTOs, Events | yes | yes | **no** | **no** |
| `app/` (console commands) | yes | **no** | **no** | n/a |

Cross-module reactions use **events**: Orders dispatches `OrderPlaced`, and a listener in Inventory reacts to it. Inventory does not call Orders' services, and Orders does not know Inventory exists. A module also never reads another module's tables.

### How the rules are enforced

- **Deptrac** (`composer deptrac`, [deptrac.yaml](../deptrac.yaml)):
  - `ModulesPublic` collects every `Modules\*\{Contracts,DTOs,Events}`.
  - Each module has a layer for its internals.
  - `Shared` may depend on nothing.
- **Pest** ([tests/Architecture](../tests/Architecture)):
  - the same boundaries, checked per module pair;
  - the conventions: final classes, readonly DTOs and events, thin controllers, strict types, no `env()` outside `config/`, no debug helpers.

A deliberate violation (Orders importing `Modules\Auth\Services\AuthService`) fails both.

## Adding a module

```bash
php artisan module:make Payments
```

This creates `modules/Payments` from `stubs/module/` and registers it in two places:
- the provider in `config/modules.php`;
- a layer and ruleset entry in `deptrac.yaml`.

It refuses to overwrite an existing module. Then rename the `Example*` classes and run `composer ci`.

To remove a module, delete its folder, its line in `config/modules.php` (and the matching `use` import), and its two entries in `deptrac.yaml`.

## Decisions

See [docs/decisions](decisions/). Write a new ADR from [0000-template.md](decisions/0000-template.md) for any decision that is hard to reverse.
