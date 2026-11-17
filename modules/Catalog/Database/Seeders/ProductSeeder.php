<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Inserts many products quickly (default 10,000,000; see config/seed.php).
 *
 * - Raw chunked INSERTs, in id order (fast for InnoDB).
 * - Resumable: starts after the products already in the table, and
 *   INSERT IGNORE on the unique sku skips anything already there.
 * - Rows come from ProductRowGenerator, so product n always has the same data.
 *
 * About 10-20 minutes for 10 million rows on a laptop; ~4 GB of disk.
 */
final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('ProductSeeder must not run in production.');
        }

        $total = config()->integer('seed.products');
        $chunk = config()->integer('seed.products_chunk');

        if ($total <= 0) {
            $this->command?->info('Products skipped (SEED_PRODUCTS=0).');

            return;
        }

        $existing = (int) DB::table('products')->count();
        if ($existing >= $total) {
            $this->command?->info("Products: {$existing} already present, nothing to do.");

            return;
        }

        $generator = new ProductRowGenerator;
        DB::connection()->disableQueryLog();

        $bar = $this->command?->getOutput()->createProgressBar($total);
        $bar?->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% / ~%estimated:-6s%');
        $bar?->setProgress($existing);

        for ($start = $existing + 1; $start <= $total; $start += $chunk) {
            $rows = [];
            $end = min($start + $chunk - 1, $total);

            for ($n = $start; $n <= $end; $n++) {
                $rows[] = $generator->row($n, $total);
            }

            DB::table('products')->insertOrIgnore($rows);
            $bar?->advance(count($rows));
        }

        $bar?->finish();
        $this->command?->newLine();
        $this->command?->info("Products: {$total} in the table.");
    }
}
