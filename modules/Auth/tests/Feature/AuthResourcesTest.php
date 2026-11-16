<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Modules\Auth\DTOs\AuthTokenData;
use Modules\Auth\DTOs\UserData;
use Modules\Auth\Http\Resources\AuthTokenResource;
use Modules\Auth\Http\Resources\UserResource;

/*
 * The resources document the response contract before the endpoints work.
 */

function sampleUser(): UserData
{
    return new UserData(
        id: 1,
        name: 'Rahim Uddin',
        email: 'rahim@example.com',
        phone: '+8801712345678',
        createdAt: CarbonImmutable::parse('2026-01-02 03:04:05', 'UTC'),
    );
}

it('renders a user', function (): void {
    expect((new UserResource(sampleUser()))->resolve(new Request))->toBe([
        'id' => 1,
        'name' => 'Rahim Uddin',
        'email' => 'rahim@example.com',
        'phone' => '+8801712345678',
        'created_at' => '2026-01-02T03:04:05Z',
    ]);
});

it('renders an access token with its user', function (): void {
    $token = new AuthTokenData(
        accessToken: 'token-value',
        expiresAt: CarbonImmutable::parse('2026-01-09 03:04:05', 'UTC'),
        user: sampleUser(),
    );

    $json = (new AuthTokenResource($token))->response(new Request)->getData(true);

    expect($json['data'])->toMatchArray([
        'access_token' => 'token-value',
        'token_type' => 'Bearer',
        'expires_at' => '2026-01-09T03:04:05Z',
    ])->and($json['data']['user']['email'])->toBe('rahim@example.com');
});
