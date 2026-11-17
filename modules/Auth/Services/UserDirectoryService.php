<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Auth\Contracts\UserDirectoryContract;
use Modules\Auth\DTOs\UserData;
use Modules\Auth\Models\User;

/**
 * Cursor pagination on the primary key: fast on 1,000,000+ users.
 */
final class UserDirectoryService implements UserDirectoryContract
{
    public function paginate(int $perPage, ?string $cursor): CursorPaginator
    {
        return User::query()
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
            ->withQueryString()
            ->through(fn (User $user): UserData => $user->toData());
    }
}
