<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for every FormRequest in the modules.
 *
 * - Validation failures throw ValidationException, which ExceptionRenderer
 *   turns into a 422 with "errors". Do not override failedValidation().
 * - Each request builds its DTO in a toData() method, so controllers never
 *   pass raw arrays to services.
 * - Never use rules that query the database (unique, exists) in this
 *   template; they come with the first migration.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * TODO(auth): return a Policy check once authentication exists.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;
}
