<?php

namespace Cesa\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'exists:helpdesk_units,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.exists' => 'Unit yang dipilih tidak tersedia.',
        ];
    }
}
