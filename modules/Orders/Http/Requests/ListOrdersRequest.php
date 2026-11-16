<?php

declare(strict_types=1);

namespace Modules\Orders\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Orders\DTOs\ListOrdersData;
use Modules\Orders\DTOs\OrderChannel;
use Modules\Orders\DTOs\OrderStatus;
use Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * GET /api/v1/orders?page=1&per_page=20&sort=-placed_at&filter[status]=paid
 */
final class ListOrdersRequest extends BaseFormRequest
{
    /** Sortable fields. A leading "-" means descending. */
    public const array SORTABLE = ['placed_at', '-placed_at', 'total', '-total'];

    public const string DEFAULT_SORT = '-placed_at';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config()->integer('api.pagination.max_per_page')],
            'sort' => ['sometimes', 'string', Rule::in(self::SORTABLE)],
            'filter' => ['sometimes', 'array:status,channel'],
            'filter.status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'filter.channel' => ['sometimes', Rule::enum(OrderChannel::class)],
        ];
    }

    public function toData(): ListOrdersData
    {
        return new ListOrdersData(
            page: $this->integer('page', 1),
            perPage: $this->integer('per_page', config()->integer('api.pagination.per_page')),
            sort: $this->string('sort', self::DEFAULT_SORT)->toString(),
            status: $this->enum('filter.status', OrderStatus::class),
            channel: $this->enum('filter.channel', OrderChannel::class),
        );
    }
}
