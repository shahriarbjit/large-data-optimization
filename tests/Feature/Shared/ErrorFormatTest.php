<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Exceptions\ConflictException;
use Modules\Shared\Exceptions\DomainRuleException;
use Modules\Shared\Exceptions\ExceptionRenderer;
use Modules\Shared\Exceptions\NotFoundException;
use Modules\Shared\Exceptions\NotImplementedException;

/**
 * Registers a throw-away route that throws $e. Not part of route:list.
 */
function routeThatThrows(Throwable $e): string
{
    Route::get('/api/v1/_test/throws', fn () => throw $e);

    return '/api/v1/_test/throws';
}

it('returns 404 in the standard format for an unknown API route', function (): void {
    $this->getJson('/api/v1/nope')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('status', 404)
        ->assertJsonPath('code', 'not_found')
        ->assertJsonPath('type', 'urn:urban-nest:error:not_found');
});

it('has no web layer: GET / is a 404 in the standard format', function (): void {
    $this->get('/')
        ->assertNotFound()
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'not_found');
});

it('does not serve local disk files: GET /storage/anything is a 404 in the standard format', function (): void {
    $this->get('/storage/anything')
        ->assertNotFound()
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'not_found');
});

it('returns 405 in the standard format for a wrong method', function (): void {
    $this->postJson('/api/v1/health')
        ->assertMethodNotAllowed()
        ->assertHeader('Allow')
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('status', 405)
        ->assertJsonPath('code', 'method_not_allowed');
});

it('returns JSON even when the client asks for HTML', function (): void {
    $this->get('/api/v1/nope', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'not_found');
});

it('renders ApiException subclasses with their status and code', function (Throwable $e, int $status, string $code): void {
    $this->getJson(routeThatThrows($e))
        ->assertStatus($status)
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('status', $status)
        ->assertJsonPath('code', $code);
})->with([
    'not implemented' => [new NotImplementedException, 501, 'not_implemented'],
    'not found' => [new NotFoundException, 404, 'not_found'],
    'not found, custom code' => [new NotFoundException('Order 7 was not found.', 'order_not_found'), 404, 'order_not_found'],
    'conflict' => [new ConflictException, 409, 'conflict'],
    'domain rule' => [new DomainRuleException('Only 2 left.', 'insufficient_stock'), 422, 'insufficient_stock'],
]);

it('uses the exception message as detail', function (): void {
    $this->getJson(routeThatThrows(new ConflictException('Order is already shipped.')))
        ->assertJsonPath('detail', 'Order is already shipped.')
        ->assertJsonPath('title', 'Conflict');
});

it('returns 422 with field errors for validation failures', function (): void {
    $this->getJson(routeThatThrows(ValidationException::withMessages(['email' => ['The email field is required.']])))
        ->assertUnprocessable()
        ->assertExactJsonStructure([...ERROR_KEYS, 'errors' => ['email']])
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.email.0', 'The email field is required.');
});

it('returns 401 in the standard format', function (): void {
    $this->getJson(routeThatThrows(new AuthenticationException))
        ->assertUnauthorized()
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'unauthenticated');
});

it('returns 403 in the standard format', function (): void {
    $this->getJson(routeThatThrows(new AuthorizationException))
        ->assertForbidden()
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'forbidden');
});

it('hides internals of unexpected errors when debug is off', function (): void {
    config(['app.debug' => false]);
    $this->app->forgetInstance(ExceptionRenderer::class);

    $response = $this->getJson(routeThatThrows(new RuntimeException('SQLSTATE[42S02]: secret table')))
        ->assertStatus(500)
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'server_error')
        ->assertJsonPath('detail', 'An unexpected error occurred.');

    expect($response->getContent())->not->toContain('SQLSTATE')->not->toContain('trace');
});

it('adds debug details to unexpected errors when debug is on', function (): void {
    config(['app.debug' => true]);
    $this->app->forgetInstance(ExceptionRenderer::class);

    $this->getJson(routeThatThrows(new RuntimeException('boom')))
        ->assertStatus(500)
        ->assertJsonPath('detail', 'boom')
        ->assertJsonPath('debug.exception', RuntimeException::class)
        ->assertJsonStructure(['debug' => ['file', 'line', 'trace']]);
});

it('returns 429 in the standard format when the api rate limit is exceeded', function (): void {
    config(['api.rate_limits.api' => 2]);

    $this->getJson('/api/v1/health')->assertOk();
    $this->getJson('/api/v1/health')->assertOk();

    $this->getJson('/api/v1/health')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertExactJsonStructure(ERROR_KEYS)
        ->assertJsonPath('code', 'rate_limited');
});
