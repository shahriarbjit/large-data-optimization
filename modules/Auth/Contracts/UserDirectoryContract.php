<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Auth\DTOs\UserData;

/**
 * Read-only list of users (for admins). Never exposes passwords.
 */
interface UserDirectoryContract
{
    /**
     * Users, newest first, cursor-paginated.
     *
     * @return CursorPaginator<int, UserData>
     */
    public function paginate(int $perPage, ?string $cursor): CursorPaginator;
}
