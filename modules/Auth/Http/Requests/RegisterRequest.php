<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Auth\DTOs\RegisterData;
use Modules\Shared\Http\Requests\BaseFormRequest;

final class RegisterRequest extends BaseFormRequest
{
    /** Bangladesh mobile number: 01XXXXXXXXX or +8801XXXXXXXXX. */
    public const string PHONE_PATTERN = '/^(?:\+880|0)1\d{9}$/';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            // TODO(db): unique email
            'email' => ['required', 'string', 'email', 'max:190'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone must be a Bangladesh mobile number: 01XXXXXXXXX or +8801XXXXXXXXX.',
        ];
    }

    public function toData(): RegisterData
    {
        return new RegisterData(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            phone: $this->string('phone')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
