<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
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
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN, Rule::unique('users', 'phone')],
            // bcrypt only uses the first 72 bytes.
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
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

    /**
     * Normalise before validating, so "unique" compares stored values:
     * email in lower case, phone as +8801XXXXXXXXX.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');

        $this->merge(array_filter([
            'email' => is_string($email) ? mb_strtolower($email) : null,
            'phone' => is_string($phone) && preg_match('/^01\d{9}$/', $phone) === 1 ? '+88'.$phone : null,
        ], fn ($value): bool => $value !== null));
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
