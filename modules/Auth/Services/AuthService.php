<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\AuthServiceContract;
use Modules\Auth\DTOs\AuthTokenData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\RegisterData;
use Modules\Auth\DTOs\UserData;
use Modules\Shared\Exceptions\NotImplementedException;

/**
 * Stub: every method answers 501 until authentication is implemented.
 */
final class AuthService implements AuthServiceContract
{
    public function register(RegisterData $data): AuthTokenData
    {
        throw new NotImplementedException('User registration is not implemented yet.');
    }

    public function login(LoginData $data): AuthTokenData
    {
        throw new NotImplementedException('Login is not implemented yet.');
    }

    public function logout(): void
    {
        throw new NotImplementedException('Logout is not implemented yet.');
    }

    public function currentUser(): UserData
    {
        throw new NotImplementedException('The current user endpoint is not implemented yet.');
    }
}
