<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Catalog\DTOs\ProductData;

/**
 * Public API of the Catalog module. Only active products are visible.
 */
interface ProductCatalogContract
{
    /**
     * Active products, newest first, cursor-paginated.
     *
     * @return CursorPaginator<int, ProductData>
     */
    public function paginate(int $perPage, ?string $cursor): CursorPaginator;

    /**
     * An unknown or inactive product answers 404 with code "product_not_found".
     */
    public function find(int $id): ProductData;
}
