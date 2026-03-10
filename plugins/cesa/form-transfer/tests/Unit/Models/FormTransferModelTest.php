<?php

namespace Cesa\FormTransfer\Tests\Unit\Models;

use App\Models\User;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User as SecurityUser;

class FormTransferModelTest extends FormTransferTestCase
{
    public function test_generate_next_request_uid_increments_sequence(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'TRX',
            'uid_padding'  => 4,
            'uid_sequence' => 0,
        ]);

        $firstUid = $formTransfer->generateNextRequestUid();
        $secondUid = $formTransfer->generateNextRequestUid();

        $this->assertSame('TRX-0001', $firstUid);
        $this->assertSame('TRX-0002', $secondUid);
        $this->assertSame(2, $formTransfer->fresh()->uid_sequence);
    }

    public function test_has_custom_notification_templates_detects_any_filled_template(): void
    {
        $withoutTemplate = FormTransfer::factory()->make([
            'approver_mail_subject'      => null,
            'approver_mail_greeting'     => null,
            'approver_mail_action_text'  => null,
            'approver_mail_template'     => null,
            'requester_mail_subject'     => null,
            'requester_mail_greeting'    => null,
            'requester_mail_action_text' => null,
            'requester_mail_template'    => null,
            'approver_whatsapp_template' => null,
        ]);

        $withTemplate = FormTransfer::factory()->make([
            'approver_mail_subject' => 'Subject exists',
        ]);

        $this->assertFalse($withoutTemplate->hasCustomNotificationTemplates());
        $this->assertTrue($withTemplate->hasCustomNotificationTemplates());
    }

    public function test_transfer_request_attachment_normalization_and_mutator_are_consistent(): void
    {
        $this->assertSame(['invoice.pdf'], TransferRequest::normalizeAttachmentPaths('invoice.pdf'));
        $this->assertSame(
            ['invoice-a.pdf', 'invoice-b.pdf'],
            TransferRequest::normalizeAttachmentPaths('["invoice-a.pdf","invoice-b.pdf"]')
        );
        $this->assertSame([], TransferRequest::normalizeAttachmentPaths(null));

        $request = new TransferRequest;
        $request->invoice_path = ['invoice-a.pdf', 'invoice-b.pdf'];
        $request->account_attachment_path = 'rekening.pdf';

        $this->assertSame('["invoice-a.pdf","invoice-b.pdf"]', $request->getAttributes()['invoice_path']);
        $this->assertSame('rekening.pdf', $request->getAttributes()['account_attachment_path']);
        $this->assertSame(['invoice-a.pdf', 'invoice-b.pdf'], $request->invoice_path);
        $this->assertSame(['rekening.pdf'], $request->account_attachment_path);
    }

    public function test_transfer_request_creation_populates_uid_and_default_statuses(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'PAY',
            'uid_padding'  => 3,
            'uid_sequence' => 0,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);

        $this->assertSame('PAY-001', $request->uid);
        $this->assertNotEmpty($request->status_response_id);
        $this->assertSame(TransferRequestSubmissionStatus::BARU, $request->submission_status);
        $this->assertSame(TransferRequestApprovalStatus::PENDING, $request->approval_status);
        $this->assertSame(TransferRequestRealizationStatus::PENDING, $request->realization_status);
    }

    public function test_transfer_request_creation_renames_uploaded_attachments_using_uid(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-b.pdf', 'invoice-b');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account.pdf', 'account');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => [
                'form-transfer/invoices/tmp-invoice-a.pdf',
                'form-transfer/invoices/tmp-invoice-b.pdf',
            ],
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account.pdf',
        ])->refresh();

        $this->assertSame('CSN-00113', $request->uid);
        $this->assertCount(2, $request->invoice_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-01-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[0]
        );
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-02-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[1]
        );
        $this->assertCount(1, $request->account_attachment_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/account-attachments/CSN-00113-[a-z0-9]{6}\.pdf$#',
            $request->account_attachment_path[0]
        );

        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-a.pdf');
        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-b.pdf');
        Storage::disk('local')->assertMissing('form-transfer/account-attachments/tmp-account.pdf');
        Storage::disk('local')->assertExists($request->invoice_path[0]);
        Storage::disk('local')->assertExists($request->invoice_path[1]);
        Storage::disk('local')->assertExists($request->account_attachment_path[0]);
    }

    public function test_transfer_request_update_renames_realization_attachment_using_uid(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/realizations/tmp-realization.png', 'proof');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);

        $request->fill([
            'realization_status'     => TransferRequestRealizationStatus::DONE,
            'realized_at'            => now()->toDateString(),
            'realization_proof_path' => 'form-transfer/realizations/tmp-realization.png',
        ]);
        $request->save();
        $request->refresh();

        $this->assertMatchesRegularExpression(
            '#^form-transfer/realizations/CSN-00113-[a-z0-9]{6}\.png$#',
            (string) $request->realization_proof_path
        );

        Storage::disk('local')->assertMissing('form-transfer/realizations/tmp-realization.png');
        Storage::disk('local')->assertExists((string) $request->realization_proof_path);
    }

    public function test_transfer_request_update_deletes_replaced_attachment_and_keeps_added_invoice(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-a.pdf', 'account-a');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => 'form-transfer/invoices/tmp-invoice-a.pdf',
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-a.pdf',
        ])->refresh();

        $existingInvoicePath = $request->invoice_path[0];
        $existingAccountPath = $request->account_attachment_path[0];

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-b.pdf', 'invoice-b');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-b.pdf', 'account-b');

        $request->fill([
            'invoice_path'            => [$existingInvoicePath, 'form-transfer/invoices/tmp-invoice-b.pdf'],
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-b.pdf',
        ]);
        $request->save();
        $request->refresh();

        $this->assertCount(2, $request->invoice_path);
        $this->assertContains($existingInvoicePath, $request->invoice_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-02-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[1]
        );
        $this->assertCount(1, $request->account_attachment_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/account-attachments/CSN-00113-[a-z0-9]{6}\.pdf$#',
            $request->account_attachment_path[0]
        );

        Storage::disk('local')->assertExists($existingInvoicePath);
        Storage::disk('local')->assertMissing($existingAccountPath);
        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-b.pdf');
        Storage::disk('local')->assertMissing('form-transfer/account-attachments/tmp-account-b.pdf');
        Storage::disk('local')->assertExists($request->invoice_path[1]);
        Storage::disk('local')->assertExists($request->account_attachment_path[0]);
    }

    public function test_transfer_request_soft_delete_keeps_attachments_until_force_delete(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-a.pdf', 'account-a');
        Storage::disk('local')->put('form-transfer/realizations/tmp-realization-a.pdf', 'realization-a');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => 'form-transfer/invoices/tmp-invoice-a.pdf',
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-a.pdf',
            'realization_proof_path'  => 'form-transfer/realizations/tmp-realization-a.pdf',
        ])->refresh();

        $storedInvoicePath = $request->invoice_path[0];
        $storedAccountPath = $request->account_attachment_path[0];
        $storedRealizationPath = (string) $request->realization_proof_path;

        $request->delete();

        Storage::disk('local')->assertExists($storedInvoicePath);
        Storage::disk('local')->assertExists($storedAccountPath);
        Storage::disk('local')->assertExists($storedRealizationPath);

        $request->forceDelete();

        Storage::disk('local')->assertMissing($storedInvoicePath);
        Storage::disk('local')->assertMissing($storedAccountPath);
        Storage::disk('local')->assertMissing($storedRealizationPath);
    }

    public function test_soft_deleted_related_records_remain_readable(): void
    {
        $formTransfer = FormTransfer::factory()->create();
        $division = TransferDivision::factory()->create([
            'form_transfer_id' => $formTransfer->id,
        ]);
        $referenceNote = TransferReferenceNote::factory()->create([
            'form_transfer_id' => $formTransfer->id,
        ]);
        $workflow = TransferApprovalWorkflow::factory()->create([
            'form_transfer_id' => $formTransfer->id,
            'division_id'      => $division->id,
        ]);
        $request = TransferRequest::factory()->create([
            'form_transfer_id' => $formTransfer->id,
            'creator_id'       => $formTransfer->creator_id,
        ]);

        $division->delete();
        $referenceNote->delete();
        $workflow->delete();
        $request->delete();
        SecurityUser::query()->findOrFail($request->creator_id)->delete();

        $freshFormTransfer = FormTransfer::query()->findOrFail($formTransfer->id);
        $freshRequest = TransferRequest::withTrashed()->findOrFail($request->id);

        $this->assertTrue($freshFormTransfer->divisions->contains('id', $division->id));
        $this->assertTrue($freshFormTransfer->referenceNotes->contains('id', $referenceNote->id));
        $this->assertTrue($freshFormTransfer->approvalWorkflows->contains('id', $workflow->id));
        $this->assertTrue($freshFormTransfer->transferRequests->contains('id', $request->id));
        $this->assertSame($request->creator_id, $freshRequest->creator?->id);
    }
}
