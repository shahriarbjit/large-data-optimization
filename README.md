# urban-nest-api

Laravel 13 API starter template (modular monolith). JSON only, versioned under `/api/v1`.

> Work in progress. This README is completed in the final step.

## Requirements

- PHP 8.4 or later, with the extensions curl, fileinfo, intl, mbstring, openssl, zip
- Composer 2
- No database is required.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Then open `http://127.0.0.1:8000/up` to check that the app is running.

Do **not** run `php artisan migrate`. The template has no tables yet.
