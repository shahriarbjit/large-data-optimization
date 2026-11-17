<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Contracts\UserDirectoryContract;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Shared\Http\Requests\CursorListRequest;
use Modules\Shared\Http\Resources\CursorPaginatedCollection;

/**
 * Admin-only list of users.
 */
final class UserController
{
    public function __construct(
        private readonly UserDirectoryContract $users,
    ) {}

    /** GET /api/v1/users?per_page=20&cursor=... (auth:sanctum, admin) */
    public function index(CursorListRequest $request): CursorPaginatedCollection
    {
        Gate::authorize('users.viewAny');

        return new CursorPaginatedCollection(
            $this->users->paginate($request->perPage(), $request->cursor()),
            UserResource::class,
        );
    }
}
