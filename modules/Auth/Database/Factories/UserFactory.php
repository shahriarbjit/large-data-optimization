<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Models\User;

/**
 * For tests. Every factory user has the password "password1234".
 *
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /** @var class-string<User> */
    protected $model = User::class;

    private static ?string $passwordHash = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+8801'.fake()->unique()->numerify('#########'),
            'role' => UserRole::User,
            // Hash once: bcrypt is slow on purpose.
            'password' => self::$passwordHash ??= bcrypt('password1234'),
        ];
    }

    public function admin(): self
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function guest(): self
    {
        return $this->state(['role' => UserRole::Guest]);
    }
}
