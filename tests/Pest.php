<?php

declare(strict_types=1);

use Tests\TestCase;

/*
 * Feature tests boot the Laravel application (no database).
 * Module tests live in modules/{Module}/tests and are picked up by the glob,
 * so a new module's tests run without editing this file.
 */
pest()->extend(TestCase::class)->in(
    'Feature',
    '../modules/*/tests/Feature',
);

/**
 * Keys every error body has (docs/api-conventions.md).
 * Validation errors add "errors" on top of these.
 */
const ERROR_KEYS = ['type', 'title', 'status', 'detail', 'code', 'request_id'];
