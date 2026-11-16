<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

/**
 * A user's role. Stored in users.role.
 *
 * - user:  a normal customer account (default for registration)
 * - admin: staff with full access
 * - guest: a limited account (for example created by an agent for a walk-in
 *          customer); may log in, but gets the least access
 *
 * Public: other modules may check a user's role through UserData.
 */
enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case Guest = 'guest';
}
