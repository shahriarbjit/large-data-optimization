<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Modules\Catalog\Contracts\ProductCatalogContract;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Shared\Http\Requests\CursorListRequest;
use Modules\Shared\Http\Resources\CursorPaginatedCollection;

/**
 * Public, read-only product endpoints. Search comes later (Elasticsearch).
 */
final class ProductController
{
    public function __construct(
        private readonly ProductCatalogContract $catalog,
    ) {}

    /** GET /api/v1/products?per_page=20&cursor=... */
    public function index(CursorListRequest $request): CursorPaginatedCollection
    {
        return new CursorPaginatedCollection(
            $this->catalog->paginate($request->perPage(), $request->cursor()),
            ProductResource::class,
        );
    }

    /** GET /api/v1/products/{product} */
    public function show(int $product): ProductResource
    {
        return new ProductResource($this->catalog->find($product));
    }
}
