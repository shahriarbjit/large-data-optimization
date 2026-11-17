<?php

declare(strict_types=1);

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Catalog\Contracts\ProductCatalogContract;
use Modules\Catalog\DTOs\ProductData;
use Modules\Catalog\Models\Product;
use Modules\Shared\Exceptions\NotFoundException;

/**
 * Reads products from MySQL. Cursor pagination on the primary key keeps
 * every page fast on 10 million rows (no COUNT, no deep OFFSET).
 */
final class ProductCatalogService implements ProductCatalogContract
{
    public function paginate(int $perPage, ?string $cursor): CursorPaginator
    {
        return Product::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
            ->withQueryString()
            ->through(fn (Product $product): ProductData => $product->toData());
    }

    public function find(int $id): ProductData
    {
        $product = Product::query()->whereKey($id)->where('is_active', true)->first();

        if (! $product instanceof Product) {
            throw new NotFoundException('The product was not found.', 'product_not_found');
        }

        return $product->toData();
    }
}
