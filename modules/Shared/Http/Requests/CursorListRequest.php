<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Requests;

/**
 * Query string of a cursor-paginated list: ?per_page=20&cursor=...
 * per_page defaults to config api.pagination.per_page and is capped at
 * api.pagination.max_per_page (larger values are a 422).
 */
final class CursorListRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config()->integer('api.pagination.max_per_page')],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', config()->integer('api.pagination.per_page'));
    }

    public function cursor(): ?string
    {
        $cursor = $this->query('cursor');

        return is_string($cursor) && $cursor !== '' ? $cursor : null;
    }
}
