<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\DTOs\UserRole;
use Modules\Auth\Events\UserRegistered;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rahim Uddin',
        'email' => 'rahim@example.com',
        'phone' => '01712345678',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ], $overrides);
}

describe('POST /api/v1/auth/register', function (): void {
    it('creates a user with the "user" role and returns a token', function (): void {
        Event::fake([UserRegistered::class]);

        $response = $this->postJson('/api/v1/auth/register', validRegistration())
            ->assertCreated()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email', 'phone', 'role', 'created_at']]])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'rahim@example.com')
            ->assertJsonPath('data.user.phone', '+8801712345678')
            ->assertJsonPath('data.user.role', 'user');

        $user = User::query()->sole();
        expect($user->role)->toBe(UserRole::User)
            ->and($user->password)->not->toBe('correct-horse-battery')
            ->and(Hash::check('correct-horse-battery', $user->password))->toBeTrue()
            ->and(PersonalAccessToken::findToken($response->json('data.access_token')))->not->toBeNull();

        Event::assertDispatched(UserRegistered::class, fn (UserRegistered $e): bool => $e->user->email === 'rahim@example.com');
    });

    it('normalises email to lower case and phone to +880', function (): void {
        $this->postJson('/api/v1/auth/register', validRegistration(['email' => 'Rahim@Example.COM', 'phone' => '01812345678']))
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'rahim@example.com')
            ->assertJsonPath('data.user.phone', '+8801812345678');
    });

    it('ignores a role sent by the client', function (): void {
        $this->postJson('/api/v1/auth/register', validRegistration(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'user');
    });

    it('rejects a duplicate email or phone, also in another format', function (array $second, string $field): void {
        User::factory()->create(['email' => 'rahim@example.com', 'phone' => '+8801712345678']);

        $this->postJson('/api/v1/auth/register', validRegistration($second))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    })->with([
        'same email, other case' => [['email' => 'RAHIM@example.com', 'phone' => '01900000000'], 'email'],
        'same phone, local format' => [['email' => 'other@example.com', 'phone' => '01712345678'], 'phone'],
        'same phone, +880 format' => [['email' => 'other@example.com', 'phone' => '+8801712345678'], 'phone'],
    ]);

    it('returns 422 with errors for every field when the body is empty', function (): void {
        $this->postJson('/api/v1/auth/register', [])
            ->assertUnprocessable()
            ->assertExactJsonStructure([...ERROR_KEYS, 'errors' => ['name', 'email', 'phone', 'password']])
            ->assertJsonPath('code', 'validation_failed');
    });

    it('rejects invalid values', function (array $overrides, string $field): void {
        $this->postJson('/api/v1/auth/register', validRegistration($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    })->with([
        'name too short' => [['name' => 'R'], 'name'],
        'name too long' => [['name' => str_repeat('a', 101)], 'name'],
        'email invalid' => [['email' => 'not-an-email'], 'email'],
        'email too long' => [['email' => str_repeat('a', 180).'@example.com'], 'email'],
        'phone too short' => [['phone' => '0171234567'], 'phone'],
        'phone not mobile' => [['phone' => '02123456789'], 'phone'],
        'phone wrong country' => [['phone' => '+9101712345678'], 'phone'],
        'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
        'password too long' => [['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)], 'password'],
        'password not confirmed' => [['password_confirmation' => 'something-else'], 'password'],
    ]);

    it('explains the phone format', function (): void {
        $this->postJson('/api/v1/auth/register', validRegistration(['phone' => '123']))
            ->assertJsonPath('errors.phone.0', 'The phone must be a Bangladesh mobile number: 01XXXXXXXXX or +8801XXXXXXXXX.');
    });

    it('never echoes the password back', function (): void {
        $response = $this->postJson('/api/v1/auth/register', validRegistration());

        expect($response->getContent())->not->toContain('correct-horse-battery');
    });
});

describe('POST /api/v1/auth/login', function (): void {
    it('returns a token for the right password, for every role', function (UserRole $role): void {
        User::factory()->create(['email' => 'rahim@example.com', 'role' => $role]);

        $this->postJson('/api/v1/auth/login', ['email' => 'Rahim@example.com', 'password' => 'password1234'])
            ->assertOk()
            ->assertJsonPath('data.user.role', $role->value)
            ->assertJsonStructure(['data' => ['access_token', 'expires_at']]);
    })->with(UserRole::cases());

    it('returns 401 invalid_credentials for a wrong password or unknown email', function (string $email, string $password): void {
        User::factory()->create(['email' => 'rahim@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password])
            ->assertUnauthorized()
            ->assertExactJsonStructure(ERROR_KEYS)
            ->assertJsonPath('code', 'invalid_credentials')
            ->assertJsonPath('detail', 'The email or password is incorrect.');
    })->with([
        'wrong password' => ['rahim@example.com', 'wrong-password'],
        'unknown email' => ['nobody@example.com', 'password1234'],
    ]);

    it('returns 422 when the body is empty', function (): void {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('authenticated routes', function (): void {
    it('returns 401 without a token', function (string $method, string $uri): void {
        $this->json($method, $uri)
            ->assertUnauthorized()
            ->assertExactJsonStructure(ERROR_KEYS)
            ->assertJsonPath('code', 'unauthenticated');
    })->with([
        ['POST', '/api/v1/auth/logout'],
        ['GET', '/api/v1/auth/me'],
    ]);

    it('returns 401 with an invalid token', function (): void {
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer 1|not-a-real-token'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    });

    it('returns the current user for GET /me', function (): void {
        $token = $this->postJson('/api/v1/auth/register', validRegistration())->json('data.access_token');

        $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.email', 'rahim@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonMissingPath('data.password');
    });

    it('revokes the token on logout', function (): void {
        $token = $this->postJson('/api/v1/auth/register', validRegistration())->json('data.access_token');

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer {$token}"])->assertNoContent();

        expect(PersonalAccessToken::query()->count())->toBe(0);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
    });

    it('rejects an expired token', function (): void {
        $token = $this->postJson('/api/v1/auth/register', validRegistration())->json('data.access_token');

        $this->travel(8)->days();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
    });
});

it('applies the auth rate limiter: 429 in the standard format after 10 requests per minute', function (): void {
    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'rate_limited');
});

it('does not apply the auth rate limiter to other modules', function (): void {
    foreach (range(1, 11) as $attempt) {
        $this->getJson('/api/v1/health')->assertOk();
    }
});
