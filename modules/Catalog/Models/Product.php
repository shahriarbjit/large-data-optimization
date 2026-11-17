<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\ProductFactory;
use Modules\Catalog\DTOs\ProductData;

/**
 * Internal to the Catalog module.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $description
 * @property string $brand
 * @property string $category
 * @property string $color
 * @property list<string> $tags
 * @property string $price decimal string, e.g. "1250.00"
 * @property string $currency
 * @property int $stock
 * @property string $rating decimal string, e.g. "4.5"
 * @property int $reviews_count
 * @property string $image image URL
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'price' => 'decimal:2',
            'rating' => 'decimal:1',
            'stock' => 'integer',
            'reviews_count' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function toData(): ProductData
    {
        return new ProductData(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            brand: $this->brand,
            category: $this->category,
            color: $this->color,
            tags: $this->tags,
            price: $this->price,
            currency: $this->currency,
            stock: $this->stock,
            rating: $this->rating,
            reviewsCount: $this->reviews_count,
            image: $this->image,
            isActive: $this->is_active,
            createdAt: $this->created_at,
            updatedAt: $this->updated_at,
        );
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
