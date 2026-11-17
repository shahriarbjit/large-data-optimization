<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Database\Seeders\ProductRowGenerator;
use Modules\Catalog\Models\Product;

/**
 * For tests: the same realistic data as the seeder.
 *
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /** @var class-string<Product> */
    protected $model = Product::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = (new ProductRowGenerator)->row(++self::$sequence, 1_000);
        $row['tags'] = json_decode((string) $row['tags'], true);

        return $row;
    }
}
