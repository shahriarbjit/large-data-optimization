<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Models\User;

/**
 * Who may see other users. Registered in AuthServiceProvider.
 */
final class UserPolicy
{
    /**
     * Only admins may list users.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->role === UserRole::Admin;
    }
}
