<?php

namespace Cesa\FormTransfer\Http\Requests;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransferRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceFiles = $this->file('invoice_path');
        $accountAttachmentFiles = $this->file('account_attachment_path');

        $invoiceRule = is_array($invoiceFiles)
            ? ['nullable', 'array']
            : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,xls,xlsx', 'max:5120'];

        $accountAttachmentRule = is_array($accountAttachmentFiles)
            ? ['nullable', 'array']
            : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'form_transfer_id'         => ['required', 'exists:form_transfers,id'],
            'requester_name'           => ['required', 'string', 'max:191'],
            'division_id'              => ['nullable', 'exists:form_transfer_divisions,id'],
            'division_name'            => ['nullable', 'string', 'max:191'],
            'email'                    => ['nullable', 'email', 'max:191'],
            'bank_id'                  => ['required', 'exists:form_transfer_banks,id'],
            'account_number'           => ['required', 'string', 'max:191'],
            'account_name'             => ['required', 'string', 'max:191'],
            'transfer_amount'          => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'purpose'                  => ['nullable', 'string', 'max:1000'],
            'reference_note_id'        => ['nullable', 'exists:form_transfer_reference_notes,id'],
            'reference_note'           => ['nullable', 'string', 'max:1000'],
            'invoice_path'             => $invoiceRule,
            'invoice_path.*'           => is_array($invoiceFiles)
                ? ['file', 'mimes:pdf,jpg,jpeg,png,xls,xlsx', 'max:5120']
                : [],
            'account_attachment_path'   => $accountAttachmentRule,
            'account_attachment_path.*' => is_array($accountAttachmentFiles)
                ? ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']
                : [],
            'approval_workflow_id'     => ['nullable', 'exists:form_transfer_approval_workflows,id'],
            'user_id'                  => ['nullable', 'exists:users,id'],
            'submission_status'        => ['required', Rule::enum(TransferRequestSubmissionStatus::class)],
            'approval_status'          => ['required', Rule::enum(TransferRequestApprovalStatus::class)],
            'realization_status'       => ['required', Rule::enum(TransferRequestRealizationStatus::class)],
            'realization_notes'        => ['nullable', 'string'],
            'approvals'                => ['nullable', 'array'],
            'approvals.*.label'        => ['required_with:approvals', 'string', 'max:191'],
            'approvals.*.name'         => ['nullable', 'string', 'max:191'],
            'approvals.*.email'        => ['nullable', 'email', 'max:191'],
            'approvals.*.status'       => [
                'required_with:approvals',
                Rule::in(array_map(fn (ApprovalStatus $status): string => $status->value, ApprovalStatus::cases())),
            ],
            'approvals.*.noted_at'     => ['nullable', 'date'],
            'approvals.*.notes'        => ['nullable', 'string', 'max:1000'],
            'approvals.*.is_mandatory' => ['boolean'],
            'realized_at'              => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'form_transfer_id.required'        => __('form-transfer::app.validation.form_transfer_required'),
            'form_transfer_id.exists'          => __('form-transfer::app.validation.form_transfer_invalid'),
            'requester_name.required'          => __('form-transfer::app.validation.requester_name_required'),
            'account_number.required'          => __('form-transfer::app.validation.account_number_required'),
            'account_name.required'            => __('form-transfer::app.validation.account_name_required'),
            'transfer_amount.required'         => __('form-transfer::app.validation.transfer_amount_required'),
            'transfer_amount.numeric'          => __('form-transfer::app.validation.transfer_amount_numeric'),
            'transfer_amount.min'              => __('form-transfer::app.validation.transfer_amount_min'),
            'transfer_amount.max'              => __('form-transfer::app.validation.transfer_amount_max'),
            'division_id.exists'               => __('form-transfer::app.validation.division_invalid'),
            'bank_id.exists'                   => __('form-transfer::app.validation.bank_invalid'),
            'reference_note_id.exists'         => __('form-transfer::app.validation.reference_note_invalid'),
            'approval_workflow_id.exists'      => __('form-transfer::app.validation.workflow_invalid'),
            'user_id.exists'                   => __('form-transfer::app.validation.user_invalid'),
            'invoice_path.file'                => __('form-transfer::app.validation.invoice_file'),
            'invoice_path.mimes'               => __('form-transfer::app.validation.invoice_mimes'),
            'invoice_path.max'                 => __('form-transfer::app.validation.invoice_max_size'),
            'invoice_path.*.file'              => __('form-transfer::app.validation.invoice_file'),
            'invoice_path.*.mimes'             => __('form-transfer::app.validation.invoice_mimes'),
            'invoice_path.*.max'               => __('form-transfer::app.validation.invoice_max_size'),
            'account_attachment_path.file'     => __('form-transfer::app.validation.account_attachment_file'),
            'account_attachment_path.mimes'    => __('form-transfer::app.validation.account_attachment_mimes'),
            'account_attachment_path.max'      => __('form-transfer::app.validation.account_attachment_max_size'),
            'account_attachment_path.*.file'   => __('form-transfer::app.validation.account_attachment_file'),
            'account_attachment_path.*.mimes'  => __('form-transfer::app.validation.account_attachment_mimes'),
            'account_attachment_path.*.max'    => __('form-transfer::app.validation.account_attachment_max_size'),
            'approvals.*.label.required_with'  => __('form-transfer::app.validation.approval_label_required'),
            'approvals.*.status.required_with' => __('form-transfer::app.validation.approval_status_required'),
            'approvals.*.status.in'            => __('form-transfer::app.validation.approval_status_invalid'),
            'approvals.*.email.email'          => __('form-transfer::app.validation.approver_email_invalid'),
            'submission_status.required'       => __('form-transfer::app.validation.status_required'),
            'submission_status.enum'           => __('form-transfer::app.validation.status_invalid'),
            'approval_status.required'         => __('form-transfer::app.validation.status_required'),
            'approval_status.enum'             => __('form-transfer::app.validation.status_invalid'),
            'realization_status.required'      => __('form-transfer::app.validation.status_required'),
            'realization_status.enum'          => __('form-transfer::app.validation.status_invalid'),
        ];
    }
}
