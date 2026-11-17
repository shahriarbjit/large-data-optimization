<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;

uses(RefreshDatabase::class);

describe('GET /api/v1/products', function (): void {
    it('lists active products newest first with cursor pagination', function (): void {
        Product::factory()->count(5)->create();
        $inactive = Product::factory()->create(['is_active' => false]);

        $first = $this->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'sku', 'name', 'description', 'brand', 'category', 'color', 'tags', 'price', 'currency', 'stock', 'rating', 'reviews_count', 'image', 'is_active', 'created_at', 'updated_at']],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor'],
                'links' => ['prev', 'next'],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.prev_cursor', null);

        $ids = Product::query()->where('is_active', true)->orderByDesc('id')->pluck('id')->all();
        expect($first->json('data.*.id'))->toBe(array_slice($ids, 0, 2));

        $second = $this->getJson('/api/v1/products?per_page=2&cursor='.$first->json('meta.next_cursor'))->assertOk();
        expect($second->json('data.*.id'))->toBe(array_slice($ids, 2, 2));

        $all = $this->getJson('/api/v1/products?per_page=100')->assertOk();
        expect($all->json('data.*.id'))->not->toContain($inactive->id)
            ->and($all->json('meta.next_cursor'))->toBeNull();
    });

    it('formats money as a string and rating as a number', function (): void {
        Product::factory()->create(['price' => '2459.5', 'rating' => '4.3']);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.price', '2459.50')
            ->assertJsonPath('data.0.currency', 'BDT')
            ->assertJsonPath('data.0.rating', 4.3);
    });

    it('rejects an out-of-range per_page', function (int|string $perPage): void {
        $this->getJson("/api/v1/products?per_page={$perPage}")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['per_page']]);
    })->with([0, 101, 'abc']);
});

describe('GET /api/v1/products/{id}', function (): void {
    it('shows one product', function (): void {
        $product = Product::factory()->create();

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku)
            ->assertJsonPath('data.image', $product->image);
    });

    it('answers 404 for an unknown or inactive product', function (): void {
        $inactive = Product::factory()->create(['is_active' => false]);

        foreach ([999_999, $inactive->id] as $id) {
            $this->getJson("/api/v1/products/{$id}")
                ->assertNotFound()
                ->assertHeader('Content-Type', 'application/problem+json')
                ->assertJsonPath('code', 'product_not_found');
        }
    });
});
