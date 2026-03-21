<?php

namespace Cesa\LegacySync\Tests\Feature;

use App\Models\User;
use Cesa\LegacySync\Tests\LegacySyncTestCase;
use Cesa\Shelf\Models\AssetTransfer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Support\Models\Company;

class LegacySqlSyncCommandTest extends LegacySyncTestCase
{
    public function test_it_syncs_legacy_form_transfer_and_exit_clearance_data(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $creator = $targetData['creator'];
        $requester = $targetData['requester'];
        $targetCompanyId = $targetData['company_id'];

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])->assertExitCode(0);

        $formTransferId = DB::table('form_transfers')
            ->where('uid_prefix', 'CSN')
            ->value('id');

        $divisionId = DB::table('form_transfer_divisions')
            ->where('form_transfer_id', $formTransferId)
            ->where('name', 'IT')
            ->value('id');

        $workflowId = DB::table('form_transfer_approval_workflows')
            ->where('form_transfer_id', $formTransferId)
            ->value('id');

        $this->assertDatabaseHas('form_transfers', [
            'id'         => $formTransferId,
            'company_id' => $targetCompanyId,
            'creator_id' => $creator->id,
            'uid_prefix' => 'CSN',
        ]);

        $this->assertDatabaseHas('form_transfer_requests', [
            'uid'                  => 'CSN-00001',
            'form_transfer_id'     => $formTransferId,
            'division_id'          => $divisionId,
            'approval_workflow_id' => $workflowId,
            'user_id'              => $requester->id,
            'creator_id'           => $creator->id,
            'company_id'           => $targetCompanyId,
            'approval_status'      => 'approved',
            'realization_status'   => 'done',
        ]);

        $departmentId = DB::table('exit_clearance_departments')
            ->where('code', 'HR')
            ->value('id');

        $approverId = DB::table('exit_clearance_approvers')
            ->where('email', 'approver@example.com')
            ->value('id');

        $requestId = DB::table('exit_clearance_requests')
            ->where('form_uid', 'EXC-00001')
            ->value('id');

        $this->assertDatabaseHas('exit_clearance_departments', [
            'id'         => $departmentId,
            'code'       => 'HR',
            'created_by' => $creator->id,
        ]);

        $this->assertDatabaseHas('exit_clearance_approvers', [
            'id'         => $approverId,
            'email'      => 'approver@example.com',
            'created_by' => $creator->id,
        ]);

        $this->assertDatabaseHas('exit_clearance_requests', [
            'id'            => $requestId,
            'department_id' => $departmentId,
            'created_by'    => $creator->id,
            'form_status'   => 'Approved',
        ]);

        $this->assertDatabaseHas('exit_clearance_department_approver', [
            'department_id' => $departmentId,
            'approver_id'   => $approverId,
        ]);

        $this->assertDatabaseHas('exit_clearance_request_approver', [
            'request_id'  => $requestId,
            'approver_id' => $approverId,
            'status'      => 'approved',
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'transfer_requests',
            'legacy_id'       => '104',
            'target_table'    => 'form_transfer_requests',
        ]);

        $officeId = DB::table('presensi_offices')->where('name', 'Head Office')->value('id');
        $shiftId = DB::table('presensi_shifts')->where('name', 'Shift Pagi')->value('id');
        $requesterPartnerId = (int) DB::table('users')->where('id', $requester->id)->value('partner_id');

        $this->assertDatabaseHas('partners_partners', [
            'id'     => $requesterPartnerId,
            'avatar' => 'legacy/requester.png',
        ]);

        $this->assertDatabaseHas('presensi_offices', [
            'id'   => $officeId,
            'name' => 'Head Office',
        ]);

        $this->assertDatabaseHas('presensi_shifts', [
            'id'   => $shiftId,
            'name' => 'Shift Pagi',
        ]);

        $this->assertDatabaseHas('presensi_schedules', [
            'user_id'   => $requester->id,
            'shift_id'  => $shiftId,
            'office_id' => $officeId,
            'is_wfa'    => 0,
            'is_banned' => 0,
        ]);

        $this->assertDatabaseHas('presensi_attendances', [
            'id'      => 300,
            'user_id' => $requester->id,
        ]);

        $this->assertDatabaseHas('presensi_leaves', [
            'id'      => 301,
            'user_id' => $requester->id,
            'type'    => 'Izin',
            'status'  => 'approved',
        ]);

        $this->assertDatabaseHas('presensi_overtimes', [
            'id'      => 302,
            'user_id' => $requester->id,
            'status'  => 'pending',
        ]);

        $helpdeskUnitId = DB::table('helpdesk_units')->where('name', 'IT')->value('id');
        $helpdeskCategoryId = DB::table('helpdesk_problem_categories')->where('name', 'Software')->value('id');
        $helpdeskTicketId = DB::table('helpdesk_tickets')->where('title', 'Laptop blue screen')->value('id');
        $helpdeskStatusId = DB::table('helpdesk_ticket_statuses')->where('name', 'In Progress')->value('id');

        $this->assertDatabaseHas('helpdesk_priorities', [
            'id'   => 1,
            'name' => 'Critical/Urgent',
        ]);

        $this->assertDatabaseHas('helpdesk_units', [
            'id'   => $helpdeskUnitId,
            'name' => 'IT',
        ]);

        $this->assertDatabaseHas('helpdesk_unit_user', [
            'unit_id' => $helpdeskUnitId,
            'user_id' => $creator->id,
        ]);

        $this->assertDatabaseHas('helpdesk_problem_categories', [
            'id'      => $helpdeskCategoryId,
            'unit_id' => $helpdeskUnitId,
            'name'    => 'Software',
        ]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'                  => $helpdeskTicketId,
            'priority_id'         => 1,
            'unit_id'             => $helpdeskUnitId,
            'problem_category_id' => $helpdeskCategoryId,
            'owner_id'            => $requester->id,
            'responsible_id'      => $creator->id,
            'company_id'          => $targetCompanyId,
            'ticket_status_id'    => $helpdeskStatusId,
            'title'               => 'Laptop blue screen',
        ]);

        $this->assertDatabaseHas('helpdesk_comments', [
            'ticket_id' => $helpdeskTicketId,
            'user_id'   => $creator->id,
            'comment'   => 'Sedang dicek oleh tim IT.',
        ]);

        $this->assertDatabaseHas('helpdesk_ticket_histories', [
            'ticket_id'        => $helpdeskTicketId,
            'ticket_status_id' => $helpdeskStatusId,
            'user_id'          => $creator->id,
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'tickets',
            'legacy_id'       => '400',
            'target_table'    => 'helpdesk_tickets',
        ]);
    }

    public function test_it_syncs_legacy_shelf_data(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $creator = $targetData['creator'];
        $requester = $targetData['requester'];
        $targetCompanyId = $targetData['company_id'];

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $parentCategoryId = (int) DB::table('shelf_categories')->where('name', 'Hardware')->value('id');
        $categoryId = (int) DB::table('shelf_categories')->where('name', 'Laptop')->value('id');
        $brandId = (int) DB::table('shelf_brands')->where('name', 'Dell')->value('id');
        $locationId = (int) DB::table('shelf_asset_locations')->where('name', 'Gudang Pusat')->value('id');
        $vendorId = (int) DB::table('shelf_vendors')->where('name', 'CV Legacy Service')->value('id');
        $assetId = (int) DB::table('shelf_assets')->where('serial_number', 'SN-LAP-001')->value('id');
        $customAttributeId = (int) DB::table('shelf_custom_asset_attributes')->where('name', 'Masa Garansi')->value('id');
        $taskId = (int) DB::table('shelf_tasks')->where('code', 'TSK-001')->value('id');
        $assetTransferId = (int) DB::table('shelf_asset_transfers')->where('letter_number', 'BAST-001')->value('id');
        $approvalLevelId = (int) DB::table('shelf_approval_levels')
            ->where('request_type', 'penarikan_aset')
            ->where('division', 'IT')
            ->where('level', 1)
            ->value('id');
        $assetRequestId = (int) DB::table('shelf_asset_requests')->where('uuid', 'asset-request-uuid-1')->value('id');

        $this->assertDatabaseHas('shelf_categories', [
            'id'        => $parentCategoryId,
            'name'      => 'Hardware',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('shelf_categories', [
            'id'        => $categoryId,
            'name'      => 'Laptop',
            'parent_id' => $parentCategoryId,
        ]);

        $this->assertDatabaseHas('shelf_assets', [
            'id'                   => $assetId,
            'company_id'           => $targetCompanyId,
            'category_id'          => $categoryId,
            'brand_id'             => $brandId,
            'asset_location_id'    => $locationId,
            'recipient_id'         => $requester->id,
            'recipient_company_id' => $targetCompanyId,
            'condition_status'     => 'available',
            'nbh_status'           => 'none',
            'serial_number'        => 'SN-LAP-001',
        ]);

        $this->assertDatabaseHas('shelf_custom_asset_attributes', [
            'id'       => $customAttributeId,
            'name'     => 'Masa Garansi',
            'type'     => 'date',
            'required' => 1,
        ]);

        $this->assertDatabaseHas('shelf_asset_attributes', [
            'asset_id'            => $assetId,
            'custom_attribute_id' => $customAttributeId,
            'attribute_value'     => '2027-03-10',
        ]);

        $this->assertDatabaseHas('shelf_tasks', [
            'id'         => $taskId,
            'code'       => 'TSK-001',
            'company_id' => $targetCompanyId,
            'vendor_id'  => $vendorId,
            'user_id'    => $creator->id,
            'status'     => 'in_progress',
        ]);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'id'           => $assetTransferId,
            'company_id'   => $targetCompanyId,
            'from_user_id' => $creator->id,
            'to_user_id'   => $requester->id,
            'letter_number'=> 'BAST-001',
        ]);

        $this->assertDatabaseHas('shelf_asset_transfer_details', [
            'asset_transfer_id' => $assetTransferId,
            'asset_id'          => $assetId,
            'equipment'         => 'Adaptor',
        ]);

        $this->assertDatabaseHas('shelf_vehicle_checksheets', [
            'reference_number' => 'GA-2026-001',
            'asset_id'         => $assetId,
            'license_plate'    => 'B 1234 CD',
            'destination'      => 'Site Alpha',
        ]);

        $this->assertDatabaseHas('shelf_approval_levels', [
            'id'             => $approvalLevelId,
            'request_type'   => 'penarikan_aset',
            'division'       => 'IT',
            'approver_email' => 'asset.approver@example.com',
        ]);

        $this->assertDatabaseHas('shelf_asset_requests', [
            'id'            => $assetRequestId,
            'uuid'          => 'asset-request-uuid-1',
            'request_type'  => 'penarikan_aset',
            'user_id'       => $requester->id,
            'asset_id'      => $assetId,
            'status'        => 'pending',
            'approval_track'=> 'IT',
        ]);

        $this->assertDatabaseHas('shelf_request_approvals', [
            'asset_request_id'  => $assetRequestId,
            'approval_level_id' => $approvalLevelId,
            'token'             => 'asset-approval-token-1',
            'status'            => 'pending',
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'asset_requests',
            'legacy_id'       => '1001',
            'target_table'    => 'shelf_asset_requests',
        ]);
    }

    public function test_it_updates_existing_mapped_rows_on_subsequent_syncs(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])->assertExitCode(0);

        DB::connection('legacy_sync')
            ->table('transfer_requests')
            ->where('id', 104)
            ->update([
                'purpose'    => 'Updated legacy purpose',
                'updated_at' => '2026-03-13 10:00:00',
            ]);

        DB::connection('legacy_sync')
            ->table('ec_approvers')
            ->where('id', 201)
            ->update([
                'title'      => 'Updated Title',
                'updated_at' => '2026-03-13 10:00:00',
            ]);

        DB::connection('legacy_sync')
            ->table('tickets')
            ->where('id', 400)
            ->update([
                'title'              => 'Updated Laptop blue screen',
                'ticket_statuses_id' => 4,
                'updated_at'         => '2026-03-13 10:00:00',
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('form_transfer_requests', [
            'uid'     => 'CSN-00001',
            'purpose' => 'Updated legacy purpose',
        ]);

        $this->assertDatabaseHas('exit_clearance_approvers', [
            'email' => 'approver@example.com',
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'title'            => 'Updated Laptop blue screen',
            'ticket_status_id' => 4,
        ]);

        $requester = DB::table('users')->where('email', 'requester@example.com')->first();

        DB::connection('legacy_sync')
            ->table('users')
            ->where('id', 11)
            ->update([
                'image' => 'legacy/requester-updated.png',
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])->assertExitCode(0);

        $requesterPartnerId = (int) DB::table('users')->where('id', $requester->id)->value('partner_id');

        $this->assertDatabaseHas('partners_partners', [
            'id'     => $requesterPartnerId,
            'avatar' => 'legacy/requester-updated.png',
        ]);
    }

    public function test_it_normalizes_legacy_helpdesk_cancel_status_into_existing_cancelled_master(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        DB::connection('legacy_sync')
            ->table('tickets')
            ->where('id', 400)
            ->update([
                'ticket_statuses_id' => 3,
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])->assertExitCode(0);

        $this->assertSame(1, DB::table('helpdesk_ticket_statuses')->where('id', 3)->where('name', 'Cancelled')->count());
        $this->assertSame(4, DB::table('helpdesk_ticket_statuses')->count());
        $this->assertDatabaseMissing('helpdesk_ticket_statuses', [
            'name' => 'Cancel',
        ]);
        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => 400,
            'ticket_status_id' => 3,
        ]);
    }

    public function test_it_creates_missing_company_for_unmapped_helpdesk_business_entity(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        DB::connection('legacy_sync')
            ->table('business_entities')
            ->where('id', 1)
            ->update([
                'name' => 'PT MKLI',
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['helpdesk'],
        ])
            ->doesntExpectOutputToContain('Could not map legacy business entity ID [1]')
            ->expectsOutputToContain('Created missing company [PT MKLI] from legacy business entity ID [1].')
            ->assertExitCode(0);

        $companyId = (int) DB::table('companies')->where('name', 'PT MKLI')->value('id');
        $partnerId = (int) DB::table('companies')->where('id', $companyId)->value('partner_id');

        $this->assertNotSame(0, $companyId);
        $this->assertNotSame(0, $partnerId);

        $this->assertDatabaseHas('partners_partners', [
            'id'         => $partnerId,
            'name'       => 'PT MKLI',
            'sub_type'   => 'company',
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'         => 400,
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'business_entities',
            'legacy_id'       => '1',
            'target_table'    => 'companies',
            'target_id'       => (string) $companyId,
        ]);
    }

    public function test_it_creates_missing_company_for_unmapped_shelf_business_entity(): void
    {
        User::factory()->create(['email' => 'creator@example.com']);
        User::factory()->create(['email' => 'requester@example.com']);

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Could not map legacy company ID [50]')
            ->expectsOutputToContain('Created missing company [Complete Solusi Nusantara] from legacy company ID [50].')
            ->assertExitCode(0);

        $companyId = (int) DB::table('companies')->where('company_id', 'CSN')->value('id');
        $partnerId = (int) DB::table('companies')->where('id', $companyId)->value('partner_id');

        $this->assertNotSame(0, $companyId);
        $this->assertNotSame(0, $partnerId);

        $this->assertDatabaseHas('partners_partners', [
            'id'         => $partnerId,
            'name'       => 'Complete Solusi Nusantara',
            'sub_type'   => 'company',
            'company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('shelf_assets', [
            'serial_number'        => 'SN-LAP-001',
            'company_id'           => $companyId,
            'recipient_company_id' => $companyId,
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'companies',
            'legacy_id'       => '50',
            'target_table'    => 'companies',
            'target_id'       => (string) $companyId,
        ]);
    }

    public function test_it_uses_legacy_business_entity_name_for_shelf_company_creation(): void
    {
        User::factory()->create(['email' => 'creator@example.com']);
        User::factory()->create(['email' => 'requester@example.com']);

        $this->seedLegacyRecords();

        DB::connection('legacy_sync')->table('companies')->where('id', 50)->delete();
        DB::connection('legacy_sync')->table('business_entities')->insert([
            'id'   => 50,
            'name' => 'PT Shelf Entity',
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Legacy Company 50')
            ->expectsOutputToContain('Created missing company [PT Shelf Entity] from legacy company ID [50].')
            ->assertExitCode(0);

        $companyId = (int) DB::table('companies')->where('name', 'PT Shelf Entity')->value('id');

        $this->assertNotSame(0, $companyId);

        $this->assertDatabaseMissing('companies', [
            'name' => 'Legacy Company 50',
        ]);

        $this->assertDatabaseHas('shelf_assets', [
            'serial_number' => 'SN-LAP-001',
            'company_id'    => $companyId,
        ]);
    }

    public function test_it_replaces_existing_placeholder_company_name_with_business_entity_name_for_shelf_sync(): void
    {
        User::factory()->create(['email' => 'creator@example.com']);
        User::factory()->create(['email' => 'requester@example.com']);

        $this->seedLegacyRecords();

        DB::connection('legacy_sync')->table('companies')->where('id', 50)->delete();
        DB::connection('legacy_sync')->table('business_entities')->insert([
            'id'   => 50,
            'name' => 'PT Shelf Entity Rename',
        ]);

        $placeholderCompany = Company::query()->create([
            'name'       => 'Legacy Company 50',
            'company_id' => 'LEGACY-50',
            'is_active'  => true,
        ]);

        DB::table('legacy_sync_mappings')->insert([
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'companies',
            'legacy_id'       => '50',
            'target_table'    => 'companies',
            'target_id'       => (string) $placeholderCompany->id,
            'synced_at'       => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $placeholderCompany->refresh();

        $this->assertSame('PT Shelf Entity Rename', $placeholderCompany->name);

        $this->assertDatabaseMissing('companies', [
            'id'   => $placeholderCompany->id,
            'name' => 'Legacy Company 50',
        ]);

        $this->assertDatabaseHas('partners_partners', [
            'id'   => $placeholderCompany->partner_id,
            'name' => 'PT Shelf Entity Rename',
        ]);
    }

    public function test_it_syncs_shelf_company_document_settings_from_legacy_business_entities(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();
        User::factory()->create(['email' => 'creator@example.com']);
        User::factory()->create(['email' => 'requester@example.com']);

        $this->seedLegacyRecords();

        DB::connection('legacy_sync')
            ->table('business_entities')
            ->where('id', 1)
            ->update([
                'format'     => 'CSN-DOC-',
                'color'      => '#1A73E8',
                'letterhead' => '/storage/legacy/letterheads/csn.png',
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_company_document_settings', [
            'company_id'      => $targetCompanyId,
            'format'          => 'CSN-DOC-',
            'color'           => '#1A73E8',
            'letterhead_path' => 'legacy/letterheads/csn.png',
        ]);
    }

    public function test_it_preserves_existing_shelf_company_document_settings_when_legacy_business_entity_fields_are_empty(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();
        User::factory()->create(['email' => 'creator@example.com']);
        User::factory()->create(['email' => 'requester@example.com']);

        DB::table('shelf_company_document_settings')->insert([
            'company_id'      => $targetCompanyId,
            'format'          => 'KEEP-',
            'color'           => '#000000',
            'letterhead_path' => 'shelf/letterheads/existing.png',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_company_document_settings', [
            'company_id'      => $targetCompanyId,
            'format'          => 'KEEP-',
            'color'           => '#000000',
            'letterhead_path' => 'shelf/letterheads/existing.png',
        ]);
    }

    public function test_it_automatically_creates_missing_users_from_legacy_data(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
        ])
            ->doesntExpectOutputToContain('Could not map legacy user ID')
            ->doesntExpectOutputToContain('Skipping legacy record')
            ->assertExitCode(0);

        $creatorId = (int) DB::table('users')->where('email', 'creator@example.com')->value('id');
        $requesterId = (int) DB::table('users')->where('email', 'requester@example.com')->value('id');

        $this->assertNotSame(0, $creatorId);
        $this->assertNotSame(0, $requesterId);

        $this->assertDatabaseHas('form_transfers', [
            'uid_prefix'  => 'CSN',
            'company_id'  => $targetCompanyId,
            'creator_id'  => $creatorId,
        ]);

        $this->assertDatabaseHas('form_transfer_requests', [
            'uid'       => 'CSN-00001',
            'user_id'   => $requesterId,
            'creator_id'=> $creatorId,
        ]);

        $this->assertDatabaseHas('exit_clearance_requests', [
            'form_uid'    => 'EXC-00001',
            'created_by'  => $creatorId,
        ]);

        $this->assertDatabaseHas('presensi_attendances', [
            'id'                => 300,
            'user_id'           => $requesterId,
            'date'              => '2026-03-10',
            'check_in_status'   => 'late',
            'check_out_status'  => 'on_time',
            'attendance_status' => 'closed',
        ]);

        $this->assertDatabaseHas('presensi_overtimes', [
            'id'      => 302,
            'user_id' => $requesterId,
        ]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'title'         => 'Laptop blue screen',
            'owner_id'      => $requesterId,
            'responsible_id'=> $creatorId,
        ]);

        $requesterPartnerId = (int) DB::table('users')->where('id', $requesterId)->value('partner_id');

        $this->assertDatabaseHas('partners_partners', [
            'id'     => $requesterPartnerId,
            'avatar' => 'legacy/requester.png',
        ]);
    }

    public function test_it_reuses_employee_linked_user_for_shelf_sync_before_creating_a_new_user(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();
        $this->createMinimalEmployeesTable();

        $requester = User::factory()->create([
            'email' => 'existing.requester.account@example.com',
        ]);

        DB::table('employees_employees')->insert([
            'company_id'   => $targetCompanyId,
            'user_id'      => $requester->id,
            'name'         => 'Legacy Requester',
            'work_email'   => 'requester@example.com',
            'private_email'=> null,
            'created_at'   => now(),
            'updated_at'   => now(),
            'deleted_at'   => null,
        ]);

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Could not map legacy user ID [11]')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('users')->where('email', 'requester@example.com')->count());

        $this->assertDatabaseHas('shelf_assets', [
            'serial_number' => 'SN-LAP-001',
            'recipient_id'  => $requester->id,
        ]);

        $this->assertDatabaseHas('shelf_asset_requests', [
            'uuid'    => 'asset-request-uuid-1',
            'user_id' => $requester->id,
        ]);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'to_user_id'    => $requester->id,
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'users',
            'legacy_id'       => '11',
            'target_table'    => 'users',
            'target_id'       => (string) $requester->id,
        ]);
    }

    public function test_it_syncs_shelf_transfer_details_even_when_asset_optional_relations_are_missing(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        DB::connection('legacy_sync')->table('categories')->where('id', 501)->delete();
        DB::connection('legacy_sync')->table('brands')->where('id', 510)->delete();
        DB::connection('legacy_sync')->table('asset_locations')->where('id', 520)->delete();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Skipping legacy record [asset_transfer_details:571]')
            ->assertExitCode(0);

        $assetId = (int) DB::table('shelf_assets')->where('serial_number', 'SN-LAP-001')->value('id');
        $assetTransferId = (int) DB::table('shelf_asset_transfers')->where('letter_number', 'BAST-001')->value('id');

        $this->assertDatabaseHas('shelf_assets', [
            'id'                   => $assetId,
            'serial_number'        => 'SN-LAP-001',
            'company_id'           => $targetData['company_id'],
            'category_id'          => null,
            'brand_id'             => null,
            'asset_location_id'    => null,
            'recipient_company_id' => $targetData['company_id'],
        ]);

        $this->assertDatabaseHas('shelf_asset_transfer_details', [
            'asset_transfer_id' => $assetTransferId,
            'asset_id'          => $assetId,
            'equipment'         => 'Adaptor',
        ]);
    }

    public function test_it_prefers_explicit_legacy_transfer_type_for_shelf_sync(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        Schema::connection('legacy_sync')->table('asset_transfers', function (Blueprint $table): void {
            $table->string('transfer_type')->nullable();
        });

        DB::connection('legacy_sync')->table('asset_transfers')
            ->where('id', 570)
            ->update(['transfer_type' => 'return']);

        config([
            'legacy-sync.shelf.asset_transfers.fallback_to_role_inference' => false,
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number'  => 'BAST-001',
            'transfer_type'  => AssetTransfer::TYPE_RETURN,
        ]);
    }

    public function test_it_can_infer_shelf_transfer_type_from_configured_custodian_users_without_roles(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        config([
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_emails' => ['requester@example.com'],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_emails' => [],
            'legacy-sync.shelf.asset_transfers.fallback_to_role_inference'   => false,
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'transfer_type' => AssetTransfer::TYPE_RETURN,
        ]);
    }

    public function test_it_prefers_return_inference_over_legacy_pengalihan_label_for_shelf_sync(): void
    {
        $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        Schema::connection('legacy_sync')->table('asset_transfers', function (Blueprint $table): void {
            $table->string('status')->nullable();
        });

        DB::connection('legacy_sync')->table('asset_transfers')
            ->where('id', 570)
            ->update(['status' => AssetTransfer::STATUS_REASSIGNMENT]);

        config([
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_emails' => ['requester@example.com'],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_emails' => [],
            'legacy-sync.shelf.asset_transfers.fallback_to_role_inference'   => false,
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'transfer_type' => AssetTransfer::TYPE_RETURN,
        ]);
    }

    public function test_it_prefers_handover_inference_over_legacy_pengalihan_label_for_ga_named_sender(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $this->seedLegacyRecords();

        Schema::connection('legacy_sync')->table('asset_transfers', function (Blueprint $table): void {
            $table->string('status')->nullable();
        });

        $targetData['creator']->forceFill(['name' => 'GA'])->save();
        $targetData['requester']->forceFill(['name' => 'SHOFYAN NURUL GHONI AL HASAN'])->save();

        DB::connection('legacy_sync')->table('asset_transfers')
            ->where('id', 570)
            ->update(['status' => AssetTransfer::STATUS_REASSIGNMENT]);

        config([
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_emails' => [],
            'legacy-sync.shelf.asset_transfers.custodian_legacy_user_names'  => [],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_ids'    => [],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_emails' => [],
            'legacy-sync.shelf.asset_transfers.custodian_target_user_names'  => [],
            'legacy-sync.shelf.asset_transfers.fallback_to_role_inference'   => false,
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'transfer_type' => AssetTransfer::TYPE_HANDOVER,
        ]);
    }

    public function test_it_syncs_shelf_employees_and_job_titles_when_target_tables_exist(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $this->createMinimalEmployeesTable();
        $this->seedLegacyRecords();

        Schema::connection('legacy_sync')->create('employees_job_positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('legacy_sync')->create('employees_employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->string('name')->nullable();
            $table->string('employee_code')->nullable();
            $table->string('job_title')->nullable();
            $table->string('work_email')->nullable();
            $table->string('private_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::connection('legacy_sync')->table('employees_job_positions')->insert([
            'id'          => 700,
            'company_id'  => 50,
            'creator_id'  => 10,
            'name'        => 'General Affair',
            'description' => 'Legacy GA',
            'is_active'   => 1,
            'created_at'  => '2026-03-10 07:00:00',
            'updated_at'  => '2026-03-10 07:00:00',
            'deleted_at'  => null,
        ]);

        DB::connection('legacy_sync')->table('employees_employees')->insert([
            'id'           => 701,
            'company_id'   => 50,
            'user_id'      => 11,
            'creator_id'   => 10,
            'job_id'       => 700,
            'name'         => 'Legacy Requester',
            'employee_code'=> 'EMP-LEG-001',
            'job_title'    => 'General Affair',
            'work_email'   => 'requester@example.com',
            'private_email'=> null,
            'is_active'    => 1,
            'created_at'   => '2026-03-10 07:00:00',
            'updated_at'   => '2026-03-10 07:00:00',
            'deleted_at'   => null,
        ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $jobPositionId = (int) DB::table('employees_job_positions')
            ->where('name', 'General Affair')
            ->value('id');

        $this->assertDatabaseHas('employees_job_positions', [
            'id'         => $jobPositionId,
            'company_id' => $targetData['company_id'],
            'name'       => 'General Affair',
        ]);

        $this->assertDatabaseHas('employees_employees', [
            'company_id'    => $targetData['company_id'],
            'user_id'       => $targetData['requester']->id,
            'job_id'        => $jobPositionId,
            'employee_code' => 'EMP-LEG-001',
            'work_email'    => 'requester@example.com',
        ]);
    }

    public function test_it_reuses_existing_user_with_same_name_for_shelf_sync_before_creating_placeholder(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();

        $existingUser = User::factory()->create([
            'name'  => 'ANA SUCIAWATI',
            'email' => 'suci.sweetiess@gmail.com',
        ]);

        $this->seedLegacyRecords();

        Schema::connection('legacy_sync')->table('users', function (Blueprint $table): void {
            $table->string('name')->nullable();
        });

        DB::connection('legacy_sync')->table('users')
            ->where('id', 11)
            ->update([
                'name'  => 'ANA SUCIAWATI',
                'email' => null,
            ]);

        DB::connection('legacy_sync')->table('companies')
            ->where('id', 50)
            ->update([
                'name' => 'CV Top Selular',
            ]);

        DB::table('companies')
            ->where('id', $targetCompanyId)
            ->update([
                'name' => 'CV Top Selular',
            ]);

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Created placeholder user [legacy-user-11@legacy-sync.local]')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('users')->where('email', 'legacy-user-11@legacy-sync.local')->count());

        $this->assertDatabaseHas('shelf_assets', [
            'serial_number' => 'SN-LAP-001',
            'recipient_id'  => $existingUser->id,
        ]);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'to_user_id'    => $existingUser->id,
        ]);

        $this->assertDatabaseHas('legacy_sync_mappings', [
            'connection_name' => 'legacy_sync',
            'legacy_table'    => 'users',
            'legacy_id'       => '11',
            'target_table'    => 'users',
            'target_id'       => (string) $existingUser->id,
        ]);
    }

    public function test_it_preserves_legacy_asset_company_when_target_user_default_company_differs(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $targetCompanyId = $targetData['company_id'];
        $dummyCompanyId = (int) DB::table('companies')->where('company_id', 'DUMMY')->value('id');

        if (! Schema::hasColumn('users', 'default_company_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('default_company_id')->nullable();
            });
        }

        DB::table('users')
            ->where('id', $targetData['requester']->id)
            ->update(['default_company_id' => $dummyCompanyId]);

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_assets', [
            'serial_number' => 'SN-LAP-001',
            'company_id'    => $targetCompanyId,
            'recipient_id'  => $targetData['requester']->id,
        ]);
    }

    public function test_it_preserves_legacy_asset_transfer_company_when_target_users_default_company_differs(): void
    {
        $targetData = $this->createTargetUsersAndCompanies();
        $targetCompanyId = $targetData['company_id'];
        $dummyCompanyId = (int) DB::table('companies')->where('company_id', 'DUMMY')->value('id');

        if (! Schema::hasColumn('users', 'default_company_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('default_company_id')->nullable();
            });
        }

        DB::table('users')
            ->whereIn('id', [$targetData['creator']->id, $targetData['requester']->id])
            ->update(['default_company_id' => $dummyCompanyId]);

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'letter_number' => 'BAST-001',
            'company_id'    => $targetCompanyId,
            'from_user_id'  => $targetData['creator']->id,
            'to_user_id'    => $targetData['requester']->id,
        ]);
    }

    public function test_it_creates_placeholder_users_for_shelf_transfers_when_legacy_user_rows_are_missing(): void
    {
        $targetCompanyId = $this->createTargetCompaniesOnly();
        $this->seedLegacyRecords();

        DB::connection('legacy_sync')->table('users')->whereIn('id', [10, 11])->delete();

        $this->artisan('legacy:sync', [
            '--connection' => 'legacy_sync',
            '--module'     => ['shelf'],
        ])
            ->doesntExpectOutputToContain('Skipping legacy record [asset_transfers:570]')
            ->doesntExpectOutputToContain('Skipping legacy record [asset_transfer_details:571]')
            ->assertExitCode(0);

        $fromUserId = (int) DB::table('users')->where('email', 'legacy-user-10@legacy-sync.local')->value('id');
        $toUserId = (int) DB::table('users')->where('email', 'legacy-user-11@legacy-sync.local')->value('id');
        $assetId = (int) DB::table('shelf_assets')->where('serial_number', 'SN-LAP-001')->value('id');
        $assetTransferId = (int) DB::table('shelf_asset_transfers')->where('letter_number', 'BAST-001')->value('id');

        $this->assertNotSame(0, $fromUserId);
        $this->assertNotSame(0, $toUserId);

        $this->assertDatabaseHas('shelf_assets', [
            'id'                      => $assetId,
            'company_id'              => $targetCompanyId,
            'nbh_responsible_user_id' => $fromUserId,
            'recipient_id'            => $toUserId,
            'recipient_company_id'    => $targetCompanyId,
        ]);

        $this->assertDatabaseHas('shelf_asset_transfers', [
            'id'           => $assetTransferId,
            'company_id'   => $targetCompanyId,
            'from_user_id' => $fromUserId,
            'to_user_id'   => $toUserId,
        ]);

        $this->assertDatabaseHas('shelf_asset_transfer_details', [
            'asset_transfer_id' => $assetTransferId,
            'asset_id'          => $assetId,
            'equipment'         => 'Adaptor',
        ]);
    }

    public function test_it_can_skip_automatic_missing_user_creation(): void
    {
        $this->createTargetCompaniesOnly();

        $this->seedLegacyRecords();

        $this->artisan('legacy:sync', [
            '--connection'         => 'legacy_sync',
            '--skip-missing-users' => true,
        ])
            ->expectsOutputToContain('Could not map legacy user ID [10]')
            ->expectsOutputToContain('Skipping legacy record [schedules:212] because relation [user_or_shift_or_office=11:211:210] could not be resolved.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', [
            'email' => 'creator@example.com',
        ]);

        $this->assertDatabaseHas('form_transfers', [
            'uid_prefix'  => 'CSN',
            'creator_id'  => null,
        ]);

        $this->assertDatabaseMissing('presensi_schedules', [
            'id' => 212,
        ]);
    }

    /**
     * @return array{creator: User, requester: User, company_id: int}
     */
    protected function createTargetUsersAndCompanies(): array
    {
        User::factory()->create(['email' => 'dummy@example.com']);
        $creator = User::factory()->create(['email' => 'creator@example.com']);
        $requester = User::factory()->create(['email' => 'requester@example.com']);

        DB::table('partners_partners')->updateOrInsert(
            ['name' => 'Dummy Partner'],
            [
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('partners_partners')->updateOrInsert(
            ['name' => 'CSN Partner'],
            [
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $dummyPartnerId = (int) DB::table('partners_partners')->where('name', 'Dummy Partner')->value('id');
        $csnPartnerId = (int) DB::table('partners_partners')->where('name', 'CSN Partner')->value('id');

        DB::table('companies')->insert([
            [
                'name'       => 'Dummy Company',
                'company_id' => 'DUMMY',
                'partner_id' => $dummyPartnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Complete Solusi Nusantara',
                'company_id' => 'CSN',
                'partner_id' => $csnPartnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'creator'    => $creator,
            'requester'  => $requester,
            'company_id' => (int) DB::table('companies')->where('company_id', 'CSN')->value('id'),
        ];
    }

    protected function createTargetCompaniesOnly(): int
    {
        DB::table('partners_partners')->updateOrInsert(
            ['name' => 'Dummy Partner'],
            [
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('partners_partners')->updateOrInsert(
            ['name' => 'CSN Partner'],
            [
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $dummyPartnerId = (int) DB::table('partners_partners')->where('name', 'Dummy Partner')->value('id');
        $csnPartnerId = (int) DB::table('partners_partners')->where('name', 'CSN Partner')->value('id');

        DB::table('companies')->insert([
            [
                'name'       => 'Dummy Company',
                'company_id' => 'DUMMY',
                'partner_id' => $dummyPartnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Complete Solusi Nusantara',
                'company_id' => 'CSN',
                'partner_id' => $csnPartnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return (int) DB::table('companies')->where('company_id', 'CSN')->value('id');
    }

    protected function createMinimalEmployeesTable(): void
    {
        if (! Schema::hasTable('employees_job_positions')) {
            Schema::create('employees_job_positions', function (Blueprint $table): void {
                $table->id();
                $table->integer('sort')->nullable();
                $table->integer('expected_employees')->nullable();
                $table->integer('no_of_employee')->nullable();
                $table->integer('no_of_recruitment')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('creator_id')->nullable();
                $table->unsignedBigInteger('employment_type_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('requirements')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('employees_employees')) {
            return;
        }

        Schema::create('employees_employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->string('name')->nullable();
            $table->string('employee_code')->nullable();
            $table->string('job_title')->nullable();
            $table->string('work_email')->nullable();
            $table->string('private_email')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function createLegacySchema(): void
    {
        $schemaStatements = [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, image TEXT)',
            'CREATE TABLE companies (id INTEGER PRIMARY KEY, company_id TEXT, name TEXT)',
            'CREATE TABLE form_transfer_banks (id INTEGER PRIMARY KEY, code TEXT, name TEXT, short_name TEXT, is_active INTEGER, sort_order INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE form_transfers (id INTEGER PRIMARY KEY, company_id INTEGER, creator_id INTEGER, name TEXT, code TEXT, uid_prefix TEXT, uid_padding INTEGER, uid_sequence INTEGER, description TEXT, is_active INTEGER, approver_mail_subject TEXT, approver_mail_greeting TEXT, approver_mail_action_text TEXT, approver_mail_template TEXT, requester_mail_subject TEXT, requester_mail_greeting TEXT, requester_mail_action_text TEXT, requester_mail_template TEXT, approver_whatsapp_template TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE form_transfer_divisions (id INTEGER PRIMARY KEY, form_transfer_id INTEGER, name TEXT, code TEXT, description TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE form_transfer_reference_notes (id INTEGER PRIMARY KEY, form_transfer_id INTEGER, label TEXT, description TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE form_transfer_approval_workflows (id INTEGER PRIMARY KEY, form_transfer_id INTEGER, division_id INTEGER, name TEXT, code TEXT, description TEXT, steps TEXT, is_active INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE transfer_requests (id INTEGER PRIMARY KEY, uid TEXT, submission_status TEXT, approval_status TEXT, realization_status TEXT, status_response_id TEXT, form_transfer_id INTEGER, company_id INTEGER, user_id INTEGER, creator_id INTEGER, requester_name TEXT, division_name TEXT, division_id INTEGER, email TEXT, account_number TEXT, account_name TEXT, bank_id INTEGER, transfer_amount NUMERIC, purpose TEXT, reference_note TEXT, invoice_path TEXT, account_attachment_path TEXT, realized_at TEXT, realization_proof_path TEXT, realization_notes TEXT, approval_workflow_id INTEGER, approvals TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE ec_departments (id INTEGER PRIMARY KEY, code TEXT, name TEXT, description TEXT, head_of_department_id INTEGER, created_by INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE ec_approvers (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, title TEXT, created_by INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE ec_department_approver (department_id INTEGER, approver_id INTEGER)',
            'CREATE TABLE ec_requests (id INTEGER PRIMARY KEY, department_id INTEGER, name TEXT, email TEXT, phone TEXT, position TEXT, placement TEXT, join_date TEXT, request_date TEXT, departure_date TEXT, reason TEXT, resignation_letter_url TEXT, created_by INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT, workload_feedback TEXT, career_growth_feedback TEXT, facility_welfare_feedback TEXT, work_relationship_feedback TEXT, compensation_feedback TEXT, division_feedback TEXT, company_feedback TEXT, clearance_kartu_halo TEXT, clearance_employee_debt TEXT, clearance_uniform_return TEXT, clearance_vehicle_return TEXT, clearance_inventory_return TEXT, clearance_account_deactivation TEXT, clearance_receivable_data TEXT, clearance_promotor_internal TEXT, clearance_nota_pending TEXT, clearance_stock_opname TEXT, form_uid TEXT, form_status TEXT, form_response_id TEXT)',
            'CREATE TABLE ec_request_approver (request_id INTEGER, approver_id INTEGER, approved_at TEXT, notes TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE offices (id INTEGER PRIMARY KEY, name TEXT, latitude REAL, longitude REAL, created_at TEXT, updated_at TEXT, deleted_at TEXT, radius INTEGER)',
            'CREATE TABLE shifts (id INTEGER PRIMARY KEY, name TEXT, start_time TEXT, end_time TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE schedules (id INTEGER PRIMARY KEY, user_id INTEGER, shift_id INTEGER, office_id INTEGER, is_wfa INTEGER, is_banned INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE attendances (id INTEGER PRIMARY KEY, user_id INTEGER, schedule_latitude REAL, schedule_longitude REAL, schedule_start_time TEXT, schedule_end_time TEXT, start_latitude REAL, start_longitude REAL, start_time TEXT, end_time TEXT, is_leave INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT, end_latitude REAL, end_longitude REAL, start_photo_path TEXT, end_photo_path TEXT)',
            'CREATE TABLE leaves (id INTEGER PRIMARY KEY, user_id INTEGER, start_date TEXT, end_date TEXT, reason TEXT, status TEXT, note TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, type TEXT, attachment TEXT)',
            'CREATE TABLE overtimes (id INTEGER PRIMARY KEY, user_id INTEGER, date TEXT, start_time TEXT, end_time TEXT, reason TEXT, status TEXT, note TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT, attachment TEXT)',
            'CREATE TABLE priorities (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE ticket_statuses (id INTEGER PRIMARY KEY, name TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE units (id INTEGER PRIMARY KEY, name TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE problem_categories (id INTEGER PRIMARY KEY, unit_id INTEGER, name TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE business_entities (id INTEGER PRIMARY KEY, name TEXT, format TEXT, color TEXT, letterhead TEXT)',
            'CREATE TABLE user_entities (id INTEGER PRIMARY KEY, user_id INTEGER, entity_id INTEGER, entity_type TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE tickets (id INTEGER PRIMARY KEY, priority_id INTEGER, unit_id INTEGER, owner_id INTEGER, problem_category_id INTEGER, title TEXT, description TEXT, ticket_statuses_id INTEGER, responsible_id INTEGER, created_at TEXT, updated_at TEXT, approved_at TEXT, solved_at TEXT, deleted_at TEXT, supporting_attachments TEXT, business_entities_id INTEGER)',
            'CREATE TABLE comments (id INTEGER PRIMARY KEY, tiket_id INTEGER, user_id INTEGER, comment TEXT, attachments TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE ticket_histories (id INTEGER PRIMARY KEY, ticket_id INTEGER, ticket_statuses_id INTEGER, user_id INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT, parent_id INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE brands (id INTEGER PRIMARY KEY, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE asset_locations (id INTEGER PRIMARY KEY, name TEXT, address TEXT, description TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE vendors (id INTEGER PRIMARY KEY, name TEXT, last_price NUMERIC, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE assets (id INTEGER PRIMARY KEY, purchase_date TEXT, business_entity_id INTEGER, name TEXT, image TEXT, category_id INTEGER, brand_id INTEGER, type TEXT, serial_number TEXT, imei1 TEXT, imei2 TEXT, item_price NUMERIC, asset_location_id INTEGER, qty INTEGER, is_available INTEGER, condition_status TEXT, nbh_status TEXT, nbh_reported_at TEXT, audit_document_path TEXT, nbh_document_path TEXT, nbh_notes TEXT, nbh_responsible_user_id INTEGER, recipient_id INTEGER, recipient_business_entity_id INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE custom_asset_attributes (id INTEGER PRIMARY KEY, name TEXT, type TEXT, required INTEGER, is_active INTEGER, category_id TEXT, is_notifiable INTEGER, notification_type TEXT, notification_offset INTEGER, fixed_notification_date TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE asset_attributes (id INTEGER PRIMARY KEY, asset_id INTEGER, custom_attribute_id INTEGER, attribute_value TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE tasks (id INTEGER PRIMARY KEY, code TEXT, business_entity_id INTEGER, name TEXT, description TEXT, vendor_id INTEGER, cost NUMERIC, location TEXT, status TEXT, attachment TEXT, work_timestamp TEXT, user_id INTEGER, document_upload TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE asset_transfers (id INTEGER PRIMARY KEY, business_entity_id INTEGER, letter_number TEXT, from_user_id INTEGER, to_user_id INTEGER, transfer_date TEXT, document TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE asset_transfer_details (id INTEGER PRIMARY KEY, asset_transfer_id INTEGER, asset_id INTEGER, equipment TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE vehicle_checksheets (id INTEGER PRIMARY KEY, asset_id INTEGER, reference_number TEXT, pic TEXT, license_plate TEXT, location TEXT, destination TEXT, remarks TEXT, start_km INTEGER, departure_time TEXT, departure_photo TEXT, departure_damage_report TEXT, end_km INTEGER, return_time TEXT, return_photo TEXT, return_damage_report TEXT, rental_duration REAL, distance_traveled REAL, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE approval_levels (id INTEGER PRIMARY KEY, request_type TEXT, division TEXT, level INTEGER, approver_name TEXT, approver_email TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE asset_requests (id INTEGER PRIMARY KEY, uuid TEXT, request_type TEXT, requester_name TEXT, email TEXT, division TEXT, approval_track TEXT, placement TEXT, item_name TEXT, qty INTEGER, attachment_path TEXT, attachment_original_name TEXT, status TEXT, admin_notes TEXT, user_id INTEGER, asset_id INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE request_approvals (id INTEGER PRIMARY KEY, asset_request_id INTEGER, approval_level_id INTEGER, token TEXT, level INTEGER, approver_name TEXT, approver_email TEXT, status TEXT, notes TEXT, responded_at TEXT, created_at TEXT, updated_at TEXT)',
        ];

        foreach ($schemaStatements as $statement) {
            DB::connection('legacy_sync')->statement($statement);
        }
    }

    protected function seedLegacyRecords(): void
    {
        DB::connection('legacy_sync')->table('users')->insert([
            ['id' => 10, 'email' => 'creator@example.com', 'image' => null],
            ['id' => 11, 'email' => 'requester@example.com', 'image' => 'legacy/requester.png'],
        ]);

        DB::connection('legacy_sync')->table('companies')->insert([
            ['id' => 50, 'company_id' => 'CSN', 'name' => 'Complete Solusi Nusantara'],
        ]);

        DB::connection('legacy_sync')->table('priorities')->insert([
            ['id' => 1, 'name' => 'Critical/Urgent'],
            ['id' => 2, 'name' => 'High'],
        ]);

        DB::connection('legacy_sync')->table('ticket_statuses')->insert([
            ['id' => 1, 'name' => 'Open', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
            ['id' => 2, 'name' => 'In Progress', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
            ['id' => 3, 'name' => 'Cancel', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
            ['id' => 4, 'name' => 'Closed', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
        ]);

        DB::connection('legacy_sync')->table('units')->insert([
            ['id' => 1, 'name' => 'IT', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
        ]);

        DB::connection('legacy_sync')->table('problem_categories')->insert([
            ['id' => 1, 'unit_id' => 1, 'name' => 'Software', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00', 'deleted_at' => null],
        ]);

        DB::connection('legacy_sync')->table('business_entities')->insert([
            ['id' => 1, 'name' => 'Complete Solusi Nusantara'],
        ]);

        DB::connection('legacy_sync')->table('user_entities')->insert([
            ['id' => 1, 'user_id' => 10, 'entity_id' => 1, 'entity_type' => 'App\\\\Models\\\\Unit', 'created_at' => '2026-03-10 08:00:00', 'updated_at' => '2026-03-10 08:00:00'],
        ]);

        DB::connection('legacy_sync')->table('tickets')->insert([
            'id'                     => 400,
            'priority_id'            => 1,
            'unit_id'                => 1,
            'owner_id'               => 11,
            'problem_category_id'    => 1,
            'title'                  => 'Laptop blue screen',
            'description'            => '<p>Device crashes after login.</p>',
            'ticket_statuses_id'     => 2,
            'responsible_id'         => 10,
            'created_at'             => '2026-03-10 08:00:00',
            'updated_at'             => '2026-03-10 09:00:00',
            'approved_at'            => '2026-03-10 08:15:00',
            'solved_at'              => null,
            'deleted_at'             => null,
            'supporting_attachments' => json_encode(['helpdesk/evidence.pdf'], JSON_UNESCAPED_UNICODE),
            'business_entities_id'   => 1,
        ]);

        DB::connection('legacy_sync')->table('comments')->insert([
            'id'         => 401,
            'tiket_id'   => 400,
            'user_id'    => 10,
            'comment'    => 'Sedang dicek oleh tim IT.',
            'attachments'=> 'helpdesk/comment-proof.png',
            'created_at' => '2026-03-10 08:30:00',
            'updated_at' => '2026-03-10 08:30:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_sync')->table('ticket_histories')->insert([
            'id'                 => 402,
            'ticket_id'          => 400,
            'ticket_statuses_id' => 2,
            'user_id'            => 10,
            'created_at'         => '2026-03-10 08:15:00',
            'updated_at'         => '2026-03-10 08:15:00',
        ]);

        DB::connection('legacy_sync')->table('form_transfer_banks')->insert([
            'id'         => 1,
            'code'       => 'BCA',
            'name'       => 'Bank Central Asia',
            'short_name' => 'BCA',
            'is_active'  => 1,
            'sort_order' => 1,
            'created_at' => '2026-03-10 10:00:00',
            'updated_at' => '2026-03-10 10:00:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_sync')->table('form_transfers')->insert([
            'id'                         => 100,
            'company_id'                 => 50,
            'creator_id'                 => 10,
            'name'                       => 'Form Transfer CSN',
            'code'                       => 'CSN',
            'uid_prefix'                 => 'CSN',
            'uid_padding'                => 5,
            'uid_sequence'               => 1,
            'description'                => 'Legacy form transfer',
            'is_active'                  => 1,
            'approver_mail_subject'      => 'Approval',
            'approver_mail_greeting'     => null,
            'approver_mail_action_text'  => null,
            'approver_mail_template'     => null,
            'requester_mail_subject'     => 'Requester',
            'requester_mail_greeting'    => null,
            'requester_mail_action_text' => null,
            'requester_mail_template'    => null,
            'approver_whatsapp_template' => null,
            'created_at'                 => '2026-03-10 10:00:00',
            'updated_at'                 => '2026-03-10 10:00:00',
            'deleted_at'                 => null,
        ]);

        DB::connection('legacy_sync')->table('form_transfer_divisions')->insert([
            'id'               => 101,
            'form_transfer_id' => 100,
            'name'             => 'IT',
            'code'             => null,
            'description'      => null,
            'is_active'        => 1,
            'created_at'       => '2026-03-10 10:00:00',
            'updated_at'       => '2026-03-10 10:00:00',
            'deleted_at'       => null,
        ]);

        DB::connection('legacy_sync')->table('form_transfer_reference_notes')->insert([
            'id'               => 102,
            'form_transfer_id' => 100,
            'label'            => 'Head Office',
            'description'      => 'Head Office',
            'is_active'        => 1,
            'created_at'       => '2026-03-10 10:00:00',
            'updated_at'       => '2026-03-10 10:00:00',
            'deleted_at'       => null,
        ]);

        DB::connection('legacy_sync')->table('form_transfer_approval_workflows')->insert([
            'id'               => 103,
            'form_transfer_id' => 100,
            'division_id'      => 101,
            'name'             => '',
            'code'             => null,
            'description'      => null,
            'steps'            => json_encode([[
                'label'         => 'Tahap 1',
                'default_name'  => 'Approver',
                'default_email' => 'approver@example.com',
                'status'        => 'pending',
            ]], JSON_UNESCAPED_UNICODE),
            'is_active'        => 1,
            'created_at'       => '2026-03-10 10:00:00',
            'updated_at'       => '2026-03-10 10:00:00',
            'deleted_at'       => null,
        ]);

        DB::connection('legacy_sync')->table('transfer_requests')->insert([
            'id'                      => 104,
            'uid'                     => 'CSN-00001',
            'submission_status'       => 'baru',
            'approval_status'         => 'approved',
            'realization_status'      => 'done',
            'status_response_id'      => 'legacy-status-1',
            'form_transfer_id'        => 100,
            'company_id'              => 50,
            'user_id'                 => 11,
            'creator_id'              => 10,
            'requester_name'          => 'Legacy Requester',
            'division_name'           => 'IT',
            'division_id'             => 101,
            'email'                   => 'legacy.requester@example.com',
            'account_number'          => '1234567890',
            'account_name'            => 'Legacy Requester',
            'bank_id'                 => 1,
            'transfer_amount'         => 75000,
            'purpose'                 => 'Initial legacy purpose',
            'reference_note'          => 'Head Office',
            'invoice_path'            => null,
            'account_attachment_path' => null,
            'realized_at'             => '2026-03-11',
            'realization_proof_path'  => null,
            'realization_notes'       => 'Legacy done',
            'approval_workflow_id'    => 103,
            'approvals'               => json_encode([[
                'label'  => 'Tahap 1',
                'email'  => 'approver@example.com',
                'status' => 'approved',
            ]], JSON_UNESCAPED_UNICODE),
            'created_at'              => '2026-03-10 10:00:00',
            'updated_at'              => '2026-03-10 10:00:00',
            'deleted_at'              => null,
        ]);

        DB::connection('legacy_sync')->table('ec_departments')->insert([
            'id'                    => 200,
            'code'                  => 'HR',
            'name'                  => 'Human Resource',
            'description'           => 'Legacy HR',
            'head_of_department_id' => null,
            'created_by'            => 10,
            'created_at'            => '2026-03-10 11:00:00',
            'updated_at'            => '2026-03-10 11:00:00',
            'deleted_at'            => null,
        ]);

        DB::connection('legacy_sync')->table('ec_approvers')->insert([
            'id'         => 201,
            'name'       => 'Legacy Approver',
            'email'      => 'approver@example.com',
            'phone'      => '08123456789',
            'title'      => 'HR Manager',
            'created_by' => 10,
            'created_at' => '2026-03-10 11:00:00',
            'updated_at' => '2026-03-10 11:00:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_sync')->table('ec_department_approver')->insert([
            'department_id' => 200,
            'approver_id'   => 201,
        ]);

        DB::connection('legacy_sync')->table('ec_requests')->insert([
            'id'                             => 202,
            'department_id'                  => 200,
            'name'                           => 'Exit Legacy User',
            'email'                          => 'exit@example.com',
            'phone'                          => '0899999999',
            'position'                       => 'Staff',
            'placement'                      => 'Bandung',
            'join_date'                      => '2025-01-01',
            'request_date'                   => '2026-03-11',
            'departure_date'                 => '2026-03-20',
            'reason'                         => 'Legacy reason',
            'resignation_letter_url'         => 'resignation-letters/legacy.pdf',
            'created_by'                     => 10,
            'created_at'                     => '2026-03-10 11:00:00',
            'updated_at'                     => '2026-03-10 11:00:00',
            'deleted_at'                     => null,
            'workload_feedback'              => 'Workload',
            'career_growth_feedback'         => 'Growth',
            'facility_welfare_feedback'      => 'Facility',
            'work_relationship_feedback'     => 'Relationship',
            'compensation_feedback'          => 'Compensation',
            'division_feedback'              => 'Division',
            'company_feedback'               => 'Company',
            'clearance_kartu_halo'           => 'Done',
            'clearance_employee_debt'        => 'Done',
            'clearance_uniform_return'       => 'Done',
            'clearance_vehicle_return'       => 'Done',
            'clearance_inventory_return'     => 'Done',
            'clearance_account_deactivation' => 'Done',
            'clearance_receivable_data'      => 'Done',
            'clearance_promotor_internal'    => 'Done',
            'clearance_nota_pending'         => 'Done',
            'clearance_stock_opname'         => 'Done',
            'form_uid'                       => 'EXC-00001',
            'form_status'                    => 'Approved',
            'form_response_id'               => 'legacy-form-response-1',
        ]);

        DB::connection('legacy_sync')->table('ec_request_approver')->insert([
            'request_id'   => 202,
            'approver_id'  => 201,
            'approved_at'  => '2026-03-11 09:00:00',
            'notes'        => 'Legacy approved',
            'status'       => 'approved',
            'created_at'   => '2026-03-10 11:00:00',
            'updated_at'   => '2026-03-11 09:00:00',
        ]);

        DB::connection('legacy_sync')->table('offices')->insert([
            'id'         => 210,
            'name'       => 'Head Office',
            'latitude'   => -6.2,
            'longitude'  => 106.8,
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
            'deleted_at' => null,
            'radius'     => 100,
        ]);

        DB::connection('legacy_sync')->table('shifts')->insert([
            'id'         => 211,
            'name'       => 'Shift Pagi',
            'start_time' => '09:00:00',
            'end_time'   => '18:00:00',
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_sync')->table('schedules')->insert([
            'id'         => 212,
            'user_id'    => 11,
            'shift_id'   => 211,
            'office_id'  => 210,
            'is_wfa'     => 0,
            'is_banned'  => 0,
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_sync')->table('attendances')->insert([
            'id'                  => 300,
            'user_id'             => 11,
            'schedule_latitude'   => -6.2,
            'schedule_longitude'  => 106.8,
            'schedule_start_time' => '09:00:00',
            'schedule_end_time'   => '18:00:00',
            'start_latitude'      => -6.2001,
            'start_longitude'     => 106.8001,
            'start_time'          => '09:05:00',
            'end_time'            => '18:01:00',
            'is_leave'            => 0,
            'created_at'          => '2026-03-10 09:05:00',
            'updated_at'          => '2026-03-10 18:01:00',
            'deleted_at'          => null,
            'end_latitude'        => -6.2002,
            'end_longitude'       => 106.8002,
            'start_photo_path'    => 'attendance/start.jpg',
            'end_photo_path'      => 'attendance/end.jpg',
        ]);

        DB::connection('legacy_sync')->table('leaves')->insert([
            'id'         => 301,
            'user_id'    => 11,
            'start_date' => '2026-03-12',
            'end_date'   => '2026-03-12',
            'reason'     => 'Medical',
            'status'     => 'approved',
            'note'       => 'Legacy leave',
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
            'deleted_at' => null,
            'type'       => null,
            'attachment' => 'leave/attachment.pdf',
        ]);

        DB::connection('legacy_sync')->table('overtimes')->insert([
            'id'         => 302,
            'user_id'    => 11,
            'date'       => '2026-03-13',
            'start_time' => '19:00:00',
            'end_time'   => '21:00:00',
            'reason'     => 'Release deploy',
            'status'     => 'pending',
            'note'       => 'Legacy OT',
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
            'deleted_at' => null,
            'attachment' => 'overtime/attachment.pdf',
        ]);

        DB::connection('legacy_sync')->table('categories')->insert([
            ['id' => 500, 'name' => 'Hardware', 'parent_id' => null, 'created_at' => '2026-03-10 07:00:00', 'updated_at' => '2026-03-10 07:00:00'],
            ['id' => 501, 'name' => 'Laptop', 'parent_id' => 500, 'created_at' => '2026-03-10 07:05:00', 'updated_at' => '2026-03-10 07:05:00'],
        ]);

        DB::connection('legacy_sync')->table('brands')->insert([
            ['id' => 510, 'name' => 'Dell', 'created_at' => '2026-03-10 07:10:00', 'updated_at' => '2026-03-10 07:10:00'],
        ]);

        DB::connection('legacy_sync')->table('asset_locations')->insert([
            ['id' => 520, 'name' => 'Gudang Pusat', 'address' => 'Jl. Legacy No. 1', 'description' => 'Rak A1', 'created_at' => '2026-03-10 07:15:00', 'updated_at' => '2026-03-10 07:15:00'],
        ]);

        DB::connection('legacy_sync')->table('vendors')->insert([
            ['id' => 530, 'name' => 'CV Legacy Service', 'last_price' => 250000, 'created_at' => '2026-03-10 07:20:00', 'updated_at' => '2026-03-10 07:20:00'],
        ]);

        DB::connection('legacy_sync')->table('assets')->insert([
            [
                'id'                           => 540,
                'purchase_date'                => '2026-03-01',
                'business_entity_id'           => 50,
                'name'                         => 'Laptop Kerja Legacy',
                'image'                        => 'shelf/laptop.jpg',
                'category_id'                  => 501,
                'brand_id'                     => 510,
                'type'                         => 'Laptop',
                'serial_number'                => 'SN-LAP-001',
                'imei1'                        => null,
                'imei2'                        => null,
                'item_price'                   => 15000000,
                'asset_location_id'            => 520,
                'qty'                          => 1,
                'is_available'                 => 1,
                'condition_status'             => 'available',
                'nbh_status'                   => 'none',
                'nbh_reported_at'              => null,
                'audit_document_path'          => null,
                'nbh_document_path'            => null,
                'nbh_notes'                    => null,
                'nbh_responsible_user_id'      => 10,
                'recipient_id'                 => 11,
                'recipient_business_entity_id' => 50,
                'created_at'                   => '2026-03-10 07:30:00',
                'updated_at'                   => '2026-03-10 07:30:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('custom_asset_attributes')->insert([
            [
                'id'                      => 550,
                'name'                    => 'Masa Garansi',
                'type'                    => 'date',
                'required'                => 1,
                'is_active'               => 1,
                'category_id'             => json_encode([501], JSON_UNESCAPED_UNICODE),
                'is_notifiable'           => 1,
                'notification_type'       => 'fixed_date',
                'notification_offset'     => null,
                'fixed_notification_date' => '2027-03-10',
                'created_at'              => '2026-03-10 07:35:00',
                'updated_at'              => '2026-03-10 07:35:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('asset_attributes')->insert([
            [
                'id'                  => 551,
                'asset_id'            => 540,
                'custom_attribute_id' => 550,
                'attribute_value'     => '2027-03-10',
                'created_at'          => '2026-03-10 07:36:00',
                'updated_at'          => '2026-03-10 07:36:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('tasks')->insert([
            [
                'id'                 => 560,
                'code'               => 'TSK-001',
                'business_entity_id' => 50,
                'name'               => 'Perawatan Laptop',
                'description'        => 'Servis berkala laptop kerja',
                'vendor_id'          => 530,
                'cost'               => 300000,
                'location'           => 'Jakarta',
                'status'             => 'in_progress',
                'attachment'         => json_encode(['tasks/photo-1.jpg'], JSON_UNESCAPED_UNICODE),
                'work_timestamp'     => '2026-03-10 08:00:00',
                'user_id'            => 10,
                'document_upload'    => 'tasks/report.pdf',
                'created_at'         => '2026-03-10 07:40:00',
                'updated_at'         => '2026-03-10 08:10:00',
                'deleted_at'         => null,
            ],
        ]);

        DB::connection('legacy_sync')->table('asset_transfers')->insert([
            [
                'id'                 => 570,
                'business_entity_id' => 50,
                'letter_number'      => 'BAST-001',
                'from_user_id'       => 10,
                'to_user_id'         => 11,
                'transfer_date'      => '2026-03-11 09:00:00',
                'document'           => 'transfers/bast-001.pdf',
                'created_at'         => '2026-03-11 09:00:00',
                'updated_at'         => '2026-03-11 09:00:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('asset_transfer_details')->insert([
            [
                'id'                => 571,
                'asset_transfer_id' => 570,
                'asset_id'          => 540,
                'equipment'         => 'Adaptor',
                'created_at'        => '2026-03-11 09:00:00',
                'updated_at'        => '2026-03-11 09:00:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('vehicle_checksheets')->insert([
            [
                'id'                      => 580,
                'asset_id'                => 540,
                'reference_number'        => 'GA-2026-001',
                'pic'                     => 'Legacy Driver',
                'license_plate'           => 'B 1234 CD',
                'location'                => 'Jakarta',
                'destination'             => 'Site Alpha',
                'remarks'                 => 'Berangkat pagi',
                'start_km'                => 1000,
                'departure_time'          => '2026-03-12 08:00:00',
                'departure_photo'         => 'checksheets/departure.jpg',
                'departure_damage_report' => 'checksheets/departure-damage.jpg',
                'end_km'                  => 1050,
                'return_time'             => '2026-03-12 17:00:00',
                'return_photo'            => 'checksheets/return.jpg',
                'return_damage_report'    => 'checksheets/return-damage.jpg',
                'rental_duration'         => 0.375,
                'distance_traveled'       => 50,
                'created_at'              => '2026-03-12 08:00:00',
                'updated_at'              => '2026-03-12 17:00:00',
                'deleted_at'              => null,
            ],
        ]);

        DB::connection('legacy_sync')->table('approval_levels')->insert([
            [
                'id'             => 590,
                'request_type'   => 'penarikan_aset',
                'division'       => 'IT',
                'level'          => 1,
                'approver_name'  => 'Asset Approver',
                'approver_email' => 'asset.approver@example.com',
                'created_at'     => '2026-03-12 10:00:00',
                'updated_at'     => '2026-03-12 10:00:00',
            ],
        ]);

        DB::connection('legacy_sync')->table('asset_requests')->insert([
            [
                'id'                       => 1001,
                'uuid'                     => 'asset-request-uuid-1',
                'request_type'             => 'penarikan_aset',
                'requester_name'           => 'Legacy Requester',
                'email'                    => 'requester@example.com',
                'division'                 => 'IT',
                'approval_track'           => 'IT',
                'placement'                => 'Jakarta',
                'item_name'                => 'Laptop Kerja Legacy',
                'qty'                      => 1,
                'attachment_path'          => 'asset-requests/request-1.pdf',
                'attachment_original_name' => 'request-1.pdf',
                'status'                   => 'pending',
                'admin_notes'              => 'Menunggu approval',
                'user_id'                  => 11,
                'asset_id'                 => 540,
                'created_at'               => '2026-03-12 10:05:00',
                'updated_at'               => '2026-03-12 10:05:00',
                'deleted_at'               => null,
            ],
        ]);

        DB::connection('legacy_sync')->table('request_approvals')->insert([
            [
                'id'                => 1002,
                'asset_request_id'  => 1001,
                'approval_level_id' => 590,
                'token'             => 'asset-approval-token-1',
                'level'             => 1,
                'approver_name'     => 'Asset Approver',
                'approver_email'    => 'asset.approver@example.com',
                'status'            => 'pending',
                'notes'             => null,
                'responded_at'      => null,
                'created_at'        => '2026-03-12 10:05:00',
                'updated_at'        => '2026-03-12 10:05:00',
            ],
        ]);
    }
}
