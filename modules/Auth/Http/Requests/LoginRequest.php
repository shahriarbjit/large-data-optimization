<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Auth\DTOs\LoginData;
use Modules\Shared\Http\Requests\BaseFormRequest;

final class LoginRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): LoginData
    {
        return new LoginData(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
