<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Contracts\AuthServiceContract;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\AuthTokenResource;
use Modules\Auth\Http\Resources\UserResource;

/**
 * Thin controller: FormRequest -> DTO -> Service -> API Resource.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthServiceContract $auth,
    ) {}

    /** POST /api/v1/auth/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->auth->register($request->toData());

        return (new AuthTokenResource($token))->response()->setStatusCode(201);
    }

    /** POST /api/v1/auth/login */
    public function login(LoginRequest $request): AuthTokenResource
    {
        return new AuthTokenResource($this->auth->login($request->toData()));
    }

    /** POST /api/v1/auth/logout (auth:sanctum) */
    public function logout(Request $request): Response
    {
        $this->auth->logout($this->authenticated($request));

        return response()->noContent();
    }

    /** GET /api/v1/auth/me (auth:sanctum) */
    public function me(Request $request): UserResource
    {
        return new UserResource($this->auth->currentUser($this->authenticated($request)));
    }

    /**
     * The route's auth:sanctum middleware guarantees a user.
     */
    private function authenticated(Request $request): Authenticatable
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
