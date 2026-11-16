# 0006: MySQL, Sanctum tokens and a role column

- **Status:** Accepted (supersedes [0002](0002-no-database-in-template.md))
- **Date:** 2026-11-16

## Context

The first real feature is authentication: registration and login with three roles (`user`, `admin`, `guest`). That needs a database and a token mechanism. The project owner asked for Laravel Sanctum and provided a local MySQL server.

## Options

- **Tokens:**
  - Laravel Sanctum personal access tokens: first-party, simple, revocable, stored hashed.
  - Passport (OAuth2): heavier, only needed for third-party clients.
  - JWT: stateless, but hard to revoke.
- **Roles:**
  - A `role` column backed by a PHP enum.
  - A roles/permissions package. Not approved, and more than three fixed roles need.
- **Database:**
  - MySQL, which matches production and the owner's setup.
  - SQLite for tests. Its PHP extension is blocked on the development machine.

## Decision

- **Sanctum 4.x, bearer tokens only:**
  - `config/sanctum.php` has `guard => []`, so there are no session or cookie flows.
  - Tokens expire after `SANCTUM_EXPIRATION` minutes (default 7 days) and are revoked on logout.
  - Installed with `composer require` and `vendor:publish`, not `install:api`, so the API routing stays as wired in `bootstrap/app.php`.
- **`users.role`** is a string column (`user`, `admin` or `guest`, indexed), cast to `Modules\Auth\DTOs\UserRole`.
  - Registration always creates `user`; a client cannot choose its role.
  - Each token gets the ability `role:{role}`.
- **The Auth module owns the users table:**
  - The model is `Modules\Auth\Models\User`, moved from `app/Models`, and is internal to Auth.
  - The factory is `Modules\Auth\Database\Factories\UserFactory`.
  - Auth's migrations live in `modules/Auth/Database/Migrations`, loaded by `AuthServiceProvider`. Laravel's shipped `users` migration stays; Auth adds `phone` (unique, stored as `+8801XXXXXXXXX`) and `role`.
- **MySQL everywhere:**
  - The app uses `urban_nest_api`.
  - Tests use `urban_nest_api_testing` with `RefreshDatabase`.
  - CI runs a `mysql:8.4` service.
- **Seeders** (`config/seed.php`; they refuse to run in production):
  - one admin and one normal user;
  - optionally 1,000,000 fake users, inserted in raw chunks of 5,000 with one shared password hash.

## Reason

Sanctum covers first-party apps (web, POS, mobile) with the least code, and a fixed role enum is easy to read and test. Keeping the model and tables inside Auth keeps the module boundary that ADR 0001 relies on.

## Consequences

- `composer ci` now needs a reachable MySQL with the testing database (see README).
- **Role checks** (for example admin-only routes) come next. Use Policies, or middleware checking `UserData::$role` or the token ability `role:admin`.
- **The `guest` role** can log in and gets the least access. Who creates guest accounts, and what they may do, is still open.
- **Expired tokens** stay in the table until `php artisan sanctum:prune-expired` runs. Schedule it once there is a scheduler.
