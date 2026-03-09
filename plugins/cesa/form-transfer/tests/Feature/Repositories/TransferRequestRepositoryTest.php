<?php

namespace Cesa\FormTransfer\Tests\Feature\Repositories;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Repositories\TransferRequestRepository;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Str;

class TransferRequestRepositoryTest extends FormTransferTestCase
{
    protected TransferRequestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(TransferRequestRepository::class);
    }

    public function test_find_returns_request_with_eager_loaded_relationships(): void
    {
        $form = FormTransfer::factory()->create();
        $request = TransferRequest::factory()->for($form, 'formTransfer')->create();

        $found = $this->repository->find($request->id);

        $this->assertNotNull($found);
        $this->assertEquals($request->id, $found->id);
        $this->assertTrue($found->relationLoaded('formTransfer'));
        $this->assertTrue($found->relationLoaded('division'));
    }

    public function test_find_with_details_loads_extended_relationships(): void
    {
        $form = FormTransfer::factory()->create();
        $request = TransferRequest::factory()->for($form, 'formTransfer')->create();

        $found = $this->repository->findWithDetails($request->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('formTransfer'));
        $this->assertTrue($found->relationLoaded('bank'));
        $this->assertTrue($found->relationLoaded('user'));
    }

    public function test_find_by_task_id_returns_correct_request(): void
    {
        $form = FormTransfer::factory()->create();
        $taskId = Str::uuid()->toString();

        $request = TransferRequest::factory()->for($form, 'formTransfer')->create([
            'approvals' => [
                [
                    'task_id' => $taskId,
                    'label'   => 'Approval 1',
                    'status'  => 'pending',
                ],
            ],
        ]);

        $found = $this->repository->findByTaskId($taskId);

        $this->assertNotNull($found);
        $this->assertEquals($request->id, $found->id);
    }

    public function test_find_by_status_response_id_returns_correct_request(): void
    {
        $form = FormTransfer::factory()->create();
        $statusResponseId = Str::uuid()->toString();

        $request = TransferRequest::factory()->for($form, 'formTransfer')->create([
            'status_response_id' => $statusResponseId,
        ]);

        $found = $this->repository->findByStatusResponseId($statusResponseId);

        $this->assertNotNull($found);
        $this->assertEquals($request->id, $found->id);
        $this->assertEquals($statusResponseId, $found->status_response_id);
    }

    public function test_paginate_returns_filtered_requests(): void
    {
        $form = FormTransfer::factory()->create();
        TransferRequest::factory()->count(15)->for($form, 'formTransfer')->create([
            'approval_status' => 'pending',
        ]);

        TransferRequest::factory()->count(5)->for($form, 'formTransfer')->create([
            'approval_status' => 'approved',
        ]);

        $paginated = $this->repository->paginate(['approval_status' => 'pending'], 10);

        $this->assertEquals(15, $paginated->total());
        $this->assertEquals(10, $paginated->perPage());
    }

    public function test_paginate_with_search_filters_by_uid(): void
    {
        $form = FormTransfer::factory()->create();
        $request = TransferRequest::factory()->for($form, 'formTransfer')->create([
            'uid' => 'TR-2025-001',
        ]);

        TransferRequest::factory()->count(5)->for($form, 'formTransfer')->create();

        $paginated = $this->repository->paginate(['search' => 'TR-2025-001'], 10);

        $this->assertEquals(1, $paginated->total());
        $this->assertEquals('TR-2025-001', $paginated->first()->uid);
    }

    public function test_get_by_form_transfer_returns_requests_for_form(): void
    {
        $form1 = FormTransfer::factory()->create();
        $form2 = FormTransfer::factory()->create();

        TransferRequest::factory()->count(3)->for($form1, 'formTransfer')->create();
        TransferRequest::factory()->count(2)->for($form2, 'formTransfer')->create();

        $requests = $this->repository->getByFormTransfer($form1->id);

        $this->assertCount(3, $requests);
        $this->assertTrue($requests->every(fn ($r) => $r->form_transfer_id === $form1->id));
    }

    public function test_create_saves_new_request(): void
    {
        $form = FormTransfer::factory()->create();
        $bank = TransferBank::factory()->create();

        $data = [
            'form_transfer_id' => $form->id,
            'uid'              => 'TR-2025-TEST',
            'requester_name'   => 'John Doe',
            'email'            => 'john@example.com',
            'account_number'   => '1234567890',
            'account_name'     => 'John Doe',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Test transfer',
        ];

        $request = $this->repository->create($data);

        $this->assertNotNull($request);
        $this->assertDatabaseHas('form_transfer_requests', [
            'uid'            => 'TR-2025-TEST',
            'requester_name' => 'John Doe',
            'account_number' => '1234567890',
        ]);
    }

    public function test_update_modifies_existing_request(): void
    {
        $form = FormTransfer::factory()->create();
        $request = TransferRequest::factory()->for($form, 'formTransfer')->create([
            'requester_name' => 'John Doe',
        ]);

        $updated = $this->repository->update($request->id, [
            'requester_name' => 'Jane Smith',
        ]);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('form_transfer_requests', [
            'id'             => $request->id,
            'requester_name' => 'Jane Smith',
        ]);
    }

    public function test_delete_removes_request(): void
    {
        $form = FormTransfer::factory()->create();
        $request = TransferRequest::factory()->for($form, 'formTransfer')->create();

        $deleted = $this->repository->delete($request->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('form_transfer_requests', ['id' => $request->id]);
    }

    public function test_count_by_status_returns_accurate_counts(): void
    {
        $form = FormTransfer::factory()->create();

        TransferRequest::factory()->count(3)->for($form, 'formTransfer')->create([
            'approval_status' => 'pending',
        ]);

        TransferRequest::factory()->count(2)->for($form, 'formTransfer')->create([
            'approval_status' => 'approved',
        ]);

        TransferRequest::factory()->count(1)->for($form, 'formTransfer')->create([
            'approval_status' => 'rejected',
        ]);

        $counts = $this->repository->countByStatus($form->id);

        $this->assertEquals(3, $counts['pending']);
        $this->assertEquals(2, $counts['approved']);
        $this->assertEquals(1, $counts['rejected']);
    }
}
