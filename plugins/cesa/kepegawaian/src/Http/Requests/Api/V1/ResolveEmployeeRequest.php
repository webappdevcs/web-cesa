<?php

namespace Cesa\Kepegawaian\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ResolveEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'source_system'   => ['required', 'string', 'max:64'],
            'source_instance' => ['required', 'string', 'max:191'],
            'identifier_type' => ['required', 'string', 'max:64'],
            'external_id'     => ['required', 'string', 'max:191'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_system'   => $this->normalizedKey('source_system'),
            'source_instance' => $this->normalizedKey('source_instance'),
            'identifier_type' => $this->normalizedKey('identifier_type'),
            'external_id'     => $this->normalizedKey('external_id'),
        ]);
    }

    private function normalizedKey(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        return Str::lower(Str::squish($value));
    }
}
