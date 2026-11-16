<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Database\Factories\UserFactory;
use Modules\Auth\DTOs\UserData;
use Modules\Auth\DTOs\UserRole;

/**
 * Internal to the Auth module. Other modules never use this class; they get
 * UserData through AuthServiceContract or events.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property UserRole $role
 * @property string $password
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'phone', 'password', 'role'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @var array<string, mixed> */
    protected $attributes = ['role' => 'user'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function toData(): UserData
    {
        return new UserData(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            phone: $this->phone,
            role: $this->role,
            createdAt: $this->created_at,
        );
    }
}
