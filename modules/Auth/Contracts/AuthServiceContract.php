<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts;

use Modules\Auth\DTOs\AuthTokenData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\RegisterData;
use Modules\Auth\DTOs\UserData;

/**
 * Public API of the Auth module. Other modules may depend on this interface
 * (and on Auth's DTOs and Events), never on Auth's Services or Http classes.
 */
interface AuthServiceContract
{
    /**
     * Creates a user and returns an access token for them.
     */
    public function register(RegisterData $data): AuthTokenData;

    public function login(LoginData $data): AuthTokenData;

    /**
     * TODO(auth): takes the authenticated user once authentication exists.
     */
    public function logout(): void;

    /**
     * TODO(auth): takes the authenticated user once authentication exists.
     */
    public function currentUser(): UserData;
}
