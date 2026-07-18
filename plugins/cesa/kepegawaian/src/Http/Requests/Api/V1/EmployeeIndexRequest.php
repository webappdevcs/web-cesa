<?php

namespace Cesa\Kepegawaian\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'filter'               => ['sometimes', 'array'],
            'filter.employee_code' => ['sometimes', 'string', 'max:255'],
            'filter.name'          => ['sometimes', 'string', 'max:255'],
            'filter.company_id'    => ['sometimes', 'integer', 'min:1'],
            'filter.is_active'     => ['sometimes', 'boolean'],
            'per_page'             => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
