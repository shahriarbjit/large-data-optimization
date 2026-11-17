<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\AccountSeeder;
use Modules\Auth\Database\Seeders\BulkUserSeeder;
use Modules\Catalog\Database\Seeders\ProductSeeder;

/**
 * php artisan db:seed
 *
 * Calls each module's seeders. Settings are in config/seed.php.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            BulkUserSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
