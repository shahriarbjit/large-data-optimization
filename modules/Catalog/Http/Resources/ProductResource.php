<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\DTOs\ProductData;

/**
 * Response shape of a product:
 *
 *   { "id": 7, "sku": "SKU-0000000007", "name": "...", "description": "...",
 *     "brand": "Padma", "category": "Men Clothing", "color": "Navy",
 *     "tags": ["cotton", "eid"], "price": "2459.00", "currency": "BDT",
 *     "stock": 120, "rating": 4.3, "reviews_count": 812,
 *     "image": "https://...", "is_active": true,
 *     "created_at": "2026-01-02T03:04:05Z", "updated_at": "2026-01-02T03:04:05Z" }
 *
 * @property ProductData $resource
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'sku' => $this->resource->sku,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'brand' => $this->resource->brand,
            'category' => $this->resource->category,
            'color' => $this->resource->color,
            'tags' => $this->resource->tags,
            'price' => $this->resource->price,
            'currency' => $this->resource->currency,
            'stock' => $this->resource->stock,
            'rating' => (float) $this->resource->rating,
            'reviews_count' => $this->resource->reviewsCount,
            'image' => $this->resource->image,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt->toIso8601ZuluString(),
            'updated_at' => $this->resource->updatedAt->toIso8601ZuluString(),
        ];
    }
}
