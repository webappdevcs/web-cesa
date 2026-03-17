<?php

namespace Cesa\LegacySync\Console\Commands;

use Cesa\ExitClearance\Models\Request as ExitClearanceRequest;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\Shelf\Support\InteractsWithShelfCreatorBackfill;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Security\Models\User as SecurityUser;
use Webkul\Support\Models\Company;

class SyncLegacySqlData extends Command
{
    use InteractsWithShelfCreatorBackfill;

    protected $signature = 'legacy:sync
                            {--module=* : Modules to sync (form-transfer, exit-clearance, presensi, helpdesk, shelf)}
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
    protected array $availableModules = ['form-transfer', 'exit-clearance', 'presensi', 'helpdesk', 'shelf'];

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
     * @var array<int, string>
     */
    protected array $targetUserEmailsById = [];

    /**
     * @var array<int, string>
     */
    protected array $targetUserNamesById = [];

    /**
     * @var array<string, array<int, int>>
     */
    protected array $targetUserIdsByName = [];

    /**
     * @var array<int, int|null>
     */
    protected array $targetUserDefaultCompaniesById = [];

    protected bool $targetEmployeesLoaded = false;

    /**
     * @var array<string, int>
     */
    protected array $targetEmployeeUserIdsByIdentifier = [];

    /**
     * @var array<string, int>
     */
    protected array $targetEmployeesWithoutUsersByIdentifier = [];

    /**
     * @var array<int, array<int, string>>
     */
    protected array $targetEmployeeIdentifiersById = [];

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
     * @var array<string, int>
     */
    protected array $targetCompaniesByName = [];

    /**
     * @var array<int, string>
     */
    protected array $legacyHelpdeskBusinessEntitiesById = [];

    protected bool $legacyHelpdeskBusinessEntitiesLoaded = false;

    /**
     * @var array<int, object|null>
     */
    protected array $legacyShelfAssetsById = [];

    protected bool $legacyShelfAssetsLoaded = false;

    /**
     * @var array<int, object|null>
     */
    protected array $legacyShelfAssetTransfersById = [];

    protected bool $legacyShelfAssetTransfersLoaded = false;

    protected ?string $legacyShelfJobPositionsTable = null;

    protected ?string $legacyShelfEmployeesTable = null;

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
                    'helpdesk'       => $this->syncHelpdeskModule(),
                    'shelf'          => $this->syncShelfModule(),
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

    protected function syncHelpdeskModule(): void
    {
        $this->components->twoColumnDetail('Module', 'helpdesk');

        $requiredTables = [
            'users',
            'priorities',
            'ticket_statuses',
            'units',
            'problem_categories',
            'tickets',
        ];

        if (! $this->ensureLegacyTablesExist($requiredTables, 'helpdesk')) {
            return;
        }

        if ($this->shouldTruncate()) {
            $this->truncateTables([
                'helpdesk_ticket_histories',
                'helpdesk_comments',
                'helpdesk_tickets',
                'helpdesk_problem_categories',
                'helpdesk_unit_user',
                'helpdesk_units',
                'helpdesk_ticket_statuses',
                'helpdesk_priorities',
            ]);
        }

        $this->syncHelpdeskPriorities();
        $this->syncHelpdeskTicketStatuses();
        $this->syncHelpdeskUnits();
        $this->syncHelpdeskUnitUsers();
        $this->syncHelpdeskProblemCategories();
        $this->syncHelpdeskTickets();
        $this->syncHelpdeskComments();
        $this->syncHelpdeskTicketHistories();
    }

    protected function syncShelfModule(): void
    {
        $this->components->twoColumnDetail('Module', 'shelf');

        $legacyTables = [
            'categories',
            'brands',
            'asset_locations',
            'vendors',
            'assets',
            'asset_transfers',
            'asset_transfer_details',
            'tasks',
            'vehicle_checksheets',
            'custom_asset_attributes',
            'asset_attributes',
            'approval_levels',
            'asset_requests',
            'public_asset_requests',
            'request_approvals',
        ];

        $existingLegacyTables = array_values(array_filter(
            $legacyTables,
            fn (string $table): bool => Schema::connection($this->legacyConnection)->hasTable($table)
        ));

        if ($existingLegacyTables === []) {
            $this->warn('Skipping module [shelf]. No legacy shelf tables were found.');

            return;
        }

        if ($this->shouldTruncate()) {
            $this->truncateTables([
                'shelf_company_document_settings',
                'shelf_request_approvals',
                'shelf_asset_transfer_details',
                'shelf_asset_attributes',
                'shelf_vehicle_checksheets',
                'shelf_asset_transfers',
                'shelf_tasks',
                'shelf_asset_requests',
                'shelf_approval_levels',
                'shelf_assets',
                'shelf_custom_asset_attributes',
                'shelf_vendors',
                'shelf_asset_locations',
                'shelf_brands',
                'shelf_categories',
            ]);
        }

        $this->syncShelfCategories();
        $this->syncShelfBrands();
        $this->syncShelfAssetLocations();
        $this->syncShelfVendors();
        $this->syncShelfCustomAssetAttributes();
        $this->syncShelfAssets();
        $this->syncShelfAssetAttributes();
        $this->syncShelfTasks();
        $this->syncShelfAssetTransfers();
        $this->syncShelfAssetTransferDetails();
        $this->syncShelfEmployeeJobPositions();
        $this->syncShelfEmployees();
        $this->syncShelfCompanyDocumentSettings();
        $this->syncShelfVehicleChecksheets();
        $this->syncShelfApprovalLevels();
        $this->syncShelfAssetRequests();
        $this->syncShelfRequestApprovals();
        $this->backfillShelfCreatorIds();
    }

    protected function syncShelfCategories(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('categories')) {
            $this->line('Legacy categories table not found. Skipping shelf categories.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('categories');

        $this->syncRows('Shelf categories', $query, function (object $row): void {
            $targetId = $this->resolveTargetId('categories', $row->id, 'shelf_categories');

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_categories')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? 'Category',
                    'parent_id'  => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('categories', $row->id, 'shelf_categories', $targetId);
        });

        DB::connection($this->legacyConnection)
            ->table('categories')
            ->whereNotNull('parent_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                $targetId = $this->mappedTargetId('categories', $row->id, 'shelf_categories');
                $parentId = $this->mappedTargetId('categories', $row->parent_id, 'shelf_categories');

                if ($targetId === null || $parentId === null) {
                    $this->warnMissingRelation('categories', $row->id, 'parent_id', $row->parent_id);

                    return;
                }

                DB::table('shelf_categories')
                    ->where('id', $targetId)
                    ->update(['parent_id' => $parentId]);
            });
    }

    protected function syncShelfBrands(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('brands')) {
            $this->line('Legacy brands table not found. Skipping shelf brands.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('brands');

        $this->syncRows('Shelf brands', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'brands',
                $row->id,
                'shelf_brands',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_brands')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_brands')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? 'Brand',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('brands', $row->id, 'shelf_brands', $targetId);
        });
    }

    protected function syncShelfAssetLocations(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('asset_locations')) {
            $this->line('Legacy asset_locations table not found. Skipping shelf asset locations.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('asset_locations');

        $this->syncRows('Shelf asset locations', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'asset_locations',
                $row->id,
                'shelf_asset_locations',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_asset_locations')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->where('address', $this->nullableString($row->address))
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_asset_locations')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'        => $this->nullableString($row->name) ?? 'Location',
                    'address'     => $this->nullableString($row->address),
                    'description' => $this->nullableString($row->description),
                    'created_at'  => $row->created_at ?? now(),
                    'updated_at'  => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('asset_locations', $row->id, 'shelf_asset_locations', $targetId);
        });
    }

    protected function syncShelfVendors(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('vendors')) {
            $this->line('Legacy vendors table not found. Skipping shelf vendors.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('vendors');

        $this->syncRows('Shelf vendors', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'vendors',
                $row->id,
                'shelf_vendors',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_vendors')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_vendors')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? 'Vendor',
                    'last_price' => $row->last_price ?? 0,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('vendors', $row->id, 'shelf_vendors', $targetId);
        });
    }

    protected function syncShelfCustomAssetAttributes(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('custom_asset_attributes')) {
            $this->line('Legacy custom_asset_attributes table not found. Skipping shelf custom asset attributes.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('custom_asset_attributes');

        $this->syncRows('Shelf custom asset attributes', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'custom_asset_attributes',
                $row->id,
                'shelf_custom_asset_attributes',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_custom_asset_attributes')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->where('type', $this->nullableString($row->type) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_custom_asset_attributes')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'                    => $this->nullableString($row->name) ?? 'Attribute',
                    'type'                    => $this->nullableString($row->type) ?? 'text',
                    'required'                => $this->normalizeBoolean($row->required ?? null, false),
                    'is_active'               => $this->normalizeBoolean($row->is_active ?? null, true),
                    'category_id'             => $this->normalizeJsonString($row->category_id ?? null),
                    'is_notifiable'           => $this->normalizeBoolean($row->is_notifiable ?? null, false),
                    'notification_type'       => $this->normalizeShelfNotificationType($row->notification_type ?? null),
                    'notification_offset'     => $this->nullableInt($row->notification_offset ?? null),
                    'fixed_notification_date' => $row->fixed_notification_date ?? null,
                    'created_at'              => $row->created_at ?? now(),
                    'updated_at'              => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('custom_asset_attributes', $row->id, 'shelf_custom_asset_attributes', $targetId);
        });
    }

    protected function syncShelfAssets(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('assets')) {
            $this->line('Legacy assets table not found. Skipping shelf assets.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('assets');

        $this->syncRows('Shelf assets', $query, function (object $row): void {
            $this->syncShelfAssetRow($row);
        });
    }

    protected function syncShelfAssetAttributes(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('asset_attributes')) {
            $this->line('Legacy asset_attributes table not found. Skipping shelf asset attributes.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('asset_attributes');

        $this->syncRows('Shelf asset attributes', $query, function (object $row): void {
            $assetId = $this->resolveShelfAssetId($this->nullableInt($row->asset_id ?? null));
            $customAttributeId = $this->nullableInt($row->custom_attribute_id ?? null) !== null
                ? $this->mappedTargetId('custom_asset_attributes', $row->custom_attribute_id, 'shelf_custom_asset_attributes')
                : null;

            if ($assetId === null) {
                $this->warnMissingRelation('asset_attributes', $row->id, 'asset_id', $row->asset_id);

                return;
            }

            if ($this->nullableInt($row->custom_attribute_id ?? null) !== null && $customAttributeId === null) {
                $this->warnMissingRelation('asset_attributes', $row->id, 'custom_attribute_id', $row->custom_attribute_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'asset_attributes',
                $row->id,
                'shelf_asset_attributes',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_asset_attributes')
                        ->where('asset_id', $assetId)
                        ->where('custom_attribute_id', $customAttributeId)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_asset_attributes')->updateOrInsert(
                ['id' => $targetId],
                [
                    'asset_id'            => $assetId,
                    'custom_attribute_id' => $customAttributeId,
                    'attribute_value'     => $this->nullableString($row->attribute_value ?? null),
                    'created_at'          => $row->created_at ?? now(),
                    'updated_at'          => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('asset_attributes', $row->id, 'shelf_asset_attributes', $targetId);
        });
    }

    protected function syncShelfTasks(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('tasks')) {
            $this->line('Legacy tasks table not found. Skipping shelf tasks.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('tasks');

        $this->syncRows('Shelf tasks', $query, function (object $row): void {
            $companyId = $this->resolveCompanyId($this->nullableInt($row->business_entity_id ?? null));
            $vendorId = $this->mappedTargetId('vendors', $row->vendor_id, 'shelf_vendors');
            $userId = $this->resolveUserId($this->nullableInt($row->user_id ?? null), $companyId);

            if ($companyId === null) {
                $this->warnMissingRelation('tasks', $row->id, 'company_id', $row->business_entity_id ?? null);

                return;
            }

            if ($vendorId === null) {
                $this->warnMissingRelation('tasks', $row->id, 'vendor_id', $row->vendor_id);

                return;
            }

            $code = $this->nullableString($row->code ?? null) ?? sprintf('LEGACY-TASK-%d', $row->id);

            $targetId = $this->resolveTargetId(
                'tasks',
                $row->id,
                'shelf_tasks',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_tasks')
                        ->where('code', $code)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_tasks')->updateOrInsert(
                ['id' => $targetId],
                [
                    'code'            => $code,
                    'company_id'      => $companyId,
                    'name'            => $this->nullableString($row->name) ?? 'Task',
                    'description'     => $this->nullableString($row->description) ?? '',
                    'vendor_id'       => $vendorId,
                    'cost'            => $row->cost ?? 0,
                    'location'        => $this->nullableString($row->location) ?? '',
                    'status'          => $this->normalizeShelfTaskStatus($row->status ?? null),
                    'attachment'      => $this->normalizeAttachmentArrayPayload($row->attachment ?? null),
                    'work_timestamp'  => $row->work_timestamp ?? null,
                    'user_id'         => $userId,
                    'document_upload' => $this->nullableString($row->document_upload ?? null),
                    'created_at'      => $row->created_at ?? now(),
                    'updated_at'      => $row->updated_at ?? now(),
                    'deleted_at'      => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('tasks', $row->id, 'shelf_tasks', $targetId);
        });
    }

    protected function syncShelfAssetTransfers(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('asset_transfers')) {
            $this->line('Legacy asset_transfers table not found. Skipping shelf asset transfers.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('asset_transfers');

        $this->syncRows('Shelf asset transfers', $query, function (object $row): void {
            $this->syncShelfAssetTransferRow($row);
        });
    }

    protected function syncShelfAssetTransferDetails(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('asset_transfer_details')) {
            $this->line('Legacy asset_transfer_details table not found. Skipping shelf asset transfer details.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('asset_transfer_details');

        $this->syncRows('Shelf asset transfer details', $query, function (object $row): void {
            $assetTransferId = $this->resolveShelfAssetTransferId($this->nullableInt($row->asset_transfer_id ?? null));
            $assetId = $this->resolveShelfAssetId($this->nullableInt($row->asset_id ?? null));

            if ($assetTransferId === null || $assetId === null) {
                $this->warnMissingRelation(
                    'asset_transfer_details',
                    $row->id,
                    'asset_transfer_or_asset',
                    implode(':', [(string) $row->asset_transfer_id, (string) $row->asset_id])
                );

                return;
            }

            $targetId = $this->resolveTargetId(
                'asset_transfer_details',
                $row->id,
                'shelf_asset_transfer_details',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_asset_transfer_details')
                        ->where('asset_transfer_id', $assetTransferId)
                        ->where('asset_id', $assetId)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_asset_transfer_details')->updateOrInsert(
                ['id' => $targetId],
                [
                    'asset_transfer_id' => $assetTransferId,
                    'asset_id'          => $assetId,
                    'equipment'         => $this->nullableString($row->equipment ?? null),
                    'created_at'        => $row->created_at ?? now(),
                    'updated_at'        => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('asset_transfer_details', $row->id, 'shelf_asset_transfer_details', $targetId);
        });
    }

    protected function syncShelfVehicleChecksheets(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('vehicle_checksheets')) {
            $this->line('Legacy vehicle_checksheets table not found. Skipping shelf vehicle checksheets.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('vehicle_checksheets');

        $this->syncRows('Shelf vehicle checksheets', $query, function (object $row): void {
            $assetId = $this->nullableInt($row->asset_id ?? null) !== null
                ? $this->resolveShelfAssetId($this->nullableInt($row->asset_id ?? null))
                : null;
            $referenceNumber = $this->nullableString($row->reference_number ?? null) ?? sprintf('LEGACY-CHK-%d', $row->id);

            if ($this->nullableInt($row->asset_id ?? null) !== null && $assetId === null) {
                $this->warnMissingRelation('vehicle_checksheets', $row->id, 'asset_id', $row->asset_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'vehicle_checksheets',
                $row->id,
                'shelf_vehicle_checksheets',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_vehicle_checksheets')
                        ->where('reference_number', $referenceNumber)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_vehicle_checksheets')->updateOrInsert(
                ['id' => $targetId],
                [
                    'asset_id'                 => $assetId,
                    'reference_number'         => $referenceNumber,
                    'pic'                      => $this->nullableString($row->pic ?? null),
                    'license_plate'            => $this->nullableString($row->license_plate) ?? '',
                    'location'                 => $this->nullableString($row->location ?? null),
                    'destination'              => $this->nullableString($row->destination ?? null),
                    'remarks'                  => $this->nullableString($row->remarks ?? null),
                    'start_km'                 => $this->nullableInt($row->start_km ?? null),
                    'departure_time'           => $row->departure_time ?? null,
                    'departure_photo'          => $this->nullableString($row->departure_photo ?? null),
                    'departure_damage_report'  => $this->nullableString($row->departure_damage_report ?? null),
                    'end_km'                   => $this->nullableInt($row->end_km ?? null),
                    'return_time'              => $row->return_time ?? null,
                    'return_photo'             => $this->nullableString($row->return_photo ?? null),
                    'return_damage_report'     => $this->nullableString($row->return_damage_report ?? null),
                    'rental_duration'          => $row->rental_duration ?? null,
                    'distance_traveled'        => $row->distance_traveled ?? 0,
                    'created_at'               => $row->created_at ?? now(),
                    'updated_at'               => $row->updated_at ?? now(),
                    'deleted_at'               => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('vehicle_checksheets', $row->id, 'shelf_vehicle_checksheets', $targetId);
        });
    }

    protected function syncShelfCompanyDocumentSettings(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('business_entities')) {
            $this->line('Legacy business_entities table not found. Skipping shelf company document settings.');

            return;
        }

        $formatColumn = $this->firstExistingLegacyColumn('business_entities', [
            'format',
            'document_format',
            'letter_format',
            'number_format',
        ]);
        $colorColumn = $this->firstExistingLegacyColumn('business_entities', [
            'color',
            'document_color',
        ]);
        $letterheadColumn = $this->firstExistingLegacyColumn('business_entities', [
            'letterhead_path',
            'letterhead',
            'logo',
            'header_image',
        ]);

        if ($formatColumn === null && $colorColumn === null && $letterheadColumn === null) {
            $this->line('Legacy business_entities document columns not found. Skipping shelf company document settings.');

            return;
        }

        $selectColumns = array_values(array_unique(array_filter([
            'id',
            'name',
            $formatColumn,
            $colorColumn,
            $letterheadColumn,
        ])));
        $query = DB::connection($this->legacyConnection)->table('business_entities')->select($selectColumns);

        $this->syncRows('Shelf company document settings', $query, function (object $row) use (
            $colorColumn,
            $formatColumn,
            $letterheadColumn,
        ): void {
            $companyId = $this->resolveHelpdeskCompanyId($this->nullableInt($row->id ?? null));

            if ($companyId === null) {
                $this->warnMissingRelation('business_entities', $row->id, 'company_id', $row->id);

                return;
            }

            $existingSetting = DB::table('shelf_company_document_settings')
                ->where('company_id', $companyId)
                ->first();
            $format = $formatColumn !== null
                ? $this->nullableString(data_get($row, $formatColumn))
                : null;
            $color = $colorColumn !== null
                ? $this->nullableString(data_get($row, $colorColumn))
                : null;
            $letterheadPath = $letterheadColumn !== null
                ? $this->normalizeLegacyStoragePath($this->nullableString(data_get($row, $letterheadColumn)))
                : null;

            if (
                $format === null
                && $color === null
                && $letterheadPath === null
                && $existingSetting === null
            ) {
                return;
            }

            DB::table('shelf_company_document_settings')->updateOrInsert(
                ['company_id' => $companyId],
                [
                    'format'          => $format ?? $this->nullableString($existingSetting?->format ?? null),
                    'color'           => $color ?? $this->nullableString($existingSetting?->color ?? null),
                    'letterhead_path' => $letterheadPath ?? $this->nullableString($existingSetting?->letterhead_path ?? null),
                    'created_at'      => $existingSetting?->created_at ?? now(),
                    'updated_at'      => now(),
                ],
            );
        });
    }

    protected function syncShelfEmployeeJobPositions(): void
    {
        if (! Schema::hasTable('employees_job_positions')) {
            $this->warnOnce(
                'shelf:employees_job_positions:missing',
                'Skipping shelf employee job positions sync because target table [employees_job_positions] is missing. Install kepegawaian first.'
            );

            return;
        }

        $legacyTable = $this->legacyShelfJobPositionsTable();

        if ($legacyTable === null) {
            $this->line('Legacy employee job positions table not found. Skipping shelf job titles.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table($legacyTable);

        $this->syncRows('Shelf employee job positions', $query, function (object $row) use ($legacyTable): void {
            $legacyId = $this->nullableInt($row->id ?? null);

            if ($legacyId === null) {
                return;
            }

            $name = $this->nullableString($this->legacyRowValue($row, ['name', 'title', 'job_title']));

            if ($name === null) {
                $this->warnOnce(
                    "relation:{$legacyTable}:{$legacyId}:name",
                    sprintf('Skipping legacy record [%s:%s] because no job position name could be resolved.', $legacyTable, $legacyId)
                );

                return;
            }

            $companyId = $this->resolveCompanyId($this->nullableInt($this->legacyRowValue($row, ['company_id', 'business_entity_id'])));
            $creatorId = $this->resolveUserId($this->nullableInt($this->legacyRowValue($row, ['creator_id', 'created_by'])));

            $targetId = $this->resolveTargetId(
                $legacyTable,
                $legacyId,
                'employees_job_positions',
                function () use ($companyId, $name): ?int {
                    $query = DB::table('employees_job_positions')->where('name', $name);

                    if ($companyId === null) {
                        $query->whereNull('company_id');
                    } else {
                        $query->where('company_id', $companyId);
                    }

                    return $this->nullableInt($query->value('id'));
                }
            );

            if ($targetId === null) {
                return;
            }

            DB::table('employees_job_positions')->updateOrInsert(
                ['id' => $targetId],
                [
                    'sort'               => $this->nullableInt($row->sort ?? null),
                    'expected_employees' => $this->nullableInt($row->expected_employees ?? null),
                    'no_of_employee'     => $this->nullableInt($row->no_of_employee ?? null),
                    'no_of_recruitment'  => $this->nullableInt($row->no_of_recruitment ?? null),
                    'department_id'      => null,
                    'company_id'         => $companyId,
                    'creator_id'         => $creatorId,
                    'employment_type_id' => null,
                    'name'               => $name,
                    'description'        => $this->nullableString($row->description ?? null),
                    'requirements'       => $this->nullableString($row->requirements ?? null),
                    'is_active'          => $this->normalizeBoolean($row->is_active ?? null, true),
                    'deleted_at'         => $row->deleted_at ?? null,
                    'created_at'         => $row->created_at ?? now(),
                    'updated_at'         => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping($legacyTable, $legacyId, 'employees_job_positions', $targetId);
        });
    }

    protected function syncShelfEmployees(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            $this->warnOnce(
                'shelf:employees_employees:missing',
                'Skipping shelf employees sync because target table [employees_employees] is missing. Install kepegawaian first.'
            );

            return;
        }

        $legacyTable = $this->legacyShelfEmployeesTable();

        if ($legacyTable === null) {
            $this->line('Legacy employees table not found. Skipping shelf employees.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table($legacyTable);

        $this->syncRows('Shelf employees', $query, function (object $row) use ($legacyTable): void {
            $legacyId = $this->nullableInt($row->id ?? null);

            if ($legacyId === null) {
                return;
            }

            $companyId = $this->resolveCompanyId($this->nullableInt($this->legacyRowValue($row, ['company_id', 'business_entity_id'])));
            $userId = $this->resolveUserId($this->nullableInt($row->user_id ?? null), $companyId);
            $creatorId = $this->resolveUserId($this->nullableInt($this->legacyRowValue($row, ['creator_id', 'created_by'])), $companyId);
            $legacyJobId = $this->nullableInt($this->legacyRowValue($row, ['job_id', 'job_position_id']));
            $jobId = $legacyJobId !== null
                ? $this->resolveShelfEmployeeJobPositionId($legacyJobId)
                : null;

            $name = $this->nullableString($row->name ?? null);
            $workEmail = $this->nullableString($row->work_email ?? $row->email ?? null);
            $privateEmail = $this->nullableString($row->private_email ?? null);
            $employeeCode = $this->nullableString($row->employee_code ?? null);

            $targetId = $this->resolveTargetId(
                $legacyTable,
                $legacyId,
                'employees_employees',
                function () use ($companyId, $name, $privateEmail, $userId, $workEmail): ?int {
                    if ($userId !== null) {
                        $existingByUserId = $this->nullableInt(
                            DB::table('employees_employees')->where('user_id', $userId)->value('id')
                        );

                        if ($existingByUserId !== null) {
                            return $existingByUserId;
                        }
                    }

                    foreach (array_filter([$workEmail, $privateEmail]) as $email) {
                        $existingByEmail = $this->nullableInt(
                            DB::table('employees_employees')
                                ->where(function (Builder $query) use ($email): void {
                                    $query->where('work_email', $email)
                                        ->orWhere('private_email', $email);
                                })
                                ->value('id')
                        );

                        if ($existingByEmail !== null) {
                            return $existingByEmail;
                        }
                    }

                    if ($name === null) {
                        return null;
                    }

                    $query = DB::table('employees_employees')->where('name', $name);

                    if ($companyId === null) {
                        $query->whereNull('company_id');
                    } else {
                        $query->where('company_id', $companyId);
                    }

                    return $this->nullableInt($query->value('id'));
                }
            );

            if ($targetId === null) {
                return;
            }

            $payload = [
                'company_id'     => $companyId,
                'user_id'        => $userId,
                'creator_id'     => $creatorId,
                'job_id'         => $jobId,
                'name'           => $name,
                'job_title'      => $this->nullableString($row->job_title ?? null),
                'work_email'     => $workEmail,
                'private_email'  => $privateEmail,
                'mobile_phone'   => $this->nullableString($row->mobile_phone ?? null),
                'work_phone'     => $this->nullableString($row->work_phone ?? null),
                'is_active'      => $this->normalizeBoolean($row->is_active ?? null, true),
                'deleted_at'     => $row->deleted_at ?? null,
                'created_at'     => $row->created_at ?? now(),
                'updated_at'     => $row->updated_at ?? now(),
            ];

            if (Schema::hasColumn('employees_employees', 'employee_code')) {
                $payload['employee_code'] = $employeeCode;
            }

            DB::table('employees_employees')->updateOrInsert(
                ['id' => $targetId],
                $payload,
            );

            $this->rememberMapping($legacyTable, $legacyId, 'employees_employees', $targetId);
        });
    }

    protected function syncShelfAssetRow(object $row): ?int
    {
        $legacyAssetCompanyId = $this->nullableInt($this->legacyRowValue($row, ['company_id', 'business_entity_id']));
        $companyId = $this->resolveCompanyId($legacyAssetCompanyId);
        $categoryId = $this->nullableInt($row->category_id ?? null) !== null
            ? $this->mappedTargetId('categories', $row->category_id, 'shelf_categories')
            : null;
        $brandId = $this->nullableInt($row->brand_id ?? null) !== null
            ? $this->mappedTargetId('brands', $row->brand_id, 'shelf_brands')
            : null;
        $assetLocationId = $this->nullableInt($row->asset_location_id ?? null) !== null
            ? $this->mappedTargetId('asset_locations', $row->asset_location_id, 'shelf_asset_locations')
            : null;
        $legacyRecipientCompanyId = $this->nullableInt($this->legacyRowValue($row, ['recipient_company_id', 'recipient_business_entity_id']));
        $recipientCompanyId = $this->resolveCompanyId($legacyRecipientCompanyId);
        $recipientId = $this->resolveUserId(
            $this->nullableInt($row->recipient_id ?? null),
            $recipientCompanyId ?? $companyId,
        );
        $nbhResponsibleUserId = $this->resolveUserId($this->nullableInt($row->nbh_responsible_user_id ?? null), $companyId);

        $targetId = $this->resolveTargetId(
            'assets',
            $row->id,
            'shelf_assets',
            fn (): ?int => $this->findExistingShelfAssetId($row)
        );

        if ($targetId === null) {
            return null;
        }

        DB::table('shelf_assets')->updateOrInsert(
            ['id' => $targetId],
            [
                'purchase_date'           => $row->purchase_date ?? null,
                'company_id'              => $companyId,
                'name'                    => $this->nullableString($row->name) ?? 'Asset',
                'image'                   => $this->nullableString($row->image ?? null),
                'category_id'             => $categoryId,
                'brand_id'                => $brandId,
                'type'                    => $this->nullableString($row->type ?? null),
                'serial_number'           => $this->nullableString($row->serial_number ?? null),
                'imei1'                   => $this->nullableString($row->imei1 ?? null),
                'imei2'                   => $this->nullableString($row->imei2 ?? null),
                'item_price'              => $row->item_price ?? null,
                'asset_location_id'       => $assetLocationId,
                'qty'                     => $this->nullableInt($row->qty ?? null) ?? 1,
                'is_available'            => $this->normalizeBoolean($row->is_available ?? null, true),
                'condition_status'        => $this->normalizeShelfConditionStatus(
                    $row->condition_status ?? null,
                    $row->is_available ?? null,
                ),
                'nbh_status'              => $this->normalizeShelfNbhStatus($row->nbh_status ?? null),
                'nbh_reported_at'         => $row->nbh_reported_at ?? null,
                'audit_document_path'     => $this->nullableString($row->audit_document_path ?? null),
                'nbh_document_path'       => $this->nullableString($row->nbh_document_path ?? null),
                'nbh_notes'               => $this->nullableString($row->nbh_notes ?? null),
                'nbh_responsible_user_id' => $nbhResponsibleUserId,
                'recipient_id'            => $recipientId,
                'recipient_company_id'    => $recipientCompanyId,
                'created_at'              => $row->created_at ?? now(),
                'updated_at'              => $row->updated_at ?? now(),
            ],
        );

        $this->rememberMapping('assets', $row->id, 'shelf_assets', $targetId);
        $this->legacyShelfAssetsById[(int) $row->id] = $row;

        return $targetId;
    }

    protected function syncShelfAssetTransferRow(object $row): ?int
    {
        $legacyTransferCompanyId = $this->nullableInt($this->legacyRowValue($row, ['company_id', 'business_entity_id']));
        $companyId = $this->resolveCompanyId($legacyTransferCompanyId);
        $fromUserId = $this->resolveUserId($this->nullableInt($row->from_user_id ?? null), $companyId);
        $toUserId = $this->resolveUserId($this->nullableInt($row->to_user_id ?? null), $companyId);
        $letterNumber = $this->nullableString($row->letter_number ?? null) ?? sprintf('LEGACY-AST-%d', $row->id);

        if ($companyId === null || $fromUserId === null || $toUserId === null) {
            $this->warnMissingRelation(
                'asset_transfers',
                $row->id,
                'company_or_users',
                implode(':', [
                    (string) ($this->legacyRowValue($row, ['company_id', 'business_entity_id']) ?? ''),
                    (string) ($row->from_user_id ?? ''),
                    (string) ($row->to_user_id ?? ''),
                ])
            );

            return null;
        }

        $targetId = $this->resolveTargetId(
            'asset_transfers',
            $row->id,
            'shelf_asset_transfers',
            fn (): ?int => $this->findExistingShelfAssetTransferId($letterNumber)
        );

        if ($targetId === null) {
            return null;
        }

        DB::table('shelf_asset_transfers')->updateOrInsert(
            ['id' => $targetId],
            [
                'company_id'    => $companyId,
                'letter_number' => $letterNumber,
                'transfer_type' => $this->resolveShelfAssetTransferType($row, $fromUserId, $toUserId),
                'from_user_id'  => $fromUserId,
                'to_user_id'    => $toUserId,
                'transfer_date' => $row->transfer_date ?? $row->created_at ?? now(),
                'document'      => $this->nullableString($row->document ?? null),
                'created_at'    => $row->created_at ?? now(),
                'updated_at'    => $row->updated_at ?? now(),
            ],
        );

        $this->rememberMapping('asset_transfers', $row->id, 'shelf_asset_transfers', $targetId);
        $this->legacyShelfAssetTransfersById[(int) $row->id] = $row;

        return $targetId;
    }

    protected function resolveShelfEmployeeJobPositionId(int $legacyJobId): ?int
    {
        $legacyTable = $this->legacyShelfJobPositionsTable();

        if ($legacyTable === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId($legacyTable, $legacyJobId, 'employees_job_positions');

        if ($mappedId !== null && $this->targetRecordExists('employees_job_positions', $mappedId)) {
            return $mappedId;
        }

        return null;
    }

    protected function resolveShelfAssetTransferType(object $row, ?int $fromUserId, ?int $toUserId): ?string
    {
        $explicitTransferType = $this->extractExplicitLegacyShelfTransferType($row);
        $configuredTransferType = $this->inferShelfTransferTypeFromConfiguredCustodians(
            $this->nullableInt($row->from_user_id ?? null),
            $this->nullableInt($row->to_user_id ?? null),
            $fromUserId,
            $toUserId,
        );

        $fallbackTransferType = (bool) config('legacy-sync.shelf.asset_transfers.fallback_to_role_inference', true)
            ? \Cesa\Shelf\Models\AssetTransfer::inferTransferTypeFromUserIds($fromUserId, $toUserId)
            : null;

        return $this->mergeExplicitAndInferredShelfTransferTypes(
            $explicitTransferType,
            $configuredTransferType ?? $fallbackTransferType,
        );
    }

    protected function mergeExplicitAndInferredShelfTransferTypes(?string $explicitTransferType, ?string $inferredTransferType): ?string
    {
        if ($explicitTransferType === null) {
            return $inferredTransferType;
        }

        if ($inferredTransferType === null) {
            return $explicitTransferType;
        }

        if (
            $explicitTransferType === \Cesa\Shelf\Models\AssetTransfer::TYPE_REASSIGNMENT
            && in_array($inferredTransferType, [
                \Cesa\Shelf\Models\AssetTransfer::TYPE_HANDOVER,
                \Cesa\Shelf\Models\AssetTransfer::TYPE_RETURN,
            ], true)
        ) {
            return $inferredTransferType;
        }

        return $explicitTransferType;
    }

    protected function extractExplicitLegacyShelfTransferType(object $row): ?string
    {
        foreach (['transfer_type', 'document_type', 'type', 'status'] as $column) {
            if (! property_exists($row, $column)) {
                continue;
            }

            $normalizedTransferType = $this->normalizeShelfTransferType(
                $this->nullableString($row->{$column})
            );

            if ($normalizedTransferType !== null) {
                return $normalizedTransferType;
            }
        }

        return null;
    }

    protected function inferShelfTransferTypeFromConfiguredCustodians(
        ?int $legacyFromUserId,
        ?int $legacyToUserId,
        ?int $targetFromUserId,
        ?int $targetToUserId,
    ): ?string {
        $fromHasKnownCustodianIdentity = $this->hasKnownShelfCustodianIdentity($legacyFromUserId, $targetFromUserId);
        $toHasKnownCustodianIdentity = $this->hasKnownShelfCustodianIdentity($legacyToUserId, $targetToUserId);

        if (! $this->hasConfiguredShelfCustodianIdentities() && ! $fromHasKnownCustodianIdentity && ! $toHasKnownCustodianIdentity) {
            return null;
        }

        if (($legacyFromUserId === null && $targetFromUserId === null) || ($legacyToUserId === null && $targetToUserId === null)) {
            return null;
        }

        $fromIsCustodian = $this->matchesConfiguredShelfCustodianIdentity($legacyFromUserId, $targetFromUserId);
        $toIsCustodian = $this->matchesConfiguredShelfCustodianIdentity($legacyToUserId, $targetToUserId);

        return match (true) {
            $fromIsCustodian && ! $toIsCustodian   => \Cesa\Shelf\Models\AssetTransfer::TYPE_HANDOVER,
            ! $fromIsCustodian && ! $toIsCustodian => \Cesa\Shelf\Models\AssetTransfer::TYPE_REASSIGNMENT,
            ! $fromIsCustodian && $toIsCustodian   => \Cesa\Shelf\Models\AssetTransfer::TYPE_RETURN,
            default                                => null,
        };
    }

    protected function hasConfiguredShelfCustodianIdentities(): bool
    {
        foreach ([
            'custodian_legacy_user_ids',
            'custodian_legacy_user_emails',
            'custodian_legacy_user_names',
            'custodian_target_user_ids',
            'custodian_target_user_emails',
            'custodian_target_user_names',
        ] as $key) {
            if (config('legacy-sync.shelf.asset_transfers.'.$key, []) !== []) {
                return true;
            }
        }

        return false;
    }

    protected function hasKnownShelfCustodianIdentity(?int $legacyUserId, ?int $targetUserId): bool
    {
        $legacyUserName = $legacyUserId !== null ? $this->legacyUserName($legacyUserId) : null;

        if ($legacyUserName !== null && in_array($legacyUserName, ['ga', 'general affair', 'general_affair', 'general affairs', 'general_affairs'], true)) {
            return true;
        }

        $targetUserName = $targetUserId !== null ? $this->targetUserName($targetUserId) : null;

        return $targetUserName !== null
            && in_array($targetUserName, ['ga', 'general affair', 'general_affair', 'general affairs', 'general_affairs'], true);
    }

    protected function matchesConfiguredShelfCustodianIdentity(?int $legacyUserId, ?int $targetUserId): bool
    {
        if ($legacyUserId !== null && in_array($legacyUserId, config('legacy-sync.shelf.asset_transfers.custodian_legacy_user_ids', []), true)) {
            return true;
        }

        $legacyUserEmail = $legacyUserId !== null ? $this->legacyUserEmail($legacyUserId) : null;

        if ($legacyUserEmail !== null && in_array($legacyUserEmail, config('legacy-sync.shelf.asset_transfers.custodian_legacy_user_emails', []), true)) {
            return true;
        }

        $legacyUserName = $legacyUserId !== null ? $this->legacyUserName($legacyUserId) : null;

        if ($this->matchesShelfCustodianName($legacyUserName, 'legacy')) {
            return true;
        }

        if ($targetUserId !== null && in_array($targetUserId, config('legacy-sync.shelf.asset_transfers.custodian_target_user_ids', []), true)) {
            return true;
        }

        $targetUserEmail = $targetUserId !== null ? $this->targetUserEmail($targetUserId) : null;

        if ($targetUserEmail !== null && in_array($targetUserEmail, config('legacy-sync.shelf.asset_transfers.custodian_target_user_emails', []), true)) {
            return true;
        }

        $targetUserName = $targetUserId !== null ? $this->targetUserName($targetUserId) : null;

        return $this->matchesShelfCustodianName($targetUserName, 'target');
    }

    protected function matchesShelfCustodianName(?string $name, string $source): bool
    {
        if ($name === null) {
            return false;
        }

        if (in_array($name, ['ga', 'general affair', 'general_affair', 'general affairs', 'general_affairs'], true)) {
            return true;
        }

        return in_array(
            $name,
            array_map(
                fn (mixed $configuredName): ?string => $this->normalizeLookupName(is_string($configuredName) ? $configuredName : null),
                config("legacy-sync.shelf.asset_transfers.custodian_{$source}_user_names", [])
            ),
            true
        );
    }

    protected function resolveShelfAssetId(?int $legacyAssetId): ?int
    {
        if ($legacyAssetId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('assets', $legacyAssetId, 'shelf_assets');

        if ($mappedId !== null && $this->targetRecordExists('shelf_assets', $mappedId)) {
            return $mappedId;
        }

        $legacyAsset = $this->legacyShelfAssetRow($legacyAssetId);

        if ($legacyAsset === null) {
            return null;
        }

        $existingId = $this->findExistingShelfAssetId($legacyAsset);

        if ($existingId !== null) {
            $this->rememberMapping('assets', $legacyAssetId, 'shelf_assets', $existingId);

            return $existingId;
        }

        return $this->syncShelfAssetRow($legacyAsset);
    }

    protected function resolveShelfAssetTransferId(?int $legacyAssetTransferId): ?int
    {
        if ($legacyAssetTransferId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('asset_transfers', $legacyAssetTransferId, 'shelf_asset_transfers');

        if ($mappedId !== null && $this->targetRecordExists('shelf_asset_transfers', $mappedId)) {
            return $mappedId;
        }

        $legacyAssetTransfer = $this->legacyShelfAssetTransferRow($legacyAssetTransferId);

        if ($legacyAssetTransfer === null) {
            return null;
        }

        $letterNumber = $this->nullableString($legacyAssetTransfer->letter_number ?? null)
            ?? sprintf('LEGACY-AST-%d', $legacyAssetTransferId);
        $existingId = $this->findExistingShelfAssetTransferId($letterNumber);

        if ($existingId !== null) {
            $this->rememberMapping('asset_transfers', $legacyAssetTransferId, 'shelf_asset_transfers', $existingId);

            return $existingId;
        }

        return $this->syncShelfAssetTransferRow($legacyAssetTransfer);
    }

    protected function findExistingShelfAssetId(object $row): ?int
    {
        return $this->nullableInt(
            DB::table('shelf_assets')
                ->when(
                    $this->nullableString($row->serial_number ?? null) !== null,
                    fn (Builder $query): Builder => $query->where('serial_number', $this->nullableString($row->serial_number))
                )
                ->where('name', $this->nullableString($row->name) ?? '')
                ->value('id')
        );
    }

    protected function findExistingShelfAssetTransferId(string $letterNumber): ?int
    {
        return $this->nullableInt(
            DB::table('shelf_asset_transfers')
                ->where('letter_number', $letterNumber)
                ->value('id')
        );
    }

    protected function legacyShelfAssetRow(int $legacyAssetId): ?object
    {
        if (! array_key_exists($legacyAssetId, $this->legacyShelfAssetsById)) {
            $this->loadLegacyShelfAssets();
        }

        return $this->legacyShelfAssetsById[$legacyAssetId] ?? null;
    }

    protected function legacyShelfAssetTransferRow(int $legacyAssetTransferId): ?object
    {
        if (! array_key_exists($legacyAssetTransferId, $this->legacyShelfAssetTransfersById)) {
            $this->loadLegacyShelfAssetTransfers();
        }

        return $this->legacyShelfAssetTransfersById[$legacyAssetTransferId] ?? null;
    }

    protected function loadLegacyShelfAssets(): void
    {
        if ($this->legacyShelfAssetsLoaded) {
            return;
        }

        $this->legacyShelfAssetsLoaded = true;

        if (! Schema::connection($this->legacyConnection)->hasTable('assets')) {
            return;
        }

        $this->legacyShelfAssetsById = DB::connection($this->legacyConnection)
            ->table('assets')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => $row])
            ->all();
    }

    protected function loadLegacyShelfAssetTransfers(): void
    {
        if ($this->legacyShelfAssetTransfersLoaded) {
            return;
        }

        $this->legacyShelfAssetTransfersLoaded = true;

        if (! Schema::connection($this->legacyConnection)->hasTable('asset_transfers')) {
            return;
        }

        $this->legacyShelfAssetTransfersById = DB::connection($this->legacyConnection)
            ->table('asset_transfers')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => $row])
            ->all();
    }

    protected function syncShelfApprovalLevels(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('approval_levels')) {
            $this->line('Legacy approval_levels table not found. Skipping shelf approval levels.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('approval_levels');
        $hasDivisionColumn = $this->legacyTableHasColumn('approval_levels', 'division');

        $this->syncRows('Shelf approval levels', $query, function (object $row) use ($hasDivisionColumn): void {
            $requestType = $this->normalizeShelfRequestType($row->request_type ?? null);
            $division = $hasDivisionColumn
                ? ($this->nullableString($row->division ?? null) ?? '*')
                : '*';
            $level = (int) ($row->level ?? 1);

            $targetId = $this->resolveTargetId(
                'approval_levels',
                $row->id,
                'shelf_approval_levels',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_approval_levels')
                        ->where('request_type', $requestType)
                        ->where('level', $level)
                        ->where('division', $division)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_approval_levels')->updateOrInsert(
                ['id' => $targetId],
                [
                    'request_type'   => $requestType,
                    'division'       => $division,
                    'level'          => $level,
                    'approver_name'  => $this->nullableString($row->approver_name) ?? '',
                    'approver_email' => $this->nullableString($row->approver_email) ?? '',
                    'created_at'     => $row->created_at ?? now(),
                    'updated_at'     => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('approval_levels', $row->id, 'shelf_approval_levels', $targetId);
        });
    }

    protected function syncShelfAssetRequests(): void
    {
        $legacyTable = $this->firstExistingLegacyTable(['asset_requests', 'public_asset_requests']);

        if ($legacyTable === null) {
            $this->line('Legacy asset request table not found. Skipping shelf asset requests.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table($legacyTable);

        $hasApprovalTrackColumn = $this->legacyTableHasColumn($legacyTable, 'approval_track');
        $hasAttachmentPathColumn = $this->legacyTableHasColumn($legacyTable, 'attachment_path');
        $hasAttachmentOriginalNameColumn = $this->legacyTableHasColumn($legacyTable, 'attachment_original_name');
        $hasStatusColumn = $this->legacyTableHasColumn($legacyTable, 'status');
        $hasAdminNotesColumn = $this->legacyTableHasColumn($legacyTable, 'admin_notes');
        $hasUserIdColumn = $this->legacyTableHasColumn($legacyTable, 'user_id');
        $hasAssetIdColumn = $this->legacyTableHasColumn($legacyTable, 'asset_id');

        $this->syncRows('Shelf asset requests', $query, function (object $row) use (
            $legacyTable,
            $hasAdminNotesColumn,
            $hasApprovalTrackColumn,
            $hasAssetIdColumn,
            $hasAttachmentOriginalNameColumn,
            $hasAttachmentPathColumn,
            $hasStatusColumn,
            $hasUserIdColumn,
        ): void {
            $assetId = $hasAssetIdColumn && $this->nullableInt($row->asset_id ?? null) !== null
                ? $this->resolveShelfAssetId($this->nullableInt($row->asset_id ?? null))
                : null;
            $assetCompanyId = $assetId !== null
                ? $this->nullableInt(DB::table('shelf_assets')->where('id', $assetId)->value('company_id'))
                : null;
            $userId = $hasUserIdColumn
                ? $this->resolveUserId($this->nullableInt($row->user_id ?? null), $assetCompanyId)
                : null;
            $uuid = $this->nullableString($row->uuid ?? null) ?? (string) Str::uuid();

            if ($hasAssetIdColumn && $this->nullableInt($row->asset_id ?? null) !== null && $assetId === null) {
                $this->warnMissingRelation($legacyTable, $row->id, 'asset_id', $row->asset_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                $legacyTable,
                $row->id,
                'shelf_asset_requests',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_asset_requests')
                        ->where('uuid', $uuid)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_asset_requests')->updateOrInsert(
                ['id' => $targetId],
                [
                    'uuid'                     => $uuid,
                    'request_type'             => $this->normalizeShelfRequestType($row->request_type ?? null),
                    'requester_name'           => $this->nullableString($row->requester_name) ?? '',
                    'email'                    => $this->nullableString($row->email) ?? '',
                    'division'                 => $this->nullableString($row->division) ?? '',
                    'approval_track'           => $hasApprovalTrackColumn ? $this->nullableString($row->approval_track ?? null) : null,
                    'placement'                => $this->nullableString($row->placement) ?? '',
                    'item_name'                => $this->nullableString($row->item_name) ?? '',
                    'qty'                      => $this->nullableInt($row->qty ?? null) ?? 1,
                    'attachment_path'          => $hasAttachmentPathColumn ? $this->nullableString($row->attachment_path ?? null) : null,
                    'attachment_original_name' => $hasAttachmentOriginalNameColumn ? $this->nullableString($row->attachment_original_name ?? null) : null,
                    'status'                   => $hasStatusColumn ? $this->normalizeSimpleStatus($row->status ?? null) : 'pending',
                    'admin_notes'              => $hasAdminNotesColumn ? $this->nullableString($row->admin_notes ?? null) : null,
                    'user_id'                  => $userId,
                    'asset_id'                 => $assetId,
                    'created_at'               => $row->created_at ?? now(),
                    'updated_at'               => $row->updated_at ?? now(),
                    'deleted_at'               => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping($legacyTable, $row->id, 'shelf_asset_requests', $targetId);
        });
    }

    protected function syncShelfRequestApprovals(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('request_approvals')) {
            $this->line('Legacy request_approvals table not found. Skipping shelf request approvals.');

            return;
        }

        $legacyRequestTable = $this->firstExistingLegacyTable(['asset_requests', 'public_asset_requests']) ?? 'asset_requests';
        $requestForeignKey = $this->legacyTableHasColumn('request_approvals', 'asset_request_id')
            ? 'asset_request_id'
            : ($this->legacyTableHasColumn('request_approvals', 'public_asset_request_id') ? 'public_asset_request_id' : null);

        if ($requestForeignKey === null) {
            $this->line('Legacy request_approvals table does not have an asset request foreign key. Skipping shelf request approvals.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('request_approvals');

        $this->syncRows('Shelf request approvals', $query, function (object $row) use ($legacyRequestTable, $requestForeignKey): void {
            $assetRequestId = $this->mappedTargetId($legacyRequestTable, $row->{$requestForeignKey}, 'shelf_asset_requests');
            $approvalLevelId = $this->mappedTargetId('approval_levels', $row->approval_level_id, 'shelf_approval_levels');

            if ($assetRequestId === null || $approvalLevelId === null) {
                $this->warnMissingRelation(
                    'request_approvals',
                    $row->id,
                    'asset_request_or_approval_level',
                    implode(':', [(string) $row->{$requestForeignKey}, (string) $row->approval_level_id])
                );

                return;
            }

            $token = $this->nullableString($row->token ?? null) ?? sprintf('legacy-shelf-approval-%s', $row->id);

            $targetId = $this->resolveTargetId(
                'request_approvals',
                $row->id,
                'shelf_request_approvals',
                fn (): ?int => $this->nullableInt(
                    DB::table('shelf_request_approvals')
                        ->where('token', $token)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('shelf_request_approvals')->updateOrInsert(
                ['id' => $targetId],
                [
                    'asset_request_id'  => $assetRequestId,
                    'approval_level_id' => $approvalLevelId,
                    'token'             => $token,
                    'level'             => (int) ($row->level ?? 1),
                    'approver_name'     => $this->nullableString($row->approver_name) ?? '',
                    'approver_email'    => $this->nullableString($row->approver_email) ?? '',
                    'status'            => $this->normalizeSimpleStatus($row->status ?? null),
                    'notes'             => $this->nullableString($row->notes ?? null),
                    'responded_at'      => $row->responded_at ?? null,
                    'created_at'        => $row->created_at ?? now(),
                    'updated_at'        => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('request_approvals', $row->id, 'shelf_request_approvals', $targetId);
        });
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

    protected function syncHelpdeskPriorities(): void
    {
        $query = DB::connection($this->legacyConnection)->table('priorities');

        $this->syncRows('Helpdesk priorities', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'priorities',
                $row->id,
                'helpdesk_priorities',
                fn (): ?int => $this->nullableInt(
                    DB::table('helpdesk_priorities')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_priorities')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $this->nullableString($row->name) ?? 'Priority',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('priorities', $row->id, 'helpdesk_priorities', $targetId);
        });
    }

    protected function syncHelpdeskTicketStatuses(): void
    {
        $query = DB::connection($this->legacyConnection)->table('ticket_statuses');

        $this->syncRows('Helpdesk ticket statuses', $query, function (object $row): void {
            $normalizedStatusName = $this->normalizeHelpdeskStatusName($this->nullableString($row->name));

            $targetId = $this->resolveTargetId(
                'ticket_statuses',
                $row->id,
                'helpdesk_ticket_statuses',
                fn (): ?int => $this->nullableInt(
                    DB::table('helpdesk_ticket_statuses')
                        ->where('name', $normalizedStatusName)
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_ticket_statuses')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'       => $normalizedStatusName,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('ticket_statuses', $row->id, 'helpdesk_ticket_statuses', $targetId);
        });
    }

    protected function syncHelpdeskUnits(): void
    {
        $query = DB::connection($this->legacyConnection)->table('units');

        $this->syncRows('Helpdesk units', $query, function (object $row): void {
            $targetId = $this->resolveTargetId(
                'units',
                $row->id,
                'helpdesk_units',
                fn (): ?int => $this->nullableInt(
                    DB::table('helpdesk_units')
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_units')->updateOrInsert(
                ['id' => $targetId],
                [
                    'name'        => $this->nullableString($row->name) ?? 'Unit',
                    'description' => $this->nullableString($row->description ?? null),
                    'created_at'  => $row->created_at ?? now(),
                    'updated_at'  => $row->updated_at ?? now(),
                    'deleted_at'  => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('units', $row->id, 'helpdesk_units', $targetId);
        });
    }

    protected function syncHelpdeskUnitUsers(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('user_entities')) {
            $this->line('Legacy user_entities table not found. Skipping helpdesk unit assignments.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('user_entities');

        if ($this->legacyTableHasColumn('user_entities', 'entity_type')) {
            $query->where('entity_type', 'like', '%Unit');
        }

        $this->syncRows('Helpdesk unit users', $query, function (object $row): void {
            $unitId = $this->mappedTargetId('units', $row->entity_id, 'helpdesk_units');
            $userId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($unitId === null || $userId === null) {
                $this->warnMissingRelation(
                    'user_entities',
                    $row->id ?? ($row->user_id.'-'.$row->entity_id),
                    'user_or_unit',
                    implode(':', [(string) $row->user_id, (string) $row->entity_id])
                );

                return;
            }

            DB::table('helpdesk_unit_user')->updateOrInsert(
                [
                    'unit_id' => $unitId,
                    'user_id' => $userId,
                ],
                [
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ],
            );
        });
    }

    protected function syncHelpdeskProblemCategories(): void
    {
        $query = DB::connection($this->legacyConnection)->table('problem_categories');

        $this->syncRows('Helpdesk problem categories', $query, function (object $row): void {
            $unitId = $this->mappedTargetId('units', $row->unit_id, 'helpdesk_units');

            if ($unitId === null) {
                $this->warnMissingRelation('problem_categories', $row->id, 'unit_id', $row->unit_id);

                return;
            }

            $targetId = $this->resolveTargetId(
                'problem_categories',
                $row->id,
                'helpdesk_problem_categories',
                fn (): ?int => $this->nullableInt(
                    DB::table('helpdesk_problem_categories')
                        ->where('unit_id', $unitId)
                        ->where('name', $this->nullableString($row->name) ?? '')
                        ->value('id')
                ),
            );

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_problem_categories')->updateOrInsert(
                ['id' => $targetId],
                [
                    'unit_id'                => $unitId,
                    'name'                   => $this->nullableString($row->name) ?? 'Category',
                    'default_responsible_id' => null,
                    'created_at'             => $row->created_at ?? now(),
                    'updated_at'             => $row->updated_at ?? now(),
                    'deleted_at'             => $row->deleted_at ?? null,
                ],
            );

            $this->rememberMapping('problem_categories', $row->id, 'helpdesk_problem_categories', $targetId);
        });
    }

    protected function syncHelpdeskTickets(): void
    {
        $query = DB::connection($this->legacyConnection)->table('tickets');
        $hasBusinessEntityColumn = $this->legacyTableHasColumn('tickets', 'business_entities_id');
        $hasAttachmentColumn = $this->legacyTableHasColumn('tickets', 'supporting_attachments');
        $hasCloseReasonColumn = Schema::hasColumn('helpdesk_tickets', 'close_reason');
        $hasCancelReasonColumn = Schema::hasColumn('helpdesk_tickets', 'cancel_reason');
        $hasReopenReasonColumn = Schema::hasColumn('helpdesk_tickets', 'reopen_reason');

        $this->syncRows('Helpdesk tickets', $query, function (object $row) use (
            $hasAttachmentColumn,
            $hasBusinessEntityColumn,
            $hasCancelReasonColumn,
            $hasCloseReasonColumn,
            $hasReopenReasonColumn,
        ): void {
            $priorityId = $this->mappedTargetId('priorities', $row->priority_id, 'helpdesk_priorities');
            $unitId = $this->mappedTargetId('units', $row->unit_id, 'helpdesk_units');
            $ownerId = $this->resolveUserId($this->nullableInt($row->owner_id));
            $problemCategoryId = $this->mappedTargetId('problem_categories', $row->problem_category_id, 'helpdesk_problem_categories');
            $statusId = $this->mappedTargetId('ticket_statuses', $row->ticket_statuses_id, 'helpdesk_ticket_statuses');
            $responsibleId = $this->resolveUserId($this->nullableInt($row->responsible_id ?? null));
            $companyId = $hasBusinessEntityColumn
                ? $this->resolveHelpdeskCompanyId($this->nullableInt($row->business_entities_id ?? null))
                : null;

            if ($priorityId === null || $unitId === null || $ownerId === null || $problemCategoryId === null || $statusId === null) {
                $this->warnMissingRelation(
                    'tickets',
                    $row->id,
                    'ticket_dependency',
                    implode(':', [
                        (string) $row->priority_id,
                        (string) $row->unit_id,
                        (string) $row->owner_id,
                        (string) $row->problem_category_id,
                        (string) $row->ticket_statuses_id,
                    ])
                );

                return;
            }

            $targetId = $this->resolveTargetId('tickets', $row->id, 'helpdesk_tickets');

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_tickets')->updateOrInsert(
                ['id' => $targetId],
                [
                    'priority_id'            => $priorityId,
                    'unit_id'                => $unitId,
                    'owner_id'               => $ownerId,
                    'problem_category_id'    => $problemCategoryId,
                    'ticket_status_id'       => $statusId,
                    'responsible_id'         => $responsibleId,
                    'company_id'             => $companyId,
                    'title'                  => $this->nullableString($row->title) ?? 'Untitled Ticket',
                    'description'            => $this->nullableString($row->description) ?? '',
                    'supporting_attachments' => $hasAttachmentColumn
                        ? $this->normalizeAttachmentArrayPayload($row->supporting_attachments ?? null)
                        : null,
                    'approved_at'            => $row->approved_at ?? null,
                    'solved_at'              => $row->solved_at ?? null,
                    'created_at'             => $row->created_at ?? now(),
                    'updated_at'             => $row->updated_at ?? now(),
                    'deleted_at'             => $row->deleted_at ?? null,
                    ...($hasCloseReasonColumn ? ['close_reason' => null] : []),
                    ...($hasCancelReasonColumn ? ['cancel_reason' => null] : []),
                    ...($hasReopenReasonColumn ? ['reopen_reason' => null] : []),
                ],
            );

            $this->rememberMapping('tickets', $row->id, 'helpdesk_tickets', $targetId);
        });
    }

    protected function syncHelpdeskComments(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('comments')) {
            $this->line('Legacy comments table not found. Skipping helpdesk comments.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('comments');
        $hasAttachmentColumn = $this->legacyTableHasColumn('comments', 'attachments');
        $hasVisibilityColumn = Schema::hasColumn('helpdesk_comments', 'visibility');

        $this->syncRows('Helpdesk comments', $query, function (object $row) use ($hasAttachmentColumn, $hasVisibilityColumn): void {
            $ticketId = $this->mappedTargetId('tickets', $row->tiket_id, 'helpdesk_tickets');
            $userId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($ticketId === null) {
                $this->warnMissingRelation('comments', $row->id, 'ticket_id', $row->tiket_id);

                return;
            }

            $targetId = $this->resolveTargetId('comments', $row->id, 'helpdesk_comments');

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_comments')->updateOrInsert(
                ['id' => $targetId],
                [
                    'ticket_id'   => $ticketId,
                    'user_id'     => $userId,
                    'comment'     => $this->nullableString($row->comment) ?? '',
                    'attachments' => $hasAttachmentColumn
                        ? $this->normalizeAttachmentArrayPayload($row->attachments ?? null)
                        : null,
                    'created_at'  => $row->created_at ?? now(),
                    'updated_at'  => $row->updated_at ?? now(),
                    'deleted_at'  => $row->deleted_at ?? null,
                    ...($hasVisibilityColumn ? ['visibility' => 'public'] : []),
                ],
            );

            $this->rememberMapping('comments', $row->id, 'helpdesk_comments', $targetId);
        });
    }

    protected function syncHelpdeskTicketHistories(): void
    {
        if (! Schema::connection($this->legacyConnection)->hasTable('ticket_histories')) {
            $this->line('Legacy ticket_histories table not found. Skipping helpdesk ticket histories.');

            return;
        }

        $query = DB::connection($this->legacyConnection)->table('ticket_histories');

        $this->syncRows('Helpdesk ticket histories', $query, function (object $row): void {
            $ticketId = $this->mappedTargetId('tickets', $row->ticket_id, 'helpdesk_tickets');
            $statusId = $this->mappedTargetId('ticket_statuses', $row->ticket_statuses_id, 'helpdesk_ticket_statuses');
            $userId = $this->resolveUserId($this->nullableInt($row->user_id));

            if ($ticketId === null || $statusId === null) {
                $this->warnMissingRelation(
                    'ticket_histories',
                    $row->id,
                    'ticket_or_status',
                    implode(':', [(string) $row->ticket_id, (string) $row->ticket_statuses_id])
                );

                return;
            }

            $targetId = $this->resolveTargetId('ticket_histories', $row->id, 'helpdesk_ticket_histories');

            if ($targetId === null) {
                return;
            }

            DB::table('helpdesk_ticket_histories')->updateOrInsert(
                ['id' => $targetId],
                [
                    'ticket_id'        => $ticketId,
                    'ticket_status_id' => $statusId,
                    'user_id'          => $userId,
                    'created_at'       => $row->created_at ?? now(),
                    'updated_at'       => $row->updated_at ?? now(),
                ],
            );

            $this->rememberMapping('ticket_histories', $row->id, 'helpdesk_ticket_histories', $targetId);
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

    protected function resolveUserId(?int $legacyUserId, ?int $targetCompanyId = null): ?int
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
            $legacyEmail = $this->normalizeEmail($this->legacyUsersById[$legacyUserId]['email'] ?? null);
            $targetId = $legacyEmail !== null
                ? ($this->targetUsersByEmail[$legacyEmail] ?? null)
                : null;

            if ($targetId !== null) {
                $this->attachUserToMatchingEmployee($legacyUserId, $targetId);
                $this->rememberMapping('users', $legacyUserId, 'users', $targetId);

                return $targetId;
            }

            $targetEmployeeUserId = $this->resolveEmployeeLinkedUserId($legacyUserId);

            if ($targetEmployeeUserId !== null) {
                $this->rememberMapping('users', $legacyUserId, 'users', $targetEmployeeUserId);

                return $targetEmployeeUserId;
            }

            $targetUserIdByName = $this->resolveTargetUserIdByLegacyName($legacyUserId, $targetCompanyId);

            if ($targetUserIdByName !== null) {
                $this->rememberMapping('users', $legacyUserId, 'users', $targetUserIdByName);

                return $targetUserIdByName;
            }
        }

        if ($this->shouldCreateMissingUsers()) {
            $createdUserId = $this->createMissingUserFromLegacy($legacyUserId);

            if ($createdUserId !== null) {
                return $createdUserId;
            }

            $placeholderUserId = $this->createPlaceholderUserFromLegacyId($legacyUserId);

            if ($placeholderUserId !== null) {
                return $placeholderUserId;
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

    protected function resolveTargetUserIdByLegacyName(int $legacyUserId, ?int $targetCompanyId = null): ?int
    {
        $legacyUserName = $this->legacyUserName($legacyUserId);

        if ($legacyUserName === null) {
            return null;
        }

        $candidateUserIds = array_values(array_unique($this->targetUserIdsByName[$legacyUserName] ?? []));

        if ($candidateUserIds === []) {
            return null;
        }

        if ($targetCompanyId !== null) {
            $companyMatchedUserIds = array_values(array_filter(
                $candidateUserIds,
                fn (int $userId): bool => $this->targetUserMatchesCompany($userId, $targetCompanyId),
            ));

            if (count($companyMatchedUserIds) === 1) {
                return $companyMatchedUserIds[0];
            }
        }

        return count($candidateUserIds) === 1 ? $candidateUserIds[0] : null;
    }

    protected function targetUserMatchesCompany(int $userId, int $companyId): bool
    {
        if (($this->targetUserDefaultCompaniesById[$userId] ?? null) === $companyId) {
            return true;
        }

        if (Schema::hasTable('employees_employees')) {
            $query = DB::table('employees_employees')
                ->where('user_id', $userId)
                ->where('company_id', $companyId);

            if (Schema::hasColumn('employees_employees', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($query->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('user_allowed_companies')) {
            return DB::table('user_allowed_companies')
                ->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->exists();
        }

        return false;
    }

    protected function resolveCompanyId(?int $legacyCompanyId): ?int
    {
        if ($legacyCompanyId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('companies', $legacyCompanyId, 'companies');

        if ($mappedId !== null && $this->targetRecordExists('companies', $mappedId)) {
            $this->syncMappedCompanyNameFromBusinessEntity($mappedId, $legacyCompanyId);

            return $mappedId;
        }

        $this->loadLegacyCompanies();

        $legacyCompany = $this->legacyCompaniesById[$legacyCompanyId] ?? null;

        if ($legacyCompany !== null) {
            $companyCode = $this->normalizeCompanyCode($legacyCompany['company_id'] ?? null);
            $targetId = $companyCode !== null
                ? ($this->targetCompaniesByCompanyCode[$companyCode] ?? null)
                : null;

            if ($targetId === null) {
                $normalizedName = $this->normalizeLookupName($legacyCompany['name'] ?? null);
                $targetId = $normalizedName !== null
                    ? ($this->targetCompaniesByName[$normalizedName] ?? null)
                    : null;
            }

            if ($targetId !== null) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $targetId);

                return $targetId;
            }

            $createdCompanyId = $this->createMissingLegacyCompany($legacyCompanyId, $legacyCompany);

            if ($createdCompanyId !== null) {
                return $createdCompanyId;
            }
        }

        $legacyBusinessEntityName = $this->legacyBusinessEntityName($legacyCompanyId);

        if ($legacyBusinessEntityName !== null) {
            $normalizedLegacyBusinessEntityName = $this->normalizeLookupName($legacyBusinessEntityName);
            $targetId = $normalizedLegacyBusinessEntityName !== null
                ? ($this->targetCompaniesByName[$normalizedLegacyBusinessEntityName] ?? null)
                : null;

            if ($targetId !== null) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $targetId);

                return $targetId;
            }

            $createdCompanyId = $this->createMissingLegacyCompany($legacyCompanyId, [
                'company_id' => null,
                'name'       => $legacyBusinessEntityName,
            ]);

            if ($createdCompanyId !== null) {
                return $createdCompanyId;
            }
        }

        if ((bool) $this->option('trust-legacy-company-ids') && $this->targetRecordExists('companies', $legacyCompanyId)) {
            $this->rememberMapping('companies', $legacyCompanyId, 'companies', $legacyCompanyId);

            return $legacyCompanyId;
        }

        $placeholderCompanyId = $this->createPlaceholderLegacyCompany($legacyCompanyId);

        if ($placeholderCompanyId !== null) {
            return $placeholderCompanyId;
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

    protected function legacyBusinessEntityName(int $legacyBusinessEntityId): ?string
    {
        $this->loadHelpdeskBusinessEntities();

        return $this->legacyHelpdeskBusinessEntitiesById[$legacyBusinessEntityId] ?? null;
    }

    protected function syncMappedCompanyNameFromBusinessEntity(int $companyId, int $legacyBusinessEntityId): void
    {
        $legacyBusinessEntityName = $this->legacyBusinessEntityName($legacyBusinessEntityId);

        if ($legacyBusinessEntityName === null || $legacyBusinessEntityName === '') {
            return;
        }

        $company = Company::query()->find($companyId);

        if (! $company) {
            return;
        }

        if (! preg_match('/^Legacy Company \d+$/', (string) $company->name)) {
            return;
        }

        if ($company->name === $legacyBusinessEntityName) {
            return;
        }

        $previousNormalizedName = $this->normalizeLookupName($company->name);
        $company->name = $legacyBusinessEntityName;
        $company->save();

        if ($previousNormalizedName !== null) {
            unset($this->targetCompaniesByName[$previousNormalizedName]);
        }

        $normalizedBusinessEntityName = $this->normalizeLookupName($legacyBusinessEntityName);

        if ($normalizedBusinessEntityName !== null) {
            $this->targetCompaniesByName[$normalizedBusinessEntityName] = $companyId;
        }
    }

    protected function loadLegacyUsers(): void
    {
        if ($this->legacyUsersLoaded) {
            return;
        }

        $this->legacyUsersLoaded = true;
        $targetUserColumns = ['id', 'email', 'name'];

        if (Schema::hasColumn('users', 'default_company_id')) {
            $targetUserColumns[] = 'default_company_id';
        }

        $targetUsers = DB::table('users')
            ->select($targetUserColumns)
            ->get();

        $this->targetUserEmailsById = $targetUsers
            ->mapWithKeys(function (object $row): array {
                $normalizedEmail = $this->normalizeEmail($this->nullableString($row->email ?? null));

                if ($normalizedEmail === null) {
                    return [];
                }

                return [(int) $row->id => $normalizedEmail];
            })
            ->all();

        $this->targetUserNamesById = $targetUsers
            ->mapWithKeys(function (object $row): array {
                $normalizedName = $this->normalizeLookupName($this->nullableString($row->name ?? null));

                if ($normalizedName === null) {
                    return [];
                }

                return [(int) $row->id => $normalizedName];
            })
            ->all();

        $this->targetUserIdsByName = $targetUsers
            ->reduce(function (array $carry, object $row): array {
                $normalizedName = $this->normalizeLookupName($this->nullableString($row->name ?? null));

                if ($normalizedName === null) {
                    return $carry;
                }

                $carry[$normalizedName] ??= [];
                $carry[$normalizedName][] = (int) $row->id;

                return $carry;
            }, []);

        $this->targetUserDefaultCompaniesById = $targetUsers
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => $this->nullableInt($row->default_company_id ?? null)])
            ->all();

        $this->targetUsersByEmail = $targetUsers
            ->mapWithKeys(function (object $row): array {
                $normalizedEmail = $this->normalizeEmail($this->nullableString($row->email ?? null));

                if ($normalizedEmail === null) {
                    return [];
                }

                return [$normalizedEmail => (int) $row->id];
            })
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
        $existingUserId = $this->targetUsersByEmail[$this->normalizeEmail($email) ?? ''] ?? null;

        if ($existingUserId !== null) {
            $this->attachUserToMatchingEmployee($legacyUserId, $existingUserId);
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

        $this->targetUsersByEmail[$this->normalizeEmail($email) ?? strtolower($email)] = (int) $user->id;
        $this->attachUserToMatchingEmployee($legacyUserId, (int) $user->id);
        $this->rememberMapping('users', $legacyUserId, 'users', (int) $user->id);

        $this->line(sprintf(
            'Created missing user [%s] from legacy user ID [%d].',
            $email,
            $legacyUserId
        ));

        return (int) $user->id;
    }

    protected function createPlaceholderUserFromLegacyId(int $legacyUserId): ?int
    {
        $email = sprintf('legacy-user-%d@legacy-sync.local', $legacyUserId);
        $normalizedEmail = $this->normalizeEmail($email);
        $existingUserId = $normalizedEmail !== null
            ? ($this->targetUsersByEmail[$normalizedEmail] ?? null)
            : null;

        if ($existingUserId !== null) {
            $this->rememberMapping('users', $legacyUserId, 'users', $existingUserId);

            return $existingUserId;
        }

        $user = new SecurityUser;
        $user->forceFill([
            'name'      => 'Legacy User '.$legacyUserId,
            'email'     => $email,
            'password'  => Hash::make(Str::random(32)),
            'language'  => config('app.locale'),
            'is_active' => true,
        ]);
        $user->save();

        if ($normalizedEmail !== null) {
            $this->targetUsersByEmail[$normalizedEmail] = (int) $user->id;
        }

        $this->rememberMapping('users', $legacyUserId, 'users', (int) $user->id);

        $this->line(sprintf(
            'Created placeholder user [%s] for unresolved legacy user ID [%d].',
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

        $normalizedCandidate = $this->normalizeEmail($candidate);
        $normalizedFallback = $this->normalizeEmail(sprintf('legacy-user-%d@legacy-sync.local', $legacyUserId))
            ?? sprintf('legacy-user-%d@legacy-sync.local', $legacyUserId);
        $existingUserId = $normalizedCandidate !== null
            ? ($this->targetUsersByEmail[$normalizedCandidate] ?? null)
            : null;

        if ($existingUserId === null) {
            return $candidate;
        }

        return $normalizedFallback;
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
        $this->targetCompaniesByName = DB::table('companies')
            ->whereNotNull('name')
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $normalizedName = $this->normalizeLookupName($this->nullableString($row->name));

                if ($normalizedName === null) {
                    return [];
                }

                return [$normalizedName => (int) $row->id];
            })
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

    protected function loadTargetEmployees(): void
    {
        if ($this->targetEmployeesLoaded) {
            return;
        }

        $this->targetEmployeesLoaded = true;

        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        $query = DB::table('employees_employees');

        if (Schema::hasColumn('employees_employees', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $availableColumns = ['id'];

        foreach (['user_id', 'name', 'work_email', 'private_email'] as $column) {
            if (Schema::hasColumn('employees_employees', $column)) {
                $availableColumns[] = $column;
            }
        }

        $employeeUserCandidates = [];
        $employeeWithoutUserCandidates = [];

        foreach ($query->select($availableColumns)->get() as $row) {
            $employeeId = $this->nullableInt($row->id ?? null);

            if ($employeeId === null) {
                continue;
            }

            $userId = $this->nullableInt($row->user_id ?? null);

            if ($userId !== null && ! $this->targetRecordExists('users', $userId)) {
                $userId = null;
            }

            $identifiers = $this->employeeLookupIdentifiers(
                $this->nullableString($row->work_email ?? null),
                $this->nullableString($row->private_email ?? null),
                $this->nullableString($row->name ?? null),
            );

            if ($identifiers === []) {
                continue;
            }

            $this->targetEmployeeIdentifiersById[$employeeId] = $identifiers;

            foreach ($identifiers as $identifier) {
                if ($userId !== null) {
                    $employeeUserCandidates[$identifier][] = $userId;

                    continue;
                }

                $employeeWithoutUserCandidates[$identifier][] = $employeeId;
            }
        }

        foreach ($employeeUserCandidates as $identifier => $candidateUserIds) {
            $uniqueUserIds = array_values(array_unique($candidateUserIds));

            if (count($uniqueUserIds) === 1) {
                $this->targetEmployeeUserIdsByIdentifier[$identifier] = $uniqueUserIds[0];
            }
        }

        foreach ($employeeWithoutUserCandidates as $identifier => $candidateEmployeeIds) {
            $uniqueEmployeeIds = array_values(array_unique($candidateEmployeeIds));

            if (count($uniqueEmployeeIds) === 1 && ! isset($this->targetEmployeeUserIdsByIdentifier[$identifier])) {
                $this->targetEmployeesWithoutUsersByIdentifier[$identifier] = $uniqueEmployeeIds[0];
            }
        }
    }

    protected function resolveEmployeeLinkedUserId(int $legacyUserId): ?int
    {
        $this->loadTargetEmployees();

        foreach ($this->legacyUserLookupIdentifiers($legacyUserId) as $identifier) {
            $userId = $this->targetEmployeeUserIdsByIdentifier[$identifier] ?? null;

            if ($userId !== null && $this->targetRecordExists('users', $userId)) {
                return $userId;
            }
        }

        return null;
    }

    protected function attachUserToMatchingEmployee(int $legacyUserId, int $userId): void
    {
        $this->loadTargetEmployees();

        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        foreach ($this->legacyUserLookupIdentifiers($legacyUserId) as $identifier) {
            $employeeId = $this->targetEmployeesWithoutUsersByIdentifier[$identifier] ?? null;

            if ($employeeId === null) {
                continue;
            }

            $payload = ['user_id' => $userId];

            if (Schema::hasColumn('employees_employees', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            DB::table('employees_employees')
                ->where('id', $employeeId)
                ->update($payload);

            foreach ($this->targetEmployeeIdentifiersById[$employeeId] ?? [$identifier] as $employeeIdentifier) {
                unset($this->targetEmployeesWithoutUsersByIdentifier[$employeeIdentifier]);
                $this->targetEmployeeUserIdsByIdentifier[$employeeIdentifier] = $userId;
            }

            return;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function legacyUserLookupIdentifiers(int $legacyUserId): array
    {
        $legacyUser = $this->legacyUsersById[$legacyUserId] ?? null;

        if ($legacyUser === null) {
            return [];
        }

        return $this->employeeLookupIdentifiers(
            $legacyUser['email'] ?? null,
            null,
            $legacyUser['name'] ?? null,
        );
    }

    protected function legacyUserEmail(int $legacyUserId): ?string
    {
        $this->loadLegacyUsers();

        return $this->normalizeEmail($this->legacyUsersById[$legacyUserId]['email'] ?? null);
    }

    protected function legacyUserName(int $legacyUserId): ?string
    {
        $this->loadLegacyUsers();

        return $this->normalizeLookupName($this->legacyUsersById[$legacyUserId]['name'] ?? null);
    }

    protected function targetUserEmail(int $targetUserId): ?string
    {
        $this->loadLegacyUsers();

        return $this->targetUserEmailsById[$targetUserId] ?? null;
    }

    protected function targetUserName(int $targetUserId): ?string
    {
        $this->loadLegacyUsers();

        return $this->targetUserNamesById[$targetUserId] ?? null;
    }

    /**
     * @return array<int, string>
     */
    protected function employeeLookupIdentifiers(?string $workEmail, ?string $privateEmail, ?string $name): array
    {
        $identifiers = [];
        $normalizedWorkEmail = $this->normalizeEmail($workEmail);
        $normalizedPrivateEmail = $this->normalizeEmail($privateEmail);

        if ($normalizedWorkEmail !== null) {
            $identifiers[] = 'email:'.$normalizedWorkEmail;
        }

        if ($normalizedPrivateEmail !== null) {
            $identifiers[] = 'email:'.$normalizedPrivateEmail;
        }

        if ($normalizedWorkEmail === null && $normalizedPrivateEmail === null) {
            $normalizedName = $this->normalizeLookupName($name);

            if ($normalizedName !== null) {
                $identifiers[] = 'name:'.$normalizedName;
            }
        }

        return array_values(array_unique($identifiers));
    }

    protected function loadHelpdeskBusinessEntities(): void
    {
        if ($this->legacyHelpdeskBusinessEntitiesLoaded) {
            return;
        }

        $this->legacyHelpdeskBusinessEntitiesLoaded = true;

        if ($this->targetCompaniesByName === []) {
            $this->loadLegacyCompanies();
        }

        if (! Schema::connection($this->legacyConnection)->hasTable('business_entities')) {
            return;
        }

        $this->legacyHelpdeskBusinessEntitiesById = DB::connection($this->legacyConnection)
            ->table('business_entities')
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $name = $this->nullableString($row->name);

                if ($name === null) {
                    return [];
                }

                return [(int) $row->id => Str::of($name)->squish()->toString()];
            })
            ->all();
    }

    protected function resolveHelpdeskCompanyId(?int $legacyBusinessEntityId): ?int
    {
        if ($legacyBusinessEntityId === null) {
            return null;
        }

        $mappedId = $this->mappedTargetId('business_entities', $legacyBusinessEntityId, 'companies');

        if ($mappedId !== null && $this->targetRecordExists('companies', $mappedId)) {
            return $mappedId;
        }

        $this->loadHelpdeskBusinessEntities();

        $legacyName = $this->legacyHelpdeskBusinessEntitiesById[$legacyBusinessEntityId] ?? null;
        $normalizedLegacyName = $this->normalizeLookupName($legacyName);
        $targetId = $normalizedLegacyName !== null
            ? ($this->targetCompaniesByName[$normalizedLegacyName] ?? null)
            : null;

        if ($targetId !== null) {
            $this->rememberMapping('business_entities', $legacyBusinessEntityId, 'companies', $targetId);

            return $targetId;
        }

        if ($legacyName !== null && $legacyName !== '') {
            $createdCompanyId = $this->createMissingHelpdeskCompany($legacyBusinessEntityId, $legacyName);

            if ($createdCompanyId !== null) {
                return $createdCompanyId;
            }
        }

        if ((bool) $this->option('trust-legacy-company-ids') && $this->targetRecordExists('companies', $legacyBusinessEntityId)) {
            $this->rememberMapping('business_entities', $legacyBusinessEntityId, 'companies', $legacyBusinessEntityId);

            return $legacyBusinessEntityId;
        }

        $this->warnOnce(
            'business_entity:'.$legacyBusinessEntityId,
            sprintf(
                'Could not map legacy business entity ID [%d] to a company in CESA.',
                $legacyBusinessEntityId
            )
        );

        return null;
    }

    protected function createMissingHelpdeskCompany(int $legacyBusinessEntityId, string $legacyCompanyName): ?int
    {
        $normalizedName = $this->normalizeLookupName($legacyCompanyName);

        if ($normalizedName === null) {
            return null;
        }

        $existingCompanyId = $this->targetCompaniesByName[$normalizedName] ?? null;

        if ($existingCompanyId !== null && $this->targetRecordExists('companies', $existingCompanyId)) {
            $this->rememberMapping('business_entities', $legacyBusinessEntityId, 'companies', $existingCompanyId);

            return $existingCompanyId;
        }

        $company = Company::query()->create([
            'name'       => $legacyCompanyName,
            'company_id' => $this->generateCompanyCode($legacyCompanyName),
            'is_active'  => true,
        ]);

        $companyId = (int) $company->id;

        $this->targetCompaniesByName[$normalizedName] = $companyId;
        $this->targetCompaniesByCompanyCode[strtolower((string) $company->company_id)] = $companyId;

        $this->rememberMapping('business_entities', $legacyBusinessEntityId, 'companies', $companyId);

        $this->line(sprintf(
            'Created missing company [%s] from legacy business entity ID [%d].',
            $legacyCompanyName,
            $legacyBusinessEntityId
        ));

        return $companyId;
    }

    /**
     * @param  array{company_id: string|null, name: string|null}  $legacyCompany
     */
    protected function createMissingLegacyCompany(int $legacyCompanyId, array $legacyCompany): ?int
    {
        $legacyCompanyName = $this->nullableString($legacyCompany['name'] ?? null);
        $normalizedName = $this->normalizeLookupName($legacyCompanyName);
        $normalizedCompanyCode = $this->normalizeCompanyCode($legacyCompany['company_id'] ?? null);

        if ($normalizedCompanyCode !== null) {
            $existingCompanyId = $this->targetCompaniesByCompanyCode[$normalizedCompanyCode] ?? null;

            if ($existingCompanyId !== null && $this->targetRecordExists('companies', $existingCompanyId)) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $existingCompanyId);

                return $existingCompanyId;
            }
        }

        if ($normalizedName !== null) {
            $existingCompanyId = $this->targetCompaniesByName[$normalizedName] ?? null;

            if ($existingCompanyId !== null && $this->targetRecordExists('companies', $existingCompanyId)) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $existingCompanyId);

                return $existingCompanyId;
            }
        }

        $companyName = $legacyCompanyName
            ?? ($legacyCompany['company_id'] !== null ? trim((string) $legacyCompany['company_id']) : null)
            ?? 'Legacy Company '.$legacyCompanyId;
        $companyCode = $normalizedCompanyCode !== null && ! isset($this->targetCompaniesByCompanyCode[$normalizedCompanyCode])
            ? trim((string) $legacyCompany['company_id'])
            : $this->generateCompanyCode($companyName);

        $company = Company::query()->create([
            'name'       => $companyName,
            'company_id' => $companyCode,
            'is_active'  => true,
        ]);

        $companyId = (int) $company->id;

        $this->targetCompaniesByName[$this->normalizeLookupName($companyName) ?? Str::lower($companyName)] = $companyId;
        $this->targetCompaniesByCompanyCode[strtolower($companyCode)] = $companyId;

        $this->rememberMapping('companies', $legacyCompanyId, 'companies', $companyId);

        $this->line(sprintf(
            'Created missing company [%s] from legacy company ID [%d].',
            $companyName,
            $legacyCompanyId
        ));

        return $companyId;
    }

    protected function createPlaceholderLegacyCompany(int $legacyCompanyId): ?int
    {
        $placeholderName = 'Legacy Company '.$legacyCompanyId;
        $normalizedName = $this->normalizeLookupName($placeholderName);
        $placeholderCode = sprintf('LEGACY-%d', $legacyCompanyId);

        if ($normalizedName !== null) {
            $existingCompanyId = $this->targetCompaniesByName[$normalizedName] ?? null;

            if ($existingCompanyId !== null && $this->targetRecordExists('companies', $existingCompanyId)) {
                $this->rememberMapping('companies', $legacyCompanyId, 'companies', $existingCompanyId);

                return $existingCompanyId;
            }
        }

        $company = Company::query()->create([
            'name'       => $placeholderName,
            'company_id' => isset($this->targetCompaniesByCompanyCode[strtolower($placeholderCode)])
                ? $this->generateCompanyCode($placeholderName)
                : $placeholderCode,
            'is_active'  => true,
        ]);

        $companyId = (int) $company->id;

        if ($normalizedName !== null) {
            $this->targetCompaniesByName[$normalizedName] = $companyId;
        }

        $this->targetCompaniesByCompanyCode[strtolower((string) $company->company_id)] = $companyId;
        $this->rememberMapping('companies', $legacyCompanyId, 'companies', $companyId);

        $this->line(sprintf(
            'Created placeholder company [%s] for unresolved legacy company ID [%d].',
            $placeholderName,
            $legacyCompanyId
        ));

        return $companyId;
    }

    protected function generateCompanyCode(string $companyName): string
    {
        $baseCode = 'CMP-'.Str::upper(substr(sha1(Str::lower(Str::squish($companyName))), 0, 8));
        $candidate = $baseCode;
        $suffix = 1;

        while (DB::table('companies')->where('company_id', $candidate)->exists()) {
            $candidate = $baseCode.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function normalizeCompanyCode(?string $companyCode): ?string
    {
        $normalizedCompanyCode = Str::of((string) $companyCode)
            ->trim()
            ->lower()
            ->toString();

        return $normalizedCompanyCode !== '' ? $normalizedCompanyCode : null;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        $normalizedEmail = Str::of((string) $email)
            ->trim()
            ->lower()
            ->toString();

        return $normalizedEmail !== '' ? $normalizedEmail : null;
    }

    protected function normalizeLookupName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalizedName = Str::of($name)
            ->squish()
            ->lower()
            ->toString();

        return $normalizedName !== '' ? $normalizedName : null;
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

    protected function normalizeShelfTaskStatus(mixed $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'in_progress',
            'progress',
            'processing' => 'in_progress',
            'completed',
            'done',
            'closed'     => 'completed',
            default      => 'open',
        };
    }

    protected function normalizeShelfRequestType(mixed $requestType): string
    {
        return match (strtolower(trim((string) $requestType))) {
            'perbaikan_aset',
            'perbaikan-aset' => 'perbaikan_aset',
            'penarikan_aset',
            'penarikan-aset' => 'penarikan_aset',
            default          => 'pengadaan_aset',
        };
    }

    protected function normalizeShelfConditionStatus(mixed $conditionStatus, mixed $isAvailable): string
    {
        $value = strtolower(trim((string) $conditionStatus));

        return match ($value) {
            'lost'        => 'lost',
            'damaged'     => 'damaged',
            'transferred',
            'transfer'    => 'transferred',
            'available',
            'tersedia'    => 'available',
            default       => $this->normalizeBoolean($isAvailable, true) ? 'available' : 'transferred',
        };
    }

    protected function normalizeShelfNbhStatus(mixed $nbhStatus): string
    {
        return match (strtolower(trim((string) $nbhStatus))) {
            'pending',
            'process'    => 'pending',
            'resolved',
            'done'       => 'resolved',
            default      => 'none',
        };
    }

    protected function normalizeShelfNotificationType(mixed $notificationType): ?string
    {
        $value = strtolower(trim((string) $notificationType));

        return in_array($value, ['fixed_date', 'relative_date', 'monthly'], true)
            ? $value
            : null;
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

    protected function normalizeAttachmentArrayPayload(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $attachments = array_values(array_filter(
                $value,
                fn (mixed $item): bool => is_string($item) && trim($item) !== ''
            ));

            return $attachments === [] ? null : json_encode($attachments, JSON_UNESCAPED_UNICODE);
        }

        $string = $this->nullableString($value);

        if ($string === null) {
            return null;
        }

        $decoded = json_decode($string, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $attachments = array_values(array_filter(
                $decoded,
                fn (mixed $item): bool => is_string($item) && trim($item) !== ''
            ));

            return $attachments === [] ? null : json_encode($attachments, JSON_UNESCAPED_UNICODE);
        }

        return json_encode([$string], JSON_UNESCAPED_UNICODE);
    }

    protected function legacyTableHasColumn(string $table, string $column): bool
    {
        return Schema::connection($this->legacyConnection)->hasTable($table)
            && Schema::connection($this->legacyConnection)->hasColumn($table, $column);
    }

    protected function firstExistingLegacyTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::connection($this->legacyConnection)->hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function legacyShelfJobPositionsTable(): ?string
    {
        return $this->legacyShelfJobPositionsTable ??= $this->firstExistingLegacyTable([
            'employees_job_positions',
            'job_positions',
            'employee_job_positions',
        ]);
    }

    protected function legacyShelfEmployeesTable(): ?string
    {
        return $this->legacyShelfEmployeesTable ??= $this->firstExistingLegacyTable([
            'employees_employees',
            'employees',
            'employee_profiles',
        ]);
    }

    protected function legacyRowValue(object $row, array $columns): mixed
    {
        foreach ($columns as $column) {
            if (property_exists($row, $column)) {
                return $row->{$column};
            }
        }

        return null;
    }

    protected function firstExistingLegacyColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->legacyTableHasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function normalizeHelpdeskStatusName(?string $value): string
    {
        return match (strtolower(trim((string) $value))) {
            'open'        => 'Open',
            'in progress' => 'In Progress',
            'cancel',
            'cancelled'   => 'Cancelled',
            'closed'      => 'Closed',
            default       => $value ?: 'Open',
        };
    }

    protected function normalizeShelfTransferType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');

        return match ($normalizedValue) {
            'handover',
            'serah_terima',
            'berita_acara_serah_terima',
            'ba',
            'bast' => \Cesa\Shelf\Models\AssetTransfer::TYPE_HANDOVER,
            'reassignment',
            'pengalihan_barang',
            'berita_acara_pengalihan_barang',
            'bapab' => \Cesa\Shelf\Models\AssetTransfer::TYPE_REASSIGNMENT,
            'return',
            'pengembalian_barang',
            'berita_acara_pengembalian_barang',
            'bapeb' => \Cesa\Shelf\Models\AssetTransfer::TYPE_RETURN,
            default => null,
        };
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function normalizeLegacyStoragePath(?string $path): ?string
    {
        $normalizedPath = $this->nullableString($path);

        if ($normalizedPath === null) {
            return null;
        }

        return ltrim((string) Str::of($normalizedPath)
            ->replaceStart('storage/app/public/', '')
            ->replaceStart('/storage/app/public/', '')
            ->replaceStart('public/', '')
            ->replaceStart('/public/', '')
            ->replaceStart('storage/', '')
            ->replaceStart('/storage/', ''), '/');
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
