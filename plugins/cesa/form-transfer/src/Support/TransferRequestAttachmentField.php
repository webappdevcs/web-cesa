<?php

namespace Cesa\FormTransfer\Support;

use Filament\Forms\Components\FileUpload;

class TransferRequestAttachmentField
{
    public static function makeInvoice(string $name = 'invoice_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::app.fields.invoice'))
            ->directory('form-transfer/invoices')
            ->visibility('private')
            ->multiple()
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->maxSize(5120)
            ->helperText(__('form-transfer::app.helpers.invoice_upload'));
    }

    public static function makeAccountAttachment(string $name = 'account_attachment_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::app.fields.account_attachment'))
            ->directory('form-transfer/account-attachments')
            ->visibility('private')
            ->multiple()
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
            ])
            ->maxSize(5120)
            ->helperText(__('form-transfer::app.helpers.account_attachment_upload'));
    }

    public static function makeRealizationProof(string $name = 'realization_proof_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('form-transfer::app.fields.realization_proof'))
            ->directory('form-transfer/realizations')
            ->visibility('private')
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
            ])
            ->maxSize(5120)
            ->helperText(__('form-transfer::app.helpers.realization_proof_upload'));
    }
}
