<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

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

beforeEach(function (): void {
    // These endpoints must work with no database at all.
    DB::listen(fn () => throw new RuntimeException('Auth stubs must not query the database.'));
});

describe('POST /api/v1/auth/register', function (): void {
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
        'phone +880 without 1' => [['phone' => '+880212345678'], 'phone'],
        'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
        'password not confirmed' => [['password_confirmation' => 'something-else'], 'password'],
    ]);

    it('explains the phone format', function (): void {
        $this->postJson('/api/v1/auth/register', validRegistration(['phone' => '123']))
            ->assertJsonPath('errors.phone.0', 'The phone must be a Bangladesh mobile number: 01XXXXXXXXX or +8801XXXXXXXXX.');
    });

    it('returns 501 for a valid payload', function (string $phone): void {
        $this->postJson('/api/v1/auth/register', validRegistration(['phone' => $phone]))
            ->assertStatus(501)
            ->assertExactJsonStructure(ERROR_KEYS)
            ->assertJsonPath('code', 'not_implemented');
    })->with(['01712345678', '+8801712345678']);

    it('never echoes the password back', function (): void {
        $response = $this->postJson('/api/v1/auth/register', validRegistration(['email' => 'bad']));

        expect($response->getContent())->not->toContain('correct-horse-battery');
    });
});

describe('POST /api/v1/auth/login', function (): void {
    it('returns 422 when the body is empty', function (): void {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('returns 501 for a valid payload', function (): void {
        $this->postJson('/api/v1/auth/login', ['email' => 'rahim@example.com', 'password' => 'secret'])
            ->assertStatus(501)
            ->assertJsonPath('code', 'not_implemented');
    });
});

it('returns 501 for logout and me', function (string $method, string $uri): void {
    $this->json($method, $uri)
        ->assertStatus(501)
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'not_implemented');
})->with([
    ['POST', '/api/v1/auth/logout'],
    ['GET', '/api/v1/auth/me'],
]);

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
