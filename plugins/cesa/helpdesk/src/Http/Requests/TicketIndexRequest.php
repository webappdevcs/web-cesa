<?php

namespace Cesa\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'box'                 => ['nullable', 'in:incoming,outgoing,all'],
            'search'              => ['nullable', 'string', 'max:255'],
            'priority_id'         => ['nullable', 'integer', 'exists:helpdesk_priorities,id'],
            'ticket_status_id'    => ['nullable', 'integer', 'exists:helpdesk_ticket_statuses,id'],
            'unit_id'             => ['nullable', 'integer', 'exists:helpdesk_units,id'],
            'problem_category_id' => ['nullable', 'integer', 'exists:helpdesk_problem_categories,id'],
            'responsible_id'      => ['nullable', 'integer', 'exists:users,id'],
            'per_page'            => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'box.in'                      => 'Kotak tiket tidak valid.',
            'priority_id.exists'          => 'Prioritas yang dipilih tidak tersedia.',
            'ticket_status_id.exists'     => 'Status tiket yang dipilih tidak tersedia.',
            'unit_id.exists'              => 'Unit yang dipilih tidak tersedia.',
            'problem_category_id.exists'  => 'Kategori masalah yang dipilih tidak tersedia.',
            'responsible_id.exists'       => 'Penanggung jawab yang dipilih tidak tersedia.',
            'per_page.max'                => 'Jumlah data per halaman maksimal 100.',
        ];
    }
}
