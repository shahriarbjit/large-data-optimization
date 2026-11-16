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
- Versions: Laravel 13, PHP 8.4, Pest 5, Larastan 3 (level 8), Deptrac 4 (`deptrac/deptrac`), Pint.
- Checks: `composer ci` = `composer lint` + `composer analyse` + `composer deptrac` + `composer test`. Fix style with `composer lint:fix`.
- No database yet: never run `php artisan migrate`; never add `unique`/`exists` rules (leave a `// TODO(db): ...` comment).
- Unfinished endpoints throw `Modules\Shared\Exceptions\NotImplementedException` (501).
- Markers: `TODO(db)` (needs tables), `TODO(auth)` (needs authentication). Search for them before starting either.
- Do not remove the `module:make` markers in config/modules.php and deptrac.yaml.
