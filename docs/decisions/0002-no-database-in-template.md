# 0002: No database in the template

- **Status:** Superseded by [0006](0006-mysql-sanctum-roles.md) (database, Sanctum and roles added 2026-11-16)
- **Date:** 2026-11-16

## Context

The template defines the conventions, the module boundaries and the API contract. Tables designed now would be guesses, and every guess becomes a migration that must be kept or undone later.

## Options

1. **Ship draft tables** for users and orders. Feels further along, but locks in schema decisions before the features exist.
2. **No database at all.** Endpoints validate input and answer 501; resources and DTOs document the intended shapes.

## Decision

Option 2:
- No migrations, models, factories or seeders are added, and `php artisan migrate` is never run.
- Laravel's shipped files are left untouched:
  - `app/Models/User.php`, `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php` and the three default migrations;
  - also `app/Http/Controllers/Controller.php` and `app/Providers/AppServiceProvider.php`.
  
  The strict-types and final-class architecture tests skip these three `app/` files by name.
- **Defaults chosen so nothing touches a database:**
  - `.env.example`: `SESSION_DRIVER=array`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
  - The rate limiter uses the file cache, or the array cache in tests.
- The project was created with `composer create-project --no-scripts`, because the skeleton's post-create script creates a SQLite file and runs migrations. Those steps were removed from `composer.json` (`setup`, `post-create-project-cmd`).
- **Validation rules that query the database** (`unique`, `exists`) are not used. A `// TODO(db): ...` comment marks each place they will go.
- **Tests:** they fail if a stub endpoint runs any query (`DB::listen`).

## Reason

- `composer install`, `composer ci` and `php artisan serve` work on a clean machine with no database configured.
- Each schema decision is made with the feature that needs it.

## Consequences

- Each first real feature adds its module's migrations and models, and writes an ADR if the decision is hard to reverse.
- Sessions in the `array` driver do not persist. That is fine for a token-based API; revisit if a stateful flow is ever needed.
- The shipped `User` model is not used by the Auth module. When Auth is implemented, decide whether to move it into `modules/Auth/Models` or delete it, and record that decision.
