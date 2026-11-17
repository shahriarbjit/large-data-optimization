# 0009: The Docker MySQL is the only development database

- **Status:** Accepted (refines [0006](0006-mysql-sanctum-roles.md))
- **Date:** 2026-11-17

## Context

Developers ran a MySQL installed on their own PC, and Docker ran a second MySQL. That gave two databases with different data: `php artisan serve` showed one and the Docker app the other. Each developer also had to create databases and copy credentials into `.env`. The project owner wants every developer to use Docker for the database, with no separate database and no database settings in `.env`.

## Options

- **Keep both:** confusing, the data drifts apart, and every developer needs manual setup.
- **Docker MySQL only, `DB_*` lines in `.env.example`:** works, but every `.env` carries settings that never change.
- **Docker MySQL only, defaults in `config/database.php`:** nothing to configure.

## Decision

- The MySQL container (compose service `db`) is the development database for everything: `php artisan` on the host, the Docker app, and the tests (`urban_nest_api_testing`, created by `docker/mysql/init`).
- `config/database.php` defaults to it:
  - connection `mysql`, at `127.0.0.1:3307`;
  - database `urban_nest_api`, user `root`, password `secret`.
- `.env.example` (and so `.env`) has no `DB_*` lines.
- Containers still get `DB_HOST=db` and `DB_PORT=3306` from `compose.yaml`, and CI sets its own `DB_*` variables. Both override the defaults.
- The app container migrates and seeds an empty database on its first start, so a new developer gets accounts, 1M users and 10M products without running the seeders.

## Reason

One command (`docker compose up -d`) gives every developer the same database with the same data, and there is nothing to configure.

## Consequences

- Docker must be running whenever you use `php artisan` or run `composer ci` on the host.
- The default password `secret` is for the local container only. Production must set real `DB_*` environment variables.
- To use another MySQL server, add `DB_*` lines to `.env`. The defaults step aside.
- The developer's own Windows or macOS MySQL is no longer used and can be removed.
