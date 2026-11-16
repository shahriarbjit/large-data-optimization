<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
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

    /** POST /api/v1/auth/logout */
    public function logout(): Response
    {
        $this->auth->logout();

        return response()->noContent();
    }

    /** GET /api/v1/auth/me */
    public function me(): UserResource
    {
        return new UserResource($this->auth->currentUser());
    }
}
