# Growth path

How this template becomes a large application, in the order each step is needed. Do each step only when the previous one is in production and a real need appears.

1. **Implement features inside the existing modules.**
   - Auth: real users and tokens.
   - Orders: real tables, with the order total computed on the server.
   - Each feature adds its own migrations and models at that time, inside its module. The model lives in `Modules\{Module}\Models` and stays internal.
   - Resolve the `TODO(db)` markers (`unique`, `exists` rules) and the `TODO(auth)` markers (auth middleware, Policies).
   - Record the auth package choice in an ADR before adding it.
   - Add Scramble (OpenAPI docs, route disabled outside local) and Laravel Sail (Docker) once real endpoints and a database exist; check their compatibility with the installed Laravel version first.
2. **Add modules with `php artisan module:make`:**
   - `Catalog` (products, variants, prices)
   - `Inventory` (stock per location)
   - `Customers`
   - `Payments`
   - `Fulfillment`
   - `Reporting`
3. **Make writes safe under concurrency.**
   - Replace `IdempotencyKeyStub` with a real store. Same key and same payload returns the stored response; same key and different payload returns 409.
   - Add the order and stock locking rules: row locks or atomic decrements on stock, and an order state machine.
4. **Move slow work out of the request.**
   - Queues on Redis (`QUEUE_CONNECTION=redis`) for event listeners, emails and SMS.
   - Scheduled jobs for clean-ups and summaries.
   - Monitoring: error tracking, queue depth, slow queries.
5. **Scale reads for reporting.**
   - Reporting reads from a read replica, or from summary tables filled by listeners and scheduled jobs.
   - It never runs heavy queries on the primary database.
6. **Extract a module into its own service** only when its load or team ownership demands it.
   - The module's Contracts become an HTTP or queue API, and its Events go on a message broker.
   - Because no other module touches its internals or tables, this is a move, not a rewrite.

## What each module may depend on

| Module | May use | Must not use |
| --- | --- | --- |
| Shared | Laravel and approved packages only | any module |
| Auth | Shared | other modules' internals |
| Catalog | Shared | other modules' internals |
| Customers | Shared, Auth (Contracts/DTOs/Events, e.g. `UserRegistered`) | Auth's Services or tables |
| Inventory | Shared, Catalog and Orders (Contracts/DTOs/Events) | Orders' Services or tables |
| Orders | Shared, Auth, Catalog, Inventory, Customers (Contracts/DTOs/Events) | their Services, Http, Models or tables |
| Payments | Shared, Orders (Contracts/DTOs/Events) | Orders' Services or tables |
| Fulfillment | Shared, Orders, Inventory (Contracts/DTOs/Events) | their internals |
| Reporting | Shared; any module's Events and DTOs; its own summary tables | other modules' tables or Services |

These are the intended directions. If two modules start to need each other's contracts in a cycle, stop and redesign with events (record the choice in an ADR).

## Rule for adding a new module

1. Write a short ADR: what the module owns (its data and its rules), and why no existing module should own it.
2. Run `php artisan module:make Name`. Never copy folders by hand.
3. Expose only Contracts, DTOs and Events. React to other modules through their events.
4. The module owns its tables. No other module reads or writes them.
5. Add tests in `modules/Name/tests`, and run `composer ci` (lint, Larastan, Deptrac, Pest).
