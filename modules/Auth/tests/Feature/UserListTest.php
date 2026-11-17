<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

describe('GET /api/v1/users', function (): void {
    it('needs a token', function (): void {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    });

    it('is forbidden for users and guests', function (User $user): void {
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    })->with([
        'user' => fn (): User => User::factory()->create(),
        'guest' => fn (): User => User::factory()->guest()->create(),
    ]);

    it('lists users newest first for admins, without passwords', function (): void {
        User::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->getJson('/api/v1/users?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'phone', 'role', 'created_at']],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor'],
            ])
            ->assertJsonMissingPath('data.0.password');

        $ids = User::query()->orderByDesc('id')->pluck('id')->all();
        expect($response->json('data.*.id'))->toBe(array_slice($ids, 0, 2));

        $this->getJson('/api/v1/users?per_page=2&cursor='.$response->json('meta.next_cursor'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $ids[2]);
    });
});
