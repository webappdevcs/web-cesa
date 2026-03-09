<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Services\ReferenceDataService;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Cache;

class ReferenceDataServiceTest extends FormTransferTestCase
{
    protected ReferenceDataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReferenceDataService::class);
        Cache::flush();
    }

    public function test_get_bank_options_returns_active_banks(): void
    {
        $activeBank = TransferBank::factory()->create([
            'code'       => 'BCA',
            'name'       => 'Bank Central Asia',
            'short_name' => 'BCA',
            'is_active'  => true,
        ]);

        $inactiveBank = TransferBank::factory()->create([
            'code'      => 'BNI',
            'name'      => 'Bank Negara Indonesia',
            'is_active' => false,
        ]);

        $options = $this->service->getBankOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey($activeBank->getKey(), $options);
        $this->assertArrayNotHasKey($inactiveBank->getKey(), $options);
        $this->assertEquals('BCA', $options[$activeBank->getKey()]);
    }

    public function test_get_bank_options_caches_results(): void
    {
        TransferBank::factory()->create([
            'code'      => 'BCA',
            'is_active' => true,
        ]);

        // First call should query database
        $firstCall = $this->service->getBankOptions();

        // Second call should use cache
        $secondCall = $this->service->getBankOptions();

        $this->assertEquals($firstCall, $secondCall);

        // Verify cache key exists
        $this->assertTrue(Cache::has('form_transfer:banks:active'));
    }

    public function test_get_division_options_returns_active_divisions(): void
    {
        $form = FormTransfer::factory()->create();

        TransferDivision::factory()->create([
            'form_transfer_id' => $form->id,
            'name'             => 'IT Department',
            'is_active'        => true,
        ]);

        TransferDivision::factory()->create([
            'form_transfer_id' => $form->id,
            'name'             => 'HR Department',
            'is_active'        => false,
        ]);

        $options = $this->service->getDivisionOptions($form->id);

        $this->assertIsArray($options);
        $this->assertCount(1, $options);
        $this->assertContains('IT Department', $options);
        $this->assertNotContains('HR Department', $options);
    }

    public function test_get_division_options_returns_empty_when_form_transfer_id_is_null(): void
    {
        $options = $this->service->getDivisionOptions(null);

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    public function test_get_reference_note_options_returns_active_notes(): void
    {
        $form = FormTransfer::factory()->create();

        TransferReferenceNote::factory()->create([
            'form_transfer_id' => $form->id,
            'label'            => 'Invoice #001',
            'is_active'        => true,
        ]);

        TransferReferenceNote::factory()->create([
            'form_transfer_id' => $form->id,
            'label'            => 'Invoice #002',
            'is_active'        => false,
        ]);

        $options = $this->service->getReferenceNoteOptions($form->id);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('Invoice #001', $options);
        $this->assertArrayNotHasKey('Invoice #002', $options);
    }

    public function test_get_workflow_options_returns_active_workflows(): void
    {
        $form = FormTransfer::factory()->create();
        $division = TransferDivision::factory()->for($form)->create();

        TransferApprovalWorkflow::factory()->create([
            'form_transfer_id' => $form->id,
            'division_id'      => $division->id,
            'name'             => 'Standard Workflow',
            'is_active'        => true,
            'steps'            => [
                ['label' => 'Step 1'],
                ['label' => 'Step 2'],
            ],
        ]);

        $options = $this->service->getWorkflowOptions($form->id, $division->id);

        $this->assertIsArray($options);
        $this->assertCount(1, $options);
    }

    public function test_find_bank_returns_bank_by_id(): void
    {
        $bank = TransferBank::factory()->create();

        $found = $this->service->findBank($bank->getKey());

        $this->assertNotNull($found);
        $this->assertEquals($bank->id, $found->id);
    }

    public function test_find_bank_returns_null_when_id_is_null(): void
    {
        $found = $this->service->findBank(null);

        $this->assertNull($found);
    }

    public function test_find_division_returns_division_by_id(): void
    {
        $division = TransferDivision::factory()->create(['name' => 'IT Department']);

        $found = $this->service->findDivision($division->id);

        $this->assertNotNull($found);
        $this->assertEquals('IT Department', $found->name);
    }

    public function test_invalidate_bank_cache_clears_cache(): void
    {
        // Populate cache
        $this->service->getBankOptions();
        $this->assertTrue(Cache::has('form_transfer:banks:active'));

        // Invalidate
        $this->service->invalidateBankCache();

        // Cache should be cleared
        $this->assertFalse(Cache::has('form_transfer:banks:active'));
    }

    public function test_invalidate_division_cache_clears_cache(): void
    {
        $form = FormTransfer::factory()->create();
        TransferDivision::factory()->for($form)->create(['is_active' => true]);

        // Populate cache
        $this->service->getDivisionOptions($form->id);
        $cacheKey = "form_transfer:divisions:{$form->id}:active";
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate
        $this->service->invalidateDivisionCache($form->id);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_cache_observers_invalidate_on_model_save(): void
    {
        // Populate bank cache
        $this->service->getBankOptions();
        $this->assertTrue(Cache::has('form_transfer:banks:active'));

        // Create new bank - observer should automatically invalidate cache
        $bank = new TransferBank([
            'code'       => 'NEWBANK',
            'name'       => 'New Bank',
            'short_name' => 'NEWBANK',
            'is_active'  => true,
            'sort_order' => 99,
        ]);
        $bank->save(); // Explicitly save to trigger model events

        // Cache should be invalidated by observer
        $this->assertFalse(Cache::has('form_transfer:banks:active'));
    }
}
