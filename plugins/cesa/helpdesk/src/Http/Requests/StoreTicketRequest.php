<?php

namespace Cesa\Helpdesk\Http\Requests;

use Cesa\Helpdesk\Support\TicketOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Webkul\Security\Models\User;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'priority_id'               => ['required', 'integer', 'exists:helpdesk_priorities,id'],
            'unit_id'                   => ['required', 'integer', 'exists:helpdesk_units,id'],
            'problem_category_id'       => ['required', 'integer', 'exists:helpdesk_problem_categories,id'],
            'company_id'                => ['nullable', 'integer', 'exists:companies,id'],
            'title'                     => ['required', 'string', 'max:255'],
            'description'               => ['required', 'string'],
            'supporting_attachments'    => ['sometimes', 'array', 'max:'.config('helpdesk.attachments.ticket.max_files')],
            'supporting_attachments.*'  => [
                'file',
                'mimes:'.$this->allowedAttachmentExtensions('ticket'),
                'max:'.config('helpdesk.attachments.ticket.max_size'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'priority_id.required'               => 'Prioritas wajib dipilih.',
            'priority_id.exists'                 => 'Prioritas yang dipilih tidak tersedia.',
            'unit_id.required'                   => 'Unit wajib dipilih.',
            'unit_id.exists'                     => 'Unit yang dipilih tidak tersedia.',
            'problem_category_id.required'       => 'Kategori masalah wajib dipilih.',
            'problem_category_id.exists'         => 'Kategori masalah yang dipilih tidak tersedia.',
            'company_id.exists'                  => 'Perusahaan yang dipilih tidak tersedia.',
            'title.required'                     => 'Judul tiket wajib diisi.',
            'description.required'               => 'Deskripsi tiket wajib diisi.',
            'supporting_attachments.max'         => 'Lampiran tiket melebihi batas jumlah file.',
            'supporting_attachments.*.file'      => 'Lampiran tiket harus berupa file.',
            'supporting_attachments.*.mimes'     => 'Tipe file lampiran tiket tidak didukung.',
            'supporting_attachments.*.max'       => 'Ukuran lampiran tiket melebihi batas maksimum.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unitId = (int) $this->input('unit_id');
            $problemCategoryId = (int) $this->input('problem_category_id');

            if ($unitId && $problemCategoryId) {
                $matchesUnit = \Cesa\Helpdesk\Models\ProblemCategory::query()
                    ->whereKey($problemCategoryId)
                    ->where('unit_id', $unitId)
                    ->exists();

                if (! $matchesUnit) {
                    $validator->errors()->add('problem_category_id', 'Kategori masalah harus sesuai dengan unit yang dipilih.');
                }
            }

            $user = $this->user();

            if ($user instanceof User && $this->filled('company_id')) {
                $allowedCompanyIds = TicketOptions::companyIdsForUser($user);

                if (! in_array((int) $this->input('company_id'), $allowedCompanyIds, true)) {
                    $validator->errors()->add('company_id', 'Perusahaan yang dipilih tidak termasuk perusahaan yang dapat Anda gunakan.');
                }
            }
        });
    }

    protected function allowedAttachmentExtensions(string $scope): string
    {
        return implode(',', config("helpdesk.attachments.{$scope}.allowed_extensions", []));
    }
}
