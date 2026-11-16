<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\DTOs\UserRole;
use RuntimeException;

/**
 * Inserts many fake users quickly (default 1,000,000, see config/seed.php).
 *
 * - Raw chunked INSERTs instead of Eloquent: about 100x faster.
 * - The password is hashed once and shared by every row; hashing a
 *   million passwords with bcrypt would take hours.
 * - INSERT IGNORE on the unique email/phone, so running it again only
 *   adds what is missing.
 *
 * Emails: user1@example.test ... user1000000@example.test
 * Phones: +8801000000001 ...  (never collides with AccountSeeder's +88019999999xx)
 * Roles:  every 10th user is a guest, the rest are users.
 */
final class BulkUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('BulkUserSeeder must not run in production.');
        }

        $total = config()->integer('seed.bulk_users');
        $chunk = config()->integer('seed.bulk_chunk');

        if ($total <= 0) {
            $this->command?->info('Bulk users skipped (SEED_BULK_USERS=0).');

            return;
        }

        $password = Hash::make(config()->string('seed.bulk_password'));
        $now = CarbonImmutable::now('UTC')->toDateTimeString();

        DB::connection()->disableQueryLog();
        // No command (and no progress bar) when called from code, e.g. in tests.
        $bar = $this->command?->getOutput()->createProgressBar($total);

        for ($start = 1; $start <= $total; $start += $chunk) {
            $rows = [];

            for ($n = $start, $end = min($start + $chunk - 1, $total); $n <= $end; $n++) {
                $rows[] = [
                    'name' => "Test User {$n}",
                    'email' => "user{$n}@example.test",
                    'phone' => '+8801'.str_pad((string) $n, 9, '0', STR_PAD_LEFT),
                    'role' => ($n % 10 === 0 ? UserRole::Guest : UserRole::User)->value,
                    'password' => $password,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insertOrIgnore($rows);
            $bar?->advance(count($rows));
        }

        $bar?->finish();
        $this->command?->newLine();
        $this->command?->info("Bulk users: {$total} requested (existing emails skipped).");
    }
}
