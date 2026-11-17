<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Carbon\CarbonImmutable;

/**
 * A product as other modules see it.
 */
final readonly class ProductData
{
    /**
     * @param  list<string>  $tags
     * @param  string  $price  decimal string with two decimals, e.g. "1250.00"
     * @param  string  $rating  decimal string with one decimal, e.g. "4.5"
     */
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public string $description,
        public string $brand,
        public string $category,
        public string $color,
        public array $tags,
        public string $price,
        public string $currency,
        public int $stock,
        public string $rating,
        public int $reviewsCount,
        public string $image,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
