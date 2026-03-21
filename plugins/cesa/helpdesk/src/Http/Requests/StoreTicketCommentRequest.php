<?php

namespace Cesa\Helpdesk\Http\Requests;

use Cesa\Helpdesk\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'comment'          => ['required', 'string'],
            'visibility'       => ['sometimes', 'string', 'in:'.implode(',', [
                Comment::VISIBILITY_PUBLIC,
                Comment::VISIBILITY_INTERNAL,
            ])],
            'attachments'      => ['sometimes', 'array', 'max:'.config('helpdesk.attachments.comment.max_files')],
            'attachments.*'    => [
                'file',
                'mimes:'.$this->allowedAttachmentExtensions('comment'),
                'max:'.config('helpdesk.attachments.comment.max_size'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required'    => 'Komentar wajib diisi.',
            'visibility.in'       => 'Tipe komentar yang dipilih tidak valid.',
            'attachments.max'     => 'Lampiran komentar melebihi batas jumlah file.',
            'attachments.*.file'  => 'Lampiran komentar harus berupa file.',
            'attachments.*.mimes' => 'Tipe file lampiran komentar tidak didukung.',
            'attachments.*.max'   => 'Ukuran lampiran komentar melebihi batas maksimum.',
        ];
    }

    protected function allowedAttachmentExtensions(string $scope): string
    {
        return implode(',', config("helpdesk.attachments.{$scope}.allowed_extensions", []));
    }
}
