<?php

namespace Cesa\LegacySync\Console\Commands;

use Cesa\ExitClearance\Models\Request as ExitClearanceRequest;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Security\Models\User as SecurityUser;

class SyncLegacySqlData extends Command
{
    protected $signature = 'legacy:sync
                            {--module=* : Modules to sync (form-transfer, exit-clearance, presensi)}
                            {--connection=legacy_sync : Legacy database connection name}
                            {--host= : Override legacy DB host}
                            {--port= : Override legacy DB port}
                            {--database= : Override legacy DB database}
                            {--username= : Override legacy DB username}
                            {--password= : Override legacy DB password}
                            {--truncate : Truncate target module tables before syncing}
                            {--chunk=250 : Chunk size for large legacy tables}
                            {--create-missing-users : Deprecated compatibility flag; missing legacy users are created automatically}
                            {--skip-missing-users : Skip creating web-cesa users from legacy users when no match is found}
                            {--trust-legacy-user-ids : Fallback to legacy user IDs when no email mapping is available}
                            {--trust-legacy-company-ids : Fallback to legacy company IDs when no company mapping is available}';

    protected $description = 'Sync legacy SQL tables into the latest web-cesa schema.';

    /**
     * @var array<int, string>
     */
    protected array $availableModules = ['form-transfer', 'exit-clearance', 'presensi'];

    protected string $legacyConnection = 'legacy_sync';

    /**
     * @var array<int, array{name: string|null, email: string|null, password: string|null, remember_token: string|null, email_verified_at: mixed, created_at: mixed, updated_at: mixed}>
     */
    protected array $legacyUsersById = [];

    protected bool $legacyUsersLoaded = false;

    /**
     * @var array<string, int>
     */
    protected array $targetUsersByEmail = [];

    /**
     * @var array<int, array{company_id: string|null, name: string|null}>
     */
    protected array $legacyCompaniesById = [];

    protected bool $legacyCompaniesLoaded = false;

    /**
     * @var array<string, int>
     */
    protected array $targetCompaniesByCompanyCode = [];

    /**
     * @var array<string, bool>
     */
    protected array $emittedWarnings = [];

    /**
     * @var array<int, int>
     */
    protected array $syncedExitRequestIds = [];

    public function handle(): int
    {
        try {
            $modules = $this->resolveModules();

            $this->setupLegacyConnection();
            $this->verifyLegacyConnection();

            $this->info(sprintf(
                'Connected to legacy database using connection [%s].',
                $this->legacyConnection
            ));

            if ($this->shouldTruncate()) {
                $this->warn('Truncate mode is enabled. Target module tables will be emptied before syncing.');
            } else {
                $this->info('Upsert mode is enabled. Existing mapped records will be updated.');
            }

            if ($this->shouldCreateMissingUsers()) {
                $this->info('Missing legacy users will be created automatically when needed.');
            }

            foreach ($modules as $module) {
                match ($module) {
                    'form-transfer'  => $this->syncFormTransferModule(),
                    'exit-clearance' => $this->syncExitClearanceModule(),
                    'presensi'       => $this->syncPresensiModule(),
                };
            }

            $this->info('Legacy sync completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            report($throwable);
            $this->error('Legacy sync failed: '.$throwable->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveModules(): array
    {
        $input = $this->option('module');

        if (! is_array($input) || $input === []) {
            return $this->availableModules;
        }

        $modules = collect($input)
            ->flatMap(fn (mixed $value): array => explode(',', (string) $value))
            ->map(fn (string $value): string => strtolower(trim($value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $invalidModules = array_values(array_diff($modules, $this->availableModules));

        if ($invalidModules !== []) {
            throw new \InvalidArgumentException(
                'Unknown module(s): '.implode(', ', $invalidModules)
            );
        }

        return $modules === [] ? $this->availableModules : $modules;
    }

    protected function setupLegacyConnection(): void
    {
        $connectionName = (string) $this->option('connection');
        $baseConfig = config('legacy-sync.connections.'.$connectionName);

        if (! is_array($baseConfig)) {
            $baseConfig = config('database.connections.'.$connectionName);
        }

        if (! is_array($baseConfig)) {
            throw new \RuntimeException(sprintf(
                'Legacy connection [%s] is not configured.',
                $connectionName
            ));
        }

        $config = array_merge($baseConfig, array_filter([
            'host'     => $this->option('host'),
            'port'     => $this->option('port'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
        ], fn (mixed $value): bool => $value !== null && $value !== ''));

        config(['database.connections.'.$connectionName => $config]);

        DB::purge($connectionName);
        DB::reconnect($connectionName);

        $this->legacyConnection = $connectionName;
    }

    protected function verifyLegacyConnection(): void
    {
        DB::connection($this->legacyConnection)->getPdo();
    }

    protected function shouldTruncate(): bool
    {
        return (bool) $this->option('truncate');
    }

    protected function chunkSize(): int
    {
        $size = (int) $this->option('chunk');

        return $size > 0 ? $size : 250;
    }

    protected function syncFormTransferModule(): void
    {
        $this->components->twoColumnDetail('Module', 'form-transfer');

        $requiredTables = [
            'form_transfer_banks',
            'form_transfers',
            'form_transfer_divisions',
            'form_transfer_reference_notes',
            'form_transfer_approval_workflows',
            'transfer_requests',
        ];

        if (! $this->ensureLegacyTablesExist($requiredTables, 'form-transfer')) {
            return;
        }

        if ($this->shouldTruncate()) {
            $this->truncateTables([
                'form_transfer_requests',
                'form_transfer_approval_workflows',
                'form_transfer_reference_notes',
                'form_transfer_divisions',
                'form_transfer_user_accesses',
                'form_transfers',
                'form_transfer_banks',
            ]);
        }

        $this->syncTransferBanks();
        $this->syncFormTransfers();
        $this->syncTransferDivisions();
        $this->syncTransferReferenceNotes();
        $this->syncTransferApprovalWorkflows();
        $this->syncTransferRequests();
    }

    protected function syncExitClearanceModule(): void
    {
        $this->components->twoColumnDetail('Module', 'exit-clearance');

        $requiredTables = [
            'ec_departments',
            'ec_approvers',
            'ec_department_approver',
            'ec_requests',
            'ec_request_approver',
        ];

        if (! $this->ensureLegacyTablesExist($requiredTables, 'exit-clearance')) {
            return;
        }

        if ($this->shouldTruncate()) {
            $this->truncateTables([
                'exit_clearance_request_approver',
                'exit_clearance_department_approver',
                'exit_clearance_requests',
                'exit_clearance_approvers',
                'exit_clearance_departments',
            ]);
        }

        $this->syncedExitRequestIds = [];

        $this->syncExitClearanceDepartments();
        $this->syncExitClearanceApprovers();
        $this->syncExitClearanceDepartmentApprovers();
        $this->syncExitClearanceRequests();
        $this->syncExitClearanceRequestApprovers();
        $this->refreshExitClearanceStatuses();
    }

    protected function syncPresensiModule(): void
    {
        $this->components->twoColumnDetail('Module', 'presensi');

        $requiredTables = [
            'users',
            'offices',
            'shifts',
            'attendances',
            'leaves',
            'overtimes',
        ];

        if (! $this->ensureLegacyTablesExist($requiredTables, 'presensi')) {
            return;
        }

        if ($this->shouldTruncate()) {
            $tables = [
                'presensi_attendances',
                'presensi_leaves',
                'presensi_overtimes',
                'presensi_schedules',
                'presensi_shifts',
                'presensi_offices',
            ];

            $this->truncateTables($tables);
        }

        $this->syncPresensiOffices();
        $this->syncPresensiShifts();
        $this->syncPresensiUserImages();
        $this->syncPresensiSchedules();
        $this->syncPresensiAttendances();
        $this->syncPresensiLeaves();
        $this->syncPresensiOvertimes();
    }

    /**
     * @param  array<int, string>  $tables
     */
    protected function ensureLegacyTablesExist(array $tables, string $module): bool
    {
        $missingTables = array_values(array_filter(
            $tables,
            fn (string $table): bool => ! Schema::connection($this->legacyConnection)->hasTable($table)
        ));

        if ($missingTables === []) {
            return true;
        }

        $this->warn(sprintf(
            'Skipping module [%s]. Missing legacy table(s): %s',
            $module,
            implode(', ', $missingTables)
        ));

        return false;
    }

    /**
     * @param  array<int, string>  $tables
     */
    protected function truncateTables(array $tables): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    protected function syncTransferBanks(): void
    {
        $query = DB::connection($this->legacyConnection)->table('form_transfer_banks');

        $this->syncRows('Form transfer banks', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'form_transfer_banks',
                $row->id,
                'form_transfer_banks',
                fn (): ?int => $this->nullableInt(
                    DB::table('form_transfer_banks')
                        ->where('code', (string) $row->code)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfer_banks')->updateOrInsert(
                ['id' => $targetId],
                [
                    'code'       => $this->nullableString($row->code) ?? '',
                    'name'       => $this->nullableString($row->name) ?? '',
                    'short_name' => $this->nullableString($row->short_name),
                    'is_active'  => $this->normalizeBoolean($row->is_active, true),
                    'sort_order' => (int) ($row->sort_order ?? 0),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('form_transfer_banks', $row->id, 'form_transfer_banks', $targetId);
        });
    }

    protected function syncFormTransfers(): void
    {
        $query = DB::connection($this->legacyConnection)->table('form_transfers');

        $this->syncRows('Form transfers', $query, function (object $row): void {
            $companyId = $this->resolveCompanyId($this->nullableInt($row->company_id));
            $creatorId = $this->resolveUserId($this->nullableInt($row->creator_id));

            $targetId = $this->resolveTargetId(
                'form_transfers',
                $row->id,
                'form_transfers',
                fn (): ?int => $this->findFormTransferId(
                    $companyId,
                    $this->nullableString($row->uid_prefix),
                    $this->nullableString($row->code)
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfers')->updateOrInsert(
                ['id' => $targetId],
                [
                    'company_id'                 => $companyId,
                    'creator_id'                 => $creatorId,
                    'name'                       => $this->nullableString($row->name) ?? '',
                    'code'                       => $this->nullableString($row->code),
                    'uid_prefix'                 => $this->nullableString($row->uid_prefix) ?? '',
                    'uid_padding'                => (int) ($row->uid_padding ?? 5),
                    'uid_sequence'               => (int) ($row->uid_sequence ?? 0),
                    'description'                => $this->nullableString($row->description),
                    'is_active'                  => $this->normalizeBoolean($row->is_active, true),
                    'approver_mail_subject'      => $this->nullableString($row->approver_mail_subject),
                    'approver_mail_greeting'     => $this->nullableString($row->approver_mail_greeting),
                    'approver_mail_action_text'  => $this->nullableString($row->approver_mail_action_text),
                    'approver_mail_template'     => $this->nullableString($row->approver_mail_template),
                    'requester_mail_subject'     => $this->nullableString($row->requester_mail_subject),
                    'requester_mail_greeting'    => $this->nullableString($row->requester_mail_greeting),
                    'requester_mail_action_text' => $this->nullableString($row->requester_mail_action_text),
                    'requester_mail_template'    => $this->nullableString($row->requester_mail_template),
                    'approver_whatsapp_template' => $this->nullableString($row->approver_whatsapp_template),
                    'created_at'                 => $row->created_at,
                    'updated_at'                 => $row->updated_at,
                    'deleted_at'                 => $row->deleted_at,
                ],
            );

            $this->rememberMapping('form_transfers', $row->id, 'form_transfers', $targetId);
        });
    }

    protected function syncTransferDivisions(): void
    {
        $query = DB::connection($this->legacyConnection)->table('form_transfer_divisions');

        $this->syncRows('Form transfer divisions', $query, function (object $row): void {
            $formTransferId = $this->mappedTargetId('form_transfers', $row->form_transfer_id, 'form_transfers');

            if ($formTransferId === null) {
                $this->warnMissingRelation('form_transfer_divisions', $row->id, 'form_transfer_id', $row->form_transfer_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'form_transfer_divisions',
                $row->id,
                'form_transfer_divisions',
                fn (): ?int => $this->findTransferDivisionId(
                    $formTransferId,
                    $this->nullableString($row->name),
                    $this->nullableString($row->code)
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfer_divisions')->updateOrInsert(
                ['id' => $targetId],
                [
                    'form_transfer_id' => $formTransferId,
                    'name'             => $this->nullableString($row->name) ?? '',
                    'code'             => $this->nullableString($row->code),
                    'description'      => $this->nullableString($row->description),
                    'is_active'        => $this->normalizeBoolean($row->is_active, true),
                    'created_at'       => $row->created_at,
                    'updated_at'       => $row->updated_at,
                    'deleted_at'       => $row->deleted_at,
                ],
            );

            $this->rememberMapping('form_transfer_divisions', $row->id, 'form_transfer_divisions', $targetId);
        });
    }

    protected function syncTransferReferenceNotes(): void
    {
        $query = DB::connection($this->legacyConnection)->table('form_transfer_reference_notes');

        $this->syncRows('Form transfer reference notes', $query, function (object $row): void {
            $formTransferId = $this->mappedTargetId('form_transfers', $row->form_transfer_id, 'form_transfers');

            if ($formTransferId === null) {
                $this->warnMissingRelation('form_transfer_reference_notes', $row->id, 'form_transfer_id', $row->form_transfer_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'form_transfer_reference_notes',
                $row->id,
                'form_transfer_reference_notes',
                fn (): ?int => $this->nullableInt(
                    DB::table('form_transfer_reference_notes')
                        ->where('form_transfer_id', $formTransferId)
                        ->where('label', $this->nullableString($row->label) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfer_reference_notes')->updateOrInsert(
                ['id' => $targetId],
                [
                    'form_transfer_id' => $formTransferId,
                    'label'            => $this->nullableString($row->label) ?? '',
                    'description'      => $this->nullableString($row->description),
                    'is_active'        => $this->normalizeBoolean($row->is_active, true),
                    'created_at'       => $row->created_at,
                    'updated_at'       => $row->updated_at,
                    'deleted_at'       => $row->deleted_at,
                ],
            );

            $this->rememberMapping('form_transfer_reference_notes', $row->id, 'form_transfer_reference_notes', $targetId);
        });
    }

    protected function syncTransferApprovalWorkflows(): void
    {
        $query = DB::connection($this->legacyConnection)->table('form_transfer_approval_workflows');

        $this->syncRows('Form transfer approval workflows', $query, function (object $row): void {
            $formTransferId = $this->mappedTargetId('form_transfers', $row->form_transfer_id, 'form_transfers');
            $divisionId = $this->nullableInt($row->division_id) !== null
                ? $this->mappedTargetId('form_transfer_divisions', $row->division_id, 'form_transfer_divisions')
                : null;

            if ($formTransferId === null) {
                $this->warnMissingRelation('form_transfer_approval_workflows', $row->id, 'form_transfer_id', $row->form_transfer_id);

                return;
            }

            if ($this->nullableInt($row->division_id) !== null && $divisionId === null) {
                $this->warnMissingRelation('form_transfer_approval_workflows', $row->id, 'division_id', $row->division_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'form_transfer_approval_workflows',
                $row->id,
                'form_transfer_approval_workflows',
                fn (): ?int => $this->findTransferApprovalWorkflowId(
                    $formTransferId,
                    $divisionId,
                    $this->nullableString($row->name),
                    $this->nullableString($row->code),
                    $this->normalizeJsonString($row->steps)
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfer_approval_workflows')->updateOrInsert(
                ['id' => $targetId],
                [
                    'form_transfer_id' => $formTransferId,
                    'division_id'      => $divisionId,
                    'name'             => $this->nullableString($row->name) ?? '',
                    'code'             => $this->nullableString($row->code),
                    'description'      => $this->nullableString($row->description),
                    'steps'            => $this->normalizeJsonString($row->steps),
                    'is_active'        => $this->normalizeBoolean($row->is_active, true),
                    'created_at'       => $row->created_at,
                    'updated_at'       => $row->updated_at,
                    'deleted_at'       => $row->deleted_at,
                ],
            );

            $this->rememberMapping(
                'form_transfer_approval_workflows',
                $row->id,
                'form_transfer_approval_workflows',
                $targetId
            );
        });
    }

    protected function syncTransferRequests(): void
    {
        $query = DB::connection($this->legacyConnection)->table('transfer_requests');

        $this->syncRows('Form transfer requests', $query, function (object $row): void {
            $formTransferId = $this->nullableInt($row->form_transfer_id) !== null
                ? $this->mappedTargetId('form_transfers', $row->form_transfer_id, 'form_transfers')
                : null;
            $divisionId = $this->nullableInt($row->division_id) !== null
                ? $this->mappedTargetId('form_transfer_divisions', $row->division_id, 'form_transfer_divisions')
                : null;
            $bankId = $this->nullableInt($row->bank_id) !== null
                ? $this->mappedTargetId('form_transfer_banks', $row->bank_id, 'form_transfer_banks')
                : null;
            $workflowId = $this->nullableInt($row->approval_workflow_id) !== null
                ? $this->mappedTargetId('form_transfer_approval_workflows', $row->approval_workflow_id, 'form_transfer_approval_workflows')
                : null;
            $userId = $this->resolveUserId($this->nullableInt($row->user_id));
            $creatorId = $this->resolveUserId($this->nullableInt($row->creator_id));
            $companyId = $this->resolveCompanyId($this->nullableInt($row->company_id));

            if ($companyId === null && $formTransferId !== null) {
                $companyId = $this->nullableInt(
                    DB::table('form_transfers')
                        ->where('id', $formTransferId)
                        ->value('company_id')
                );
            }

            if ($this->nullableInt($row->form_transfer_id) !== null && $formTransferId === null) {
                $this->warnMissingRelation('transfer_requests', $row->id, 'form_transfer_id', $row->form_transfer_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'transfer_requests',
                $row->id,
                'form_transfer_requests',
                fn (): ?int => $this->findTransferRequestId(
                    $this->nullableString($row->uid),
                    $this->nullableString($row->status_response_id)
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('form_transfer_requests')->updateOrInsert(
                ['id' => $targetId],
                [
                    'uid'                     => $this->nullableString($row->uid) ?? '',
                    'submission_status'       => $this->normalizeTransferSubmissionStatus($row->submission_status),
                    'approval_status'         => $this->normalizeTransferApprovalStatus($row->approval_status),
                    'realization_status'      => $this->normalizeTransferRealizationStatus($row->realization_status),
                    'status_response_id'      => $this->nullableString($row->status_response_id),
                    'form_transfer_id'        => $formTransferId,
                    'company_id'              => $companyId,
                    'user_id'                 => $userId,
                    'creator_id'              => $creatorId,
                    'requester_name'          => $this->nullableString($row->requester_name) ?? '',
                    'division_name'           => $this->nullableString($row->division_name),
                    'division_id'             => $divisionId,
                    'email'                   => $this->nullableString($row->email),
                    'account_number'          => $this->nullableString($row->account_number) ?? '',
                    'account_name'            => $this->nullableString($row->account_name) ?? '',
                    'bank_id'                 => $bankId,
                    'transfer_amount'         => $row->transfer_amount ?? 0,
                    'purpose'                 => $this->nullableString($row->purpose),
                    'reference_note'          => $this->nullableString($row->reference_note),
                    'invoice_path'            => $this->nullableString($row->invoice_path),
                    'account_attachment_path' => $this->nullableString($row->account_attachment_path),
                    'realized_at'             => $row->realized_at,
                    'realization_proof_path'  => $this->nullableString($row->realization_proof_path),
                    'realization_notes'       => $this->nullableString($row->realization_notes),
                    'approval_workflow_id'    => $workflowId,
                    'approvals'               => $this->normalizeTransferApprovalsPayload($row->approvals),
                    'created_at'              => $row->created_at,
                    'updated_at'              => $row->updated_at,
                    'deleted_at'              => $row->deleted_at,
                ],
            );

            $this->rememberMapping('transfer_requests', $row->id, 'form_transfer_requests', $targetId);
        });
    }

    protected function syncExitClearanceDepartments(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ec_departments');

        $this->syncRows('Exit clearance departments', $query, function (object $row): void {
            $createdBy = $this->resolveUserId($this->nullableInt($row->created_by));

            $targetId = $this->resolveTargetId(
                'ec_departments',
                $row->id,
                'exit_clearance_departments',
                fn (): ?int => $this->nullableInt(
                    DB::table('exit_clearance_departments')
                        ->where('code', $this->nullableString($row->code) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('exit_clearance_departments')->updateOrInsert(
                ['id' => $targetId],
                [
                    'code'                  => $this->nullableString($row->code) ?? '',
                    'name'                  => $this->nullableString($row->name) ?? '',
                    'description'           => $this->nullableString($row->description),
                    'head_of_department_id' => null,
                    'created_by'            => $createdBy,
                    'created_at'            => $row->created_at,
                    'updated_at'            => $row->updated_at,
                    'deleted_at'            => $row->deleted_at,
                ],
            );

            $this->rememberMapping('ec_departments', $row->id, 'exit_clearance_departments', $targetId);
        });

        $rows = DB::connection($this->legacyConnection)
            ->table('ec_departments')
            ->whereNotNull('head_of_department_id')
            ->get();

        foreach ($rows as $row) {
            $targetId = $this->mappedTargetId('ec_departments', $row->id, 'exit_clearance_departments');
            $headId = $this->mappedTargetId('ec_departments', $row->head_of_department_id, 'exit_clearance_departments');

            if ($targetId === null || $headId === null) {
                continue;
            }

            DB::table('exit_clearance_departments')
                ->where('id', $targetId)
                ->update(['head_of_department_id' => $headId]);
        }
    }

    protected function syncExitClearanceApprovers(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ec_approvers');

        $this->syncRows('Exit clearance approvers', $query, function (object $row): void {
            $createdBy = $this->resolveUserId($this->nullableInt($row->created_by));

            $targetId = $this->resolveTargetId(
                'ec_approvers',
                $row->id,
                'exit_clearance_approvers',
                fn (): ?int => $this->nullableInt(
                    DB::table('exit_clearance_approvers')
                        ->where('email', $this->nullableString($row->email) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('exit_clearance_approvers')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? '',
                    'email'      => $this->nullableString($row->email) ?? '',
                    'phone'      => $this->nullableString($row->phone),
                    'title'      => $this->nullableString($row->title) ?? '',
                    'created_by' => $createdBy,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('ec_approvers', $row->id, 'exit_clearance_approvers', $targetId);
        });
    }

    protected function syncExitClearanceDepartmentApprovers(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ec_department_approver');

        $this->syncRows('Exit clearance department approvers', $query, function (object $row): void {
            $departmentId = $this->mappedTargetId('ec_departments', $row->department_id, 'exit_clearance_departments');
            $approverId = $this->mappedTargetId('ec_approvers', $row->approver_id, 'exit_clearance_approvers');

            if ($departmentId === null || $approverId === null) {
                $this->warnMissingRelation(
                    'ec_department_approver',
                    $row->department_id.'-'.$row->approver_id,
                    'department_or_approver',
                    $row->department_id.'-'.$row->approver_id
                );

                return;
            }

            DB::table('exit_clearance_department_approver')->updateOrInsert(
                [
                    'department_id' => $departmentId,
                    'approver_id'   => $approverId,
                ],
                []
            );
        }, 'department_id');
    }

    protected function syncExitClearanceRequests(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ec_requests');
        $requestService = app(ExitClearanceRequestService::class);

        $this->syncRows('Exit clearance requests', $query, function (object $row) use ($requestService): void {
            $departmentId = $this->nullableInt($row->department_id) !== null
                ? $this->mappedTargetId('ec_departments', $row->department_id, 'exit_clearance_departments')
                : null;
            $createdBy = $this->resolveUserId($this->nullableInt($row->created_by));

            $targetId = $this->resolveTargetId(
                'ec_requests',
                $row->id,
                'exit_clearance_requests',
                fn (): ?int => $this->findExitClearanceRequestId(
                    $this->nullableString($row->form_uid),
                    $this->nullableString($row->form_response_id)
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('exit_clearance_requests')->updateOrInsert(
                ['id' => $targetId],
                [
                    'department_id'                  => $departmentId,
                    'name'                           => $this->nullableString($row->name) ?? '',
                    'email'                          => $this->nullableString($row->email) ?? '',
                    'phone'                          => $this->nullableString($row->phone),
                    'position'                       => $this->nullableString($row->position),
                    'placement'                      => $this->nullableString($row->placement),
                    'join_date'                      => $row->join_date,
                    'request_date'                   => $row->request_date,
                    'departure_date'                 => $row->departure_date,
                    'reason'                         => $this->nullableString($row->reason),
                    'workload_feedback'              => $this->nullableString($row->workload_feedback),
                    'career_growth_feedback'         => $this->nullableString($row->career_growth_feedback),
                    'facility_welfare_feedback'      => $this->nullableString($row->facility_welfare_feedback),
                    'work_relationship_feedback'     => $this->nullableString($row->work_relationship_feedback),
                    'compensation_feedback'          => $this->nullableString($row->compensation_feedback),
                    'division_feedback'              => $this->nullableString($row->division_feedback),
                    'company_feedback'               => $this->nullableString($row->company_feedback),
                    'clearance_kartu_halo'           => $this->nullableString($row->clearance_kartu_halo),
                    'clearance_employee_debt'        => $this->nullableString($row->clearance_employee_debt),
                    'clearance_uniform_return'       => $this->nullableString($row->clearance_uniform_return),
                    'clearance_vehicle_return'       => $this->nullableString($row->clearance_vehicle_return),
                    'clearance_inventory_return'     => $this->nullableString($row->clearance_inventory_return),
                    'clearance_account_deactivation' => $this->nullableString($row->clearance_account_deactivation),
                    'clearance_receivable_data'      => $this->nullableString($row->clearance_receivable_data),
                    'clearance_promotor_internal'    => $this->nullableString($row->clearance_promotor_internal),
                    'clearance_nota_pending'         => $this->nullableString($row->clearance_nota_pending),
                    'clearance_stock_opname'         => $this->nullableString($row->clearance_stock_opname),
                    'resignation_letter_url'         => $this->nullableString($row->resignation_letter_url),
                    'form_uid'                       => $this->nullableString($row->form_uid),
                    'form_status'                    => $requestService->formatFormStatus($this->nullableString($row->form_status)),
                    'form_response_id'               => $this->nullableString($row->form_response_id),
                    'created_by'                     => $createdBy,
                    'created_at'                     => $row->created_at,
                    'updated_at'                     => $row->updated_at,
                    'deleted_at'                     => $row->deleted_at,
                ],
            );

            $this->rememberMapping('ec_requests', $row->id, 'exit_clearance_requests', $targetId);
            $this->syncedExitRequestIds[$targetId] = $targetId;
        });
    }

    protected function syncExitClearanceRequestApprovers(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ec_request_approver');
        $requestService = app(ExitClearanceRequestService::class);

        $this->syncRows('Exit clearance request approvers', $query, function (object $row) use ($requestService): void {
            $requestId = $this->mappedTargetId('ec_requests', $row->request_id, 'exit_clearance_requests');
            $approverId = $this->mappedTargetId('ec_approvers', $row->approver_id, 'exit_clearance_approvers');

            if ($requestId === null || $approverId === null) {
                $this->warnMissingRelation(
                    'ec_request_approver',
                    $row->request_id.'-'.$row->approver_id,
                    'request_or_approver',
                    $row->request_id.'-'.$row->approver_id
                );

                return;
            }

            DB::table('exit_clearance_request_approver')->updateOrInsert(
                [
                    'request_id'  => $requestId,
                    'approver_id' => $approverId,
                ],
                [
                    'approved_at' => $row->approved_at,
                    'notes'       => $this->nullableString($row->notes),
                    'status'      => $requestService->normalizeApprovalStatus($this->nullableString($row->status)),
                    'created_at'  => $row->created_at,
                    'updated_at'  => $row->updated_at,
                ]
            );
        }, 'request_id');
    }

    protected function syncPresensiOffices(): void
    {
        $query = DB::connection($this->legacyConnection)->table('offices');

        $this->syncRows('Presensi offices', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'offices',
                $row->id,
                'presensi_offices',
                fn (): ?int => $this->nullableInt(
                    DB::table('presensi_offices')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->where('latitude', $row->latitude)
                        ->where('longitude', $row->longitude)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_offices')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? '',
                    'latitude'   => $row->latitude,
                    'longitude'  => $row->longitude,
                    'radius'     => (int) ($row->radius ?? 0),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('offices', $row->id, 'presensi_offices', $targetId);
        });
    }

    protected function syncPresensiShifts(): void
    {
        $query = DB::connection($this->legacyConnection)->table('shifts');

        $this->syncRows('Presensi shifts', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'shifts',
                $row->id,
                'presensi_shifts',
                fn (): ?int => $this->nullableInt(
                    DB::table('presensi_shifts')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->where('start_time', $row->start_time)
                        ->where('end_time', $row->end_time)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_shifts')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? '',
                    'start_time' => $row->start_time,
                    'end_time'   => $row->end_time,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('shifts', $row->id, 'presensi_shifts', $targetId);
        });
    }

    protected function syncPresensiUserImages(): void
    {
        $query = DB::connection($this->legacyConnection)
            ->table('users')
            ->select('id', 'image')
            ->whereNotNull('image');

        $this->syncRows('Presensi user images', $query, function (object $row): void {
            $targetUserId = $this->resolveUserId($this->nullableInt($row->id));

            if ($targetUserId === null) {
                return;
            }

            $user = SecurityUser::query()->find($targetUserId);

            if (! $user) {
                return;
            }

            if (! $user->partner_id) {
                $user->save();
                $user->refresh();
            }

            $user->partner?->forceFill([
                'avatar' => $this->nullableString($row->image),
            ])->save();
        });
    }

    protected function syncPresensiSchedules(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('schedules')) {
            $this->line('Legacy schedules table not found. Skipping presensi schedules.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('schedules');

        $this->syncRows('Presensi schedules', $query, function (object $row): void {
            $targetUserId = $this->resolveUserId($this->nullableInt($row->user_id));
            $shiftId = $this->nullableInt($row->shift_id) !== null
                ? $this->mappedTargetId('shifts', $row->shift_id, 'presensi_shifts')
                : null;
            $officeId = $this->nullableInt($row->office_id) !== null
                ? $this->mappedTargetId('offices', $row->office_id, 'presensi_offices')
                : null;

            if ($targetUserId === null || $shiftId === null || $officeId === null) {
                $this->warnMissingRelation(
                    'schedules',
                    $row->id,
                    'user_or_shift_or_office',
                    implode(':', [(string) $row->user_id, (string) $row->shift_id, (string) $row->office_id])
                );

                return;
            }

            $targetId = $this->resolveTargetId(
                'schedules',
                $row->id,
                'presensi_schedules',
                fn (): ?int => $this->nullableInt(
                    DB::table('presensi_schedules')
                        ->where('user_id', $targetUserId)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_schedules')->updateOrInsert(
                ['id' => $targetId],
                [
                    'user_id'    => $targetUserId,
                    'shift_id'   => $shiftId,
                    'office_id'  => $officeId,
                    'is_wfa'     => $this->normalizeBoolean($row->is_wfa, false),
                    'is_banned'  => $this->normalizeBoolean($row->is_banned, false),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('schedules', $row->id, 'presensi_schedules', $targetId);
        });
    }

    protected function syncPresensiAttendances(): void
    {
        $query = DB::connection($this->legacyConnection)->table('attendances');

        $this->syncRows('Presensi attendances', $query, function (object $row): void {
            $targetUserId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($targetUserId === null) {
                return;
            }

            $targetId = $this->resolveTargetId(
                'attendances',
                $row->id,
                'presensi_attendances'
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_attendances')->updateOrInsert(
                ['id' => $targetId],
                [
                    'user_id'             => $targetUserId,
                    'schedule_latitude'   => $row->schedule_latitude,
                    'schedule_longitude'  => $row->schedule_longitude,
                    'schedule_start_time' => $row->schedule_start_time,
                    'schedule_end_time'   => $row->schedule_end_time,
                    'start_latitude'      => $row->start_latitude,
                    'start_longitude'     => $row->start_longitude,
                    'end_latitude'        => $row->end_latitude,
                    'end_longitude'       => $row->end_longitude,
                    'start_time'          => $row->start_time,
                    'start_photo_path'    => $this->nullableString($row->start_photo_path ?? null),
                    'end_time'            => $row->end_time,
                    'end_photo_path'      => $this->nullableString($row->end_photo_path ?? null),
                    'is_leave'            => $this->normalizeBoolean($row->is_leave, false),
                    'created_at'          => $row->created_at,
                    'updated_at'          => $row->updated_at,
                    'deleted_at'          => $row->deleted_at,
                ],
            );

            $this->rememberMapping('attendances', $row->id, 'presensi_attendances', $targetId);
        });
    }

    protected function syncPresensiLeaves(): void
    {
        $query = DB::connection($this->legacyConnection)->table('leaves');

        $this->syncRows('Presensi leaves', $query, function (object $row): void {
            $targetUserId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($targetUserId === null) {
                return;
            }

            $targetId = $this->resolveTargetId(
                'leaves',
                $row->id,
                'presensi_leaves'
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_leaves')->updateOrInsert(
                ['id' => $targetId],
                [
                    'user_id'    => $targetUserId,
                    'type'       => $this->nullableString($row->type) ?? 'Izin',
                    'start_date' => $row->start_date,
                    'end_date'   => $row->end_date,
                    'reason'     => $this->nullableString($row->reason),
                    'status'     => $this->normalizeSimpleStatus($row->status),
                    'note'       => $this->nullableString($row->note),
                    'attachment' => $this->nullableString($row->attachment ?? null),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('leaves', $row->id, 'presensi_leaves', $targetId);
        });
    }

    protected function syncPresensiOvertimes(): void
    {
        $query = DB::connection($this->legacyConnection)->table('overtimes');

        $this->syncRows('Presensi overtimes', $query, function (object $row): void {
            $targetUserId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($targetUserId === null) {
                return;
            }

            $targetId = $this->resolveTargetId(
                'overtimes',
                $row->id,
                'presensi_overtimes'
            );

            if ($targetId === null) {
                return;
            }

            DB::table('presensi_overtimes')->updateOrInsert(
                ['id' => $targetId],
                [
                    'user_id'    => $targetUserId,
                    'date'       => $row->date,
                    'start_time' => $row->start_time,
                    'end_time'   => $row->end_time,
                    'reason'     => $this->nullableString($row->reason),
                    'status'     => $this->normalizeSimpleStatus($row->status),
                    'note'       => $this->nullableString($row->note),
                    'attachment' => $this->nullableString($row->attachment ?? null),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                ],
            );

            $this->rememberMapping('overtimes', $row->id, 'presensi_overtimes', $targetId);
        });
    }

    protected function refreshExitClearanceStatuses(): void
    {
        if ($this->syncedExitRequestIds === []) {
            return;
        }

        $requestService = app(ExitClearanceRequestService::class);

        ExitClearanceRequest::query()
            ->whereIn('id', array_values($this->syncedExitRequestIds))
            ->get()
            ->each(function (ExitClearanceRequest $request) use ($requestService): void {
                $requestService->syncOverallStatus($request);
            });
    }

    protected function syncRows(
        string $label,
        Builder $query,
        callable $callback,
        string $orderColumn = 'id'
    ): void {
        $this->line('Syncing '.$label.'...');

        $total = $query->count();

        if ($total === 0) {
            $this->line('No legacy rows found for '.$label.'.');

            return;
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $query
            ->orderBy($orderColumn)
            ->chunk($this->chunkSize(), function ($rows) use ($callback, $progressBar): void {
                foreach ($rows as $row) {
                    $callback($row);
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function resolveTargetId(
        string $legacyTable,
        int|string $legacyId,
        string $targetTable,
        ?callable $finder = null
    ): ?int {
        $mappedId = $this->mappedTargetId($legacyTable, $legacyId, $targetTable);

        if ($mappedId !== null && $this->targetRecordExists($targetTable, $mappedId)) {
            return $mappedId;
        }

        if ($finder !== null) {
            $foundId = $this->nullableInt($finder());

            if ($foundId !== null) {
                $this->rememberMapping($legacyTable, $legacyId, $targetTable, $foundId);

                return $foundId;
            }
        }

        $legacyIntegerId = $this->nullableInt($legacyId);

        if ($legacyIntegerId !== null && ! $this->targetRecordExists($targetTable, $legacyIntegerId)) {
            $this->rememberMapping($legacyTable, $legacyId, $targetTable, $legacyIntegerId);

            return $legacyIntegerId;
        }

        $nextAvailableId = ((int) (DB::table($targetTable)->max('id') ?? 0)) + 1;
        $this->rememberMapping($legacyTable, $legacyId, $targetTable, $nextAvailableId);

        return $nextAvailableId;
    }

    protected function mappedTargetId(string $legacyTable, int|string $legacyId, string $targetTable): ?int
    {
        return $this->nullableInt(
            DB::table('legacy_sync_mappings')
                ->where('connection_name', $this->legacyConnection)
                ->where('legacy_table', $legacyTable)
                ->where('legacy_id', (string) $legacyId)
                ->where('target_table', $targetTable)
                ->value('target_id')
        );
    }

    protected function rememberMapping(string $legacyTable, int|string $legacyId, string $targetTable, int $targetId): void
    {
        $timestamp = now();

        DB::table('legacy_sync_mappings')->upsert(
            [[
                'connection_name' => $this->legacyConnection,
                'legacy_table'    => $legacyTable,
                'legacy_id'       => (string) $legacyId,
                'target_table'    => $targetTable,
                'target_id'       => $targetId,
                'synced_at'       => $timestamp,
                'created_at'      => $timestamp,
                'updated_at'      => $timestamp,
            ]],
            ['connection_name', 'legacy_table', 'legacy_id', 'target_table'],
            ['target_id', 'synced_at', 'updated_at']
        );
    }

    protected function resolveUserId(?int $legacyUserId): ?int
    {
        if ($legacyUserId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('users', $legacyUserId, 'users');

        if ($mappedId !== null && $this->targetRecordExists('users', $mappedId)) {
            return $mappedId;
        }

        $this->loadLegacyUsers();

        if (isset($this->legacyUsersById[$legacyUserId])) {
            $legacyEmail = strtolower((string) ($this->legacyUsersById[$legacyUserId]['email'] ?? ''));
            $targetId = $legacyEmail !== ''
                ? ($this->targetUsersByEmail[$legacyEmail] ?? null)
                : null;

            if ($targetId !== null) {
                $this->rememberMapping('users', $legacyUserId, 'users', $targetId);

                return $targetId;
            }
        }

        if ($this->shouldCreateMissingUsers()) {
            $createdUserId = $this->createMissingUserFromLegacy($legacyUserId);

            if ($createdUserId !== null) {
                return $createdUserId;
            }
        }

        if ((bool) $this->option('trust-legacy-user-ids') && $this->targetRecordExists('users', $legacyUserId)) {
            $this->rememberMapping('users', $legacyUserId, 'users', $legacyUserId);

            return $legacyUserId;
        }

        $this->warnOnce(
            'user:'.$legacyUserId,
            sprintf(
                'Could not map legacy user ID [%d]. Import legacy users table or use --trust-legacy-user-ids if IDs are aligned.',
                $legacyUserId
            )
        );

        return null;
    }

    protected function resolveCompanyId(?int $legacyCompanyId): ?int
    {
        if ($legacyCompanyId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('companies', $legacyCompanyId, 'companies');

        if ($mappedId !== null && $this->targetRecordExists('companies', $mappedId)) {
            return $mappedId;
        }

        $this->loadLegacyCompanies();

        $legacyCompany = $this->legacyCompaniesById[$legacyCompanyId] ?? null;

        if ($legacyCompany !== null) {
            $companyCode = strtolower((string) ($legacyCompany['company_id'] ?? ''));
            $targetId = $companyCode !== ''
                ? ($this->targetCompaniesByCompanyCode[$companyCode] ?? null)
                : null;

            if ($targetId !== null) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $targetId);

                return $targetId;
            }
        }

        if ((bool) $this->option('trust-legacy-company-ids') && $this->targetRecordExists('companies', $legacyCompanyId)) {
            $this->rememberMapping('companies', $legacyCompanyId, 'companies', $legacyCompanyId);

            return $legacyCompanyId;
        }

        $this->warnOnce(
            'company:'.$legacyCompanyId,
            sprintf(
                'Could not map legacy company ID [%d]. Import legacy companies table or use --trust-legacy-company-ids if IDs are aligned.',
                $legacyCompanyId
            )
        );

        return null;
    }

    protected function loadLegacyUsers(): void
    {
        if ($this->legacyUsersLoaded) {
            return;
        }

        $this->legacyUsersLoaded = true;
        $this->targetUsersByEmail = DB::table('users')
            ->whereNotNull('email')
            ->select('id', 'email')
            ->get()
            ->mapWithKeys(fn (object $row): array => [strtolower((string) $row->email) => (int) $row->id])
            ->all();

        if (! Schema::connection($this->legacyConnection)->hasTable('users')) {
            return;
        }

        $legacyUsersQuery = DB::connection($this->legacyConnection)
            ->table('users');

        $availableColumns = ['id'];

        foreach (['name', 'email', 'password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at'] as $column) {
            if (Schema::connection($this->legacyConnection)->hasColumn('users', $column)) {
                $availableColumns[] = $column;
            }
        }

        $this->legacyUsersById = DB::connection($this->legacyConnection)
            ->table('users')
            ->select($availableColumns)
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => [
                'name'              => $this->nullableString($row->name ?? null),
                'email'             => $this->nullableString($row->email ?? null),
                'password'          => $this->nullableString($row->password ?? null),
                'remember_token'    => $this->nullableString($row->remember_token ?? null),
                'email_verified_at' => $row->email_verified_at ?? null,
                'created_at'        => $row->created_at ?? null,
                'updated_at'        => $row->updated_at ?? null,
            ]])
            ->all();
    }

    protected function createMissingUserFromLegacy(int $legacyUserId): ?int
    {
        $legacyUser = $this->legacyUsersById[$legacyUserId] ?? null;

        if ($legacyUser === null) {
            return null;
        }

        $email = $this->resolveLegacyUserEmail($legacyUserId, $legacyUser['email']);
        $existingUserId = $this->targetUsersByEmail[strtolower($email)] ?? null;

        if ($existingUserId !== null) {
            $this->rememberMapping('users', $legacyUserId, 'users', $existingUserId);

            return $existingUserId;
        }

        $user = new SecurityUser;
        $user->forceFill([
            'name'              => $legacyUser['name'] ?? 'Legacy User '.$legacyUserId,
            'email'             => $email,
            'password'          => $legacyUser['password'] ?: Hash::make(Str::random(32)),
            'remember_token'    => $legacyUser['remember_token'],
            'email_verified_at' => $legacyUser['email_verified_at'],
            'language'          => config('app.locale'),
            'is_active'         => true,
            'created_at'        => $legacyUser['created_at'] ?? now(),
            'updated_at'        => $legacyUser['updated_at'] ?? now(),
        ]);
        $user->save();

        $this->targetUsersByEmail[strtolower($email)] = (int) $user->id;
        $this->rememberMapping('users', $legacyUserId, 'users', (int) $user->id);

        $this->line(sprintf(
            'Created missing user [%s] from legacy user ID [%d].',
            $email,
            $legacyUserId
        ));

        return (int) $user->id;
    }

    protected function shouldCreateMissingUsers(): bool
    {
        return ! (bool) $this->option('skip-missing-users');
    }

    protected function resolveLegacyUserEmail(int $legacyUserId, ?string $email): string
    {
        $candidate = $email;

        if ($candidate === null || $candidate === '') {
            $candidate = sprintf('legacy-user-%d@legacy-sync.local', $legacyUserId);
        }

        $normalizedCandidate = strtolower($candidate);
        $existingUserId = $this->targetUsersByEmail[$normalizedCandidate] ?? null;

        if ($existingUserId === null) {
            return $candidate;
        }

        return sprintf('legacy-user-%d@legacy-sync.local', $legacyUserId);
    }

    protected function loadLegacyCompanies(): void
    {
        if ($this->legacyCompaniesLoaded) {
            return;
        }

        $this->legacyCompaniesLoaded = true;
        $this->targetCompaniesByCompanyCode = DB::table('companies')
            ->whereNotNull('company_id')
            ->select('id', 'company_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [strtolower((string) $row->company_id) => (int) $row->id])
            ->all();

        if (! Schema::connection($this->legacyConnection)->hasTable('companies')) {
            return;
        }

        $this->legacyCompaniesById = DB::connection($this->legacyConnection)
            ->table('companies')
            ->select('id', 'company_id', 'name')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => [
                'company_id' => $this->nullableString($row->company_id),
                'name'       => $this->nullableString($row->name),
            ]])
            ->all();
    }

    protected function targetRecordExists(string $table, int $id): bool
    {
        return DB::table($table)->where('id', $id)->exists();
    }

    protected function findFormTransferId(?int $companyId, ?string $uidPrefix, ?string $code): ?int
    {
        $query = DB::table('form_transfers');

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        if ($uidPrefix !== null) {
            $candidate = (clone $query)->where('uid_prefix', $uidPrefix)->value('id');

            if ($candidate !== null) {
                return (int) $candidate;
            }
        }

        if ($code !== null) {
            $candidate = (clone $query)->where('code', $code)->value('id');

            if ($candidate !== null) {
                return (int) $candidate;
            }
        }

        return null;
    }

    protected function findTransferDivisionId(?int $formTransferId, ?string $name, ?string $code): ?int
    {
        if ($formTransferId === null) {
            return null;
        }

        $query = DB::table('form_transfer_divisions')
            ->where('form_transfer_id', $formTransferId)
            ->where('name', $name ?? '');

        if ($code === null) {
            $query->whereNull('code');
        } else {
            $query->where('code', $code);
        }

        return $this->nullableInt($query->value('id'));
    }

    protected function findTransferApprovalWorkflowId(
        int $formTransferId,
        ?int $divisionId,
        ?string $name,
        ?string $code,
        ?string $steps
    ): ?int {
        $candidates = DB::table('form_transfer_approval_workflows')
            ->select('id', 'name', 'code', 'steps')
            ->where('form_transfer_id', $formTransferId)
            ->when(
                $divisionId === null,
                fn (Builder $query): Builder => $query->whereNull('division_id'),
                fn (Builder $query): Builder => $query->where('division_id', $divisionId)
            )
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->nullableString($candidate->name) !== $name) {
                continue;
            }

            if ($this->nullableString($candidate->code) !== $code) {
                continue;
            }

            if ($this->normalizeJsonString($candidate->steps) !== $steps) {
                continue;
            }

            return (int) $candidate->id;
        }

        return null;
    }

    protected function findTransferRequestId(?string $uid, ?string $statusResponseId): ?int
    {
        if ($uid !== null) {
            $id = DB::table('form_transfer_requests')->where('uid', $uid)->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        if ($statusResponseId !== null) {
            $id = DB::table('form_transfer_requests')
                ->where('status_response_id', $statusResponseId)
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    protected function findExitClearanceRequestId(?string $formUid, ?string $formResponseId): ?int
    {
        if ($formUid !== null) {
            $id = DB::table('exit_clearance_requests')->where('form_uid', $formUid)->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        if ($formResponseId !== null) {
            $id = DB::table('exit_clearance_requests')
                ->where('form_response_id', $formResponseId)
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    protected function normalizeTransferSubmissionStatus(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return in_array($value, array_map(
            fn (TransferRequestSubmissionStatus $case): string => $case->value,
            TransferRequestSubmissionStatus::cases()
        ), true) ? $value : TransferRequestSubmissionStatus::BARU->value;
    }

    protected function normalizeTransferApprovalStatus(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            TransferRequestApprovalStatus::APPROVED->value => TransferRequestApprovalStatus::APPROVED->value,
            TransferRequestApprovalStatus::REJECTED->value,
            ApprovalStatus::DITOLAK->value => TransferRequestApprovalStatus::REJECTED->value,
            default                        => TransferRequestApprovalStatus::PENDING->value,
        };
    }

    protected function normalizeTransferRealizationStatus(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            TransferRequestRealizationStatus::DONE->value => TransferRequestRealizationStatus::DONE->value,
            'canceled',
            TransferRequestRealizationStatus::CANCELLED->value => TransferRequestRealizationStatus::CANCELLED->value,
            default                                            => TransferRequestRealizationStatus::PENDING->value,
        };
    }

    protected function normalizeTransferApprovalsPayload(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $decoded = is_string($payload) ? json_decode($payload, true) : $payload;

        if (! is_array($decoded)) {
            return $this->nullableString($payload);
        }

        $allowedStatuses = array_map(
            fn (ApprovalStatus $case): string => $case->value,
            ApprovalStatus::cases()
        );

        $normalized = array_map(function (mixed $approval) use ($allowedStatuses): mixed {
            if (! is_array($approval)) {
                return $approval;
            }

            $status = strtolower(trim((string) ($approval['status'] ?? ApprovalStatus::PENDING->value)));

            if (! in_array($status, $allowedStatuses, true)) {
                $status = match ($status) {
                    'rejected' => ApprovalStatus::DITOLAK->value,
                    'approved' => ApprovalStatus::APPROVED->value,
                    'waiting'  => ApprovalStatus::WAITING->value,
                    'revisi'   => ApprovalStatus::REVISI->value,
                    default    => ApprovalStatus::PENDING->value,
                };
            }

            $approval['status'] = $status;

            return $approval;
        }, $decoded);

        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    protected function normalizeSimpleStatus(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return in_array($value, ['pending', 'approved', 'rejected'], true)
            ? $value
            : 'pending';
    }

    protected function normalizeJsonString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            return $trimmed;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'TRUE', 'yes', 'YES'], true);
    }

    protected function warnMissingRelation(string $table, int|string $legacyId, string $column, mixed $value): void
    {
        $this->warnOnce(
            sprintf('relation:%s:%s:%s', $table, $legacyId, $column),
            sprintf(
                'Skipping legacy record [%s:%s] because relation [%s=%s] could not be resolved.',
                $table,
                $legacyId,
                $column,
                (string) $value
            )
        );
    }

    protected function warnOnce(string $key, string $message): void
    {
        if (isset($this->emittedWarnings[$key])) {
            return;
        }

        $this->emittedWarnings[$key] = true;
        $this->warn($message);
    }
}
