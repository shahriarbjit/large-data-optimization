# 0001: Modular monolith with enforced boundaries

- **Status:** Accepted
- **Date:** 2026-11-16

## Context

The application starts small (login, registration, orders) but will grow to many modules (Catalog, Inventory, Payments, ...) and many developers. Most Laravel apps that grow this way end with every class able to call every other class and every query able to join every table, and the cost of change keeps rising.

## Options

1. **Default Laravel layout** (`app/Models`, `app/Http/Controllers`, ...). Simple, but nothing stops coupling between features.
2. **Microservices from day one.** Strong boundaries, but network calls, distributed transactions and several deployments before the product is proven.
3. **Modular monolith.** One application and one deployment, with code in `modules/{Module}` and boundaries checked by tools.
4. **A modules package** (for example nwidart/laravel-modules). It brings auto-discovery and its own conventions, and adds a package we did not approve.

## Decision

Option 3, built by hand:
- **Layout:**
  - `modules/{Module}` with the PSR-4 namespace `Modules\`.
  - A module's public surface is `Contracts`, `DTOs` and `Events`; everything else is internal.
  - `Shared` is the shared kernel and depends on no module.
- **Loading:**
  - Modules are listed explicitly in `config/modules.php`; nothing is discovered automatically.
  - `php artisan module:make` creates new modules from `stubs/module/`.
- **Enforcement:**
  - Deptrac (`deptrac/deptrac` 4.x; the old `qossmic/deptrac` package is abandoned), with one `ModulesPublic` layer plus one layer per module.
  - Pest architecture tests that check the same rules.
- **Cross-module reactions** use events, not calls into another module's internals.

## Reason

- We keep the simplicity of one deployment and one database.
- The boundaries are tested on every commit, so they do not erode.
- A module that later needs to become a service can be extracted, because nothing reaches into its internals or tables.
- The loader is about 50 lines that a reviewer can read in a minute.

## Consequences

- Each module repeats a little structure: a provider, a contract, DTOs. `module:make` removes the typing.
- Data that another module needs goes through a contract or an event, never a join across module tables. Reporting will need summary tables (see [growth-path.md](../growth-path.md)).
- Adding a module edits `config/modules.php` and `deptrac.yaml`. The command does this; the markers in both files must stay.
