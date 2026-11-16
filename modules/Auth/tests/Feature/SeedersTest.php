<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Database\Seeders\AccountSeeder;
use Modules\Auth\Database\Seeders\BulkUserSeeder;
use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

it('seeds one admin and one normal user, and can run again', function (): void {
    $this->seed(AccountSeeder::class);
    $this->seed(AccountSeeder::class);

    $admin = User::query()->where('email', 'admin@urban-nest.test')->sole();
    $user = User::query()->where('email', 'user@urban-nest.test')->sole();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($user->role)->toBe(UserRole::User)
        ->and(Hash::check('Admin@123456', $admin->password))->toBeTrue()
        ->and(User::query()->count())->toBe(2);
});

it('lets the seeded admin log in', function (): void {
    $this->seed(AccountSeeder::class);

    $this->postJson('/api/v1/auth/login', ['email' => 'admin@urban-nest.test', 'password' => 'Admin@123456'])
        ->assertOk()
        ->assertJsonPath('data.user.role', 'admin');
});

it('bulk-seeds users with unique emails and phones, every 10th a guest, and skips existing rows', function (): void {
    config(['seed.bulk_users' => 25, 'seed.bulk_chunk' => 10]);

    $this->seed(BulkUserSeeder::class);
    $this->seed(BulkUserSeeder::class);

    expect(User::query()->count())->toBe(25)
        ->and(User::query()->where('role', 'guest')->count())->toBe(2)
        ->and(User::query()->where('email', 'user25@example.test')->value('phone'))->toBe('+8801000000025');

    $this->postJson('/api/v1/auth/login', ['email' => 'user7@example.test', 'password' => 'password1234'])->assertOk();
});

it('refuses to seed in production', function (string $seeder): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    // Called directly: `db:seed` would first ask for confirmation in production.
    $this->app->make($seeder)->run();
})->with([AccountSeeder::class, BulkUserSeeder::class])->throws(RuntimeException::class);
