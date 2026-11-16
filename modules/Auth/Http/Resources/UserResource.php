<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\DTOs\UserData;

/**
 * Response shape of a user:
 *
 *   { "id": 1, "name": "Rahim Uddin", "email": "rahim@example.com",
 *     "phone": "+8801712345678", "role": "user", "created_at": "2026-01-02T03:04:05Z" }
 *
 * @property UserData $resource
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'role' => $this->resource->role->value,
            'created_at' => $this->resource->createdAt->toIso8601ZuluString(),
        ];
    }
}
