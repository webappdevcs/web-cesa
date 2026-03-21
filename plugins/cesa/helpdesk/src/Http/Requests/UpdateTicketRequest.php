<?php

namespace Cesa\Helpdesk\Http\Requests;

use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Support\TicketOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Webkul\Security\Models\User;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'priority_id'                       => ['sometimes', 'integer', 'exists:helpdesk_priorities,id'],
            'unit_id'                           => ['sometimes', 'integer', 'exists:helpdesk_units,id'],
            'problem_category_id'               => ['sometimes', 'integer', 'exists:helpdesk_problem_categories,id'],
            'company_id'                        => ['nullable', 'integer', 'exists:companies,id'],
            'title'                             => ['sometimes', 'string', 'max:255'],
            'description'                       => ['sometimes', 'string'],
            'ticket_status_id'                  => ['sometimes', 'integer', 'exists:helpdesk_ticket_statuses,id'],
            'responsible_id'                    => ['nullable', 'integer', 'exists:users,id'],
            'close_reason'                      => ['sometimes', 'nullable', 'string'],
            'cancel_reason'                     => ['sometimes', 'nullable', 'string'],
            'reopen_reason'                     => ['sometimes', 'nullable', 'string'],
            'existing_supporting_attachments'   => ['sometimes', 'array'],
            'existing_supporting_attachments.*' => ['string'],
            'supporting_attachments'            => ['sometimes', 'array', 'max:'.config('helpdesk.attachments.ticket.max_files')],
            'supporting_attachments.*'          => [
                'file',
                'mimes:'.$this->allowedAttachmentExtensions('ticket'),
                'max:'.config('helpdesk.attachments.ticket.max_size'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'priority_id.exists'                    => 'Prioritas yang dipilih tidak tersedia.',
            'unit_id.exists'                        => 'Unit yang dipilih tidak tersedia.',
            'problem_category_id.exists'            => 'Kategori masalah yang dipilih tidak tersedia.',
            'company_id.exists'                     => 'Perusahaan yang dipilih tidak tersedia.',
            'ticket_status_id.exists'               => 'Status tiket yang dipilih tidak tersedia.',
            'responsible_id.exists'                 => 'Penanggung jawab yang dipilih tidak tersedia.',
            'close_reason.string'                   => 'Alasan penutupan tiket harus berupa teks.',
            'cancel_reason.string'                  => 'Alasan pembatalan tiket harus berupa teks.',
            'reopen_reason.string'                  => 'Alasan membuka kembali tiket harus berupa teks.',
            'supporting_attachments.max'            => 'Lampiran tiket melebihi batas jumlah file.',
            'supporting_attachments.*.file'         => 'Lampiran tiket harus berupa file.',
            'supporting_attachments.*.mimes'        => 'Tipe file lampiran tiket tidak didukung.',
            'supporting_attachments.*.max'          => 'Ukuran lampiran tiket melebihi batas maksimum.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ticket = $this->resolveTicket();

            $unitId = (int) ($this->input('unit_id', $ticket?->unit_id));
            $problemCategoryId = (int) ($this->input('problem_category_id', $ticket?->problem_category_id));

            if ($unitId && $problemCategoryId) {
                $matchesUnit = ProblemCategory::query()
                    ->whereKey($problemCategoryId)
                    ->where('unit_id', $unitId)
                    ->exists();

                if (! $matchesUnit) {
                    $validator->errors()->add('problem_category_id', 'Kategori masalah harus sesuai dengan unit yang dipilih.');
                }
            }

            if ($this->filled('responsible_id')) {
                $allowedResponsibleIds = array_keys(TicketOptions::unitUserOptions($unitId));

                if (! in_array((int) $this->input('responsible_id'), $allowedResponsibleIds, true)) {
                    $validator->errors()->add('responsible_id', 'Penanggung jawab harus berasal dari unit yang dipilih.');
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

    protected function resolveTicket(): ?Ticket
    {
        $ticket = $this->route('ticket');

        if ($ticket instanceof Ticket) {
            return $ticket;
        }

        if (is_numeric($ticket)) {
            return Ticket::query()->find((int) $ticket);
        }

        return null;
    }

    protected function allowedAttachmentExtensions(string $scope): string
    {
        return implode(',', config("helpdesk.attachments.{$scope}.allowed_extensions", []));
    }
}
