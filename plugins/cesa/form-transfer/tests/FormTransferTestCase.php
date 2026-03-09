<?php

namespace Cesa\FormTransfer\Tests;

use Cesa\FormTransfer\FormTransferServiceProvider;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Observers\TransferApprovalWorkflowObserver;
use Cesa\FormTransfer\Observers\TransferBankObserver;
use Cesa\FormTransfer\Observers\TransferDivisionObserver;
use Cesa\FormTransfer\Observers\TransferReferenceNoteObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class FormTransferTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(FormTransferServiceProvider::class);

        // Required dependency migrations used by form-transfer foreign keys.
        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            '--realpath' => false,
        ]);

        // Run plugin migrations.
        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/form-transfer/database/migrations',
            '--realpath' => false,
        ]);

        // Register observers explicitly for tests
        TransferBank::observe(TransferBankObserver::class);
        TransferDivision::observe(TransferDivisionObserver::class);
        TransferReferenceNote::observe(TransferReferenceNoteObserver::class);
        TransferApprovalWorkflow::observe(TransferApprovalWorkflowObserver::class);
    }
}
