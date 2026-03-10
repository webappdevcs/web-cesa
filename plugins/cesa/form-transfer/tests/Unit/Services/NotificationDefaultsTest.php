<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class NotificationDefaultsTest extends FormTransferTestCase
{
    public function test_notification_defaults_are_populated_for_create_form(): void
    {
        $defaults = FormTransferResource::getDefaultNotificationData();

        $this->assertSame(
            TransferApprovalNotificationService::getDefaultApproverMailSubject(),
            $defaults['approver_mail_subject']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultApproverMailTemplate(),
            $defaults['approver_mail_template']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultRequesterMailSubject(),
            $defaults['requester_mail_subject']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultRequesterMailTemplate(),
            $defaults['requester_mail_template']
        );

        foreach ($defaults as $value) {
            $this->assertNotSame('', trim($value));
        }
    }
}
