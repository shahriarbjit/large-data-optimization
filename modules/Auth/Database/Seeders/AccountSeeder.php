<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Models\User;
use RuntimeException;

/**
 * Creates (or updates) one admin and one normal user from config/seed.php.
 * Safe to run again.
 */
final class AccountSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('AccountSeeder must not run in production.');
        }

        $roles = ['admin' => UserRole::Admin, 'user' => UserRole::User];

        foreach ($roles as $key => $role) {
            /** @var array{name: string, email: string, phone: string, password: string} $account */
            $account = config("seed.accounts.{$key}");

            User::query()->updateOrCreate(
                ['email' => mb_strtolower($account['email'])],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'password' => $account['password'],
                    'role' => $role,
                ],
            );

            $this->command?->info("{$role->value}: {$account['email']}");
        }
    }
}
