<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Database\Seeders\ProductRowGenerator;
use Modules\Catalog\Database\Seeders\ProductSeeder;
use Modules\Catalog\Models\Product;

uses(RefreshDatabase::class);

it('seeds products with realistic data and every column filled', function (): void {
    config(['seed.products' => 50, 'seed.products_chunk' => 20]);

    $this->seed(ProductSeeder::class);

    expect(Product::query()->count())->toBe(50);

    $product = Product::query()->where('sku', 'SKU-0000000007')->sole();

    expect($product->name)->toContain($product->brand)
        ->and($product->description)->not->toBeEmpty()
        ->and($product->tags)->toBeArray()->not->toBeEmpty()
        ->and($product->price)->toMatch('/^\d+\.\d{2}$/')
        ->and($product->currency)->toBe('BDT')
        ->and((float) $product->rating)->toBeBetween(1.0, 5.0)
        ->and($product->image)->toBe('https://picsum.photos/seed/urban-nest-7/640/640')
        ->and($product->updated_at->greaterThanOrEqualTo($product->created_at))->toBeTrue();
});

it('resumes where it stopped and never duplicates', function (): void {
    config(['seed.products' => 30, 'seed.products_chunk' => 7]);
    $this->seed(ProductSeeder::class);

    config(['seed.products' => 45]);
    $this->seed(ProductSeeder::class);
    $this->seed(ProductSeeder::class);

    expect(Product::query()->count())->toBe(45)
        ->and(Product::query()->distinct()->count('sku'))->toBe(45)
        ->and(Product::query()->max('sku'))->toBe('SKU-0000000045');
});

it('generates the same row for the same number', function (): void {
    $generator = new ProductRowGenerator;

    expect($generator->row(123, 1000))->toBe($generator->row(123, 1000))
        ->and($generator->row(123, 1000)['name'])->not->toBe($generator->row(124, 1000)['name']);
});

it('spreads creation dates over time, oldest first', function (): void {
    $generator = new ProductRowGenerator;

    expect($generator->row(1, 1000)['created_at'])->toBeLessThan($generator->row(1000, 1000)['created_at']);
});

it('makes products usable through the model factory', function (): void {
    $product = Product::factory()->create();

    expect($product->fresh()?->tags)->toBeArray();
});

it('refuses to seed in production', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->app->make(ProductSeeder::class)->run();
})->throws(RuntimeException::class);
