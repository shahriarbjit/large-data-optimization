<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\DTOs\AuthTokenData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\RegisterData;
use Modules\Auth\DTOs\UserData;

/**
 * Public API of the Auth module. Other modules may depend on this interface
 * (and on Auth's DTOs and Events), never on Auth's Services, Models or Http classes.
 */
interface AuthServiceContract
{
    /**
     * Creates a user with the "user" role and issues an access token.
     */
    public function register(RegisterData $data): AuthTokenData;

    /**
     * Wrong email or password: answers 401 with code "invalid_credentials".
     */
    public function login(LoginData $data): AuthTokenData;

    /**
     * Revokes the access token used for the current request.
     */
    public function logout(Authenticatable $user): void;

    public function currentUser(Authenticatable $user): UserData;
}
