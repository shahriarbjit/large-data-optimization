<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use LogicException;
use Modules\Auth\Contracts\AuthServiceContract;
use Modules\Auth\DTOs\AuthTokenData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\RegisterData;
use Modules\Auth\DTOs\UserData;
use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Events\UserRegistered;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\Models\User;
use Modules\Shared\Support\Clock;

/**
 * Registration, login and logout with Laravel Sanctum personal access tokens.
 */
final class AuthService implements AuthServiceContract
{
    private const string TOKEN_NAME = 'api';

    public function __construct(
        private readonly Hasher $hasher,
        private readonly Dispatcher $events,
        private readonly ConnectionInterface $db,
        private readonly Clock $clock,
    ) {}

    public function register(RegisterData $data): AuthTokenData
    {
        $user = $this->db->transaction(fn (): User => User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'password' => $data->password, // hashed by the model's "hashed" cast
            'role' => UserRole::User,
        ]));

        $this->events->dispatch(new UserRegistered($user->toData()));

        return $this->issueToken($user);
    }

    public function login(LoginData $data): AuthTokenData
    {
        $user = User::query()->where('email', $data->email)->first();

        // Check a password even when the email is unknown, so the response
        // time does not reveal which emails are registered.
        $hash = $user instanceof User ? $user->password : $this->hasher->make('timing-equaliser');

        if (! $this->hasher->check($data->password, $hash) || ! $user instanceof User) {
            throw new InvalidCredentialsException;
        }

        return $this->issueToken($user);
    }

    public function logout(Authenticatable $user): void
    {
        $this->user($user)->currentAccessToken()->delete();
    }

    public function currentUser(Authenticatable $user): UserData
    {
        return $this->user($user)->toData();
    }

    private function issueToken(User $user): AuthTokenData
    {
        $minutes = config()->integer('sanctum.expiration');
        $expiresAt = $minutes > 0 ? $this->clock->now()->addMinutes($minutes) : null;

        $token = $user->createToken(self::TOKEN_NAME, ['role:'.$user->role->value], $expiresAt);

        return new AuthTokenData(
            accessToken: $token->plainTextToken,
            expiresAt: $expiresAt,
            user: $user->toData(),
        );
    }

    private function user(Authenticatable $user): User
    {
        if (! $user instanceof User) {
            throw new LogicException('The authenticated user must be a '.User::class.'.');
        }

        return $user;
    }
}
