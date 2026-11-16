<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\DTOs\AuthTokenData;

/**
 * Response shape of register and login:
 *
 *   { "access_token": "...", "token_type": "Bearer",
 *     "expires_at": "2026-01-09T03:04:05Z", "user": { ...UserResource } }
 *
 * @property AuthTokenData $resource
 */
final class AuthTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource->accessToken,
            'token_type' => $this->resource->tokenType,
            'expires_at' => $this->resource->expiresAt?->toIso8601ZuluString(),
            'user' => new UserResource($this->resource->user),
        ];
    }
}
