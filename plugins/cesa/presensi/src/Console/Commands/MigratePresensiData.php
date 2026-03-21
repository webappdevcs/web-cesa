<?php

namespace Cesa\Presensi\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Security\Models\User as SecurityUser;

class MigratePresensiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:migrate-data
                            {--host= : Legacy DB Host}
                            {--port= : Legacy DB Port}
                            {--database= : Legacy DB Name}
                            {--username= : Legacy DB Username}
                            {--password= : Legacy DB Password}
                            {--truncate : Truncate target Presensi tables before migrating}
                            {--chunk=1000 : Chunk size for bulk migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from Legacy Presensi App to CESA Presensi Plugin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Data Migration...');

        try {
            $this->setupLegacyConnection();
            $this->verifyLegacyConnection();

            $this->info('Connected to Legacy Database.');

            if ($this->shouldTruncate()) {
                $this->warn('Truncate mode is enabled. Target Presensi tables will be emptied before migration.');
            } else {
                $this->info('Upsert mode is enabled. Existing rows will be updated by primary key.');
            }

            $this->migrateOffices();
            $this->migrateShifts();

            $userMap = $this->mapUsers();
            $this->migrateUserImages($userMap);

            $this->migrateSchedules($userMap);
            $this->migrateAttendances($userMap);
            $this->migrateLeaves($userMap);
            $this->migrateOvertimes($userMap);

            $this->info('Migration Completed Successfully!');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Migration failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function setupLegacyConnection(): void
    {
        $config = [
            'driver'    => 'mysql',
            'host'      => $this->option('host') ?? env('LEGACY_DB_HOST', '127.0.0.1'),
            'port'      => $this->option('port') ?? env('LEGACY_DB_PORT', '3306'),
            'database'  => $this->option('database') ?? env('LEGACY_DB_DATABASE', 'legacy_presensi'),
            'username'  => $this->option('username') ?? env('LEGACY_DB_USERNAME', 'root'),
            'password'  => $this->option('password') ?? env('LEGACY_DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ];

        config(['database.connections.legacy_presensi' => $config]);

        DB::purge('legacy_presensi');
        DB::reconnect('legacy_presensi');
    }

    protected function verifyLegacyConnection(): void
    {
        DB::connection('legacy_presensi')->getPdo();
    }

    protected function chunkSize(): int
    {
        $size = (int) $this->option('chunk');

        return $size > 0 ? $size : 1000;
    }

    protected function shouldTruncate(): bool
    {
        return (bool) $this->option('truncate');
    }

    protected function truncateTableIfRequested(string $table): void
    {
        if (! $this->shouldTruncate()) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table($table)->truncate();
        Schema::enableForeignKeyConstraints();
    }

    protected function migrateOffices(): void
    {
        $this->info('Migrating Offices...');

        $this->truncateTableIfRequested('presensi_offices');

        $query = DB::connection('legacy_presensi')->table('offices');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($bar) {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'id'         => $row->id,
                        'name'       => $row->name,
                        'latitude'   => $row->latitude,
                        'longitude'  => $row->longitude,
                        'radius'     => $row->radius,
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_offices')->upsert(
                        $payload,
                        ['id'],
                        ['name', 'latitude', 'longitude', 'radius', 'deleted_at', 'created_at', 'updated_at']
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }

    protected function migrateShifts(): void
    {
        $this->info('Migrating Shifts...');

        $this->truncateTableIfRequested('presensi_shifts');

        $query = DB::connection('legacy_presensi')->table('shifts');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($bar) {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'id'         => $row->id,
                        'name'       => $row->name,
                        'start_time' => $row->start_time,
                        'end_time'   => $row->end_time,
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_shifts')->upsert(
                        $payload,
                        ['id'],
                        ['name', 'start_time', 'end_time', 'deleted_at', 'created_at', 'updated_at']
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }

    protected function mapUsers(): array
    {
        $this->info('Mapping Users...');

        $legacyUsers = DB::connection('legacy_presensi')->table('users')->select('id', 'email')->get();
        $targetUsersByEmail = User::query()
            ->select('id', 'email')
            ->get()
            ->keyBy(fn (User $user) => strtolower((string) $user->email));

        $mapping = []; // [legacy_id => new_id]
        $missing = [];

        foreach ($legacyUsers as $legacyUser) {
            $email = strtolower((string) $legacyUser->email);
            $targetUser = $targetUsersByEmail->get($email);

            if ($targetUser) {
                $mapping[$legacyUser->id] = $targetUser->id;
            } else {
                $missing[] = "Legacy ID: {$legacyUser->id} ({$legacyUser->email})";
            }
        }

        $this->info('Mapped '.count($mapping).' users.');

        if ($missing !== []) {
            $this->warn('Missing users ('.count($missing).') detected.');

            foreach (array_slice($missing, 0, 10) as $line) {
                $this->line($line);
            }

            if (count($missing) > 10) {
                $this->line('... and '.(count($missing) - 10).' more.');
            }

            $reportPath = storage_path('logs/presensi-migration-missing-users.log');
            file_put_contents($reportPath, implode(PHP_EOL, $missing).PHP_EOL, FILE_APPEND);
            $this->warn('Missing users report saved to: '.$reportPath);
        }

        return $mapping;
    }

    protected function migrateUserImages(array $userMap): void
    {
        $this->info('Migrating User Images...');

        if ($userMap === []) {
            $this->warn('No mapped users found. Skipping user image migration.');

            return;
        }

        $query = DB::connection('legacy_presensi')
            ->table('users')
            ->select('id', 'image')
            ->whereNotNull('image');

        $total = $query->count();

        if ($total === 0) {
            $this->line('No legacy user images found.');

            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($userMap, $bar) {
                foreach ($rows as $row) {
                    $newUserId = $userMap[$row->id] ?? null;

                    if ($newUserId) {
                        $user = SecurityUser::query()->find($newUserId);

                        if (! $user) {
                            $bar->advance();

                            continue;
                        }

                        if (! $user->partner_id) {
                            $user->save();
                            $user->refresh();
                        }

                        $user->partner?->forceFill([
                            'avatar' => $row->image,
                        ])->save();
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    protected function migrateSchedules(array $userMap): void
    {
        $this->info('Migrating Schedules...');

        $this->truncateTableIfRequested('presensi_schedules');

        $query = DB::connection('legacy_presensi')->table('schedules');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($userMap, $bar) {
                $payload = [];

                foreach ($rows as $row) {
                    if (! isset($userMap[$row->user_id])) {
                        continue;
                    }

                    $payload[] = [
                        'id'         => $row->id,
                        'user_id'    => $userMap[$row->user_id],
                        'shift_id'   => $row->shift_id,
                        'office_id'  => $row->office_id,
                        'is_wfa'     => $row->is_wfa ?? false,
                        'is_banned'  => $row->is_banned ?? false,
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_schedules')->upsert(
                        $payload,
                        ['id'],
                        ['user_id', 'shift_id', 'office_id', 'is_wfa', 'is_banned', 'deleted_at', 'created_at', 'updated_at']
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }

    protected function migrateAttendances(array $userMap): void
    {
        $this->info('Migrating Attendances...');

        $this->truncateTableIfRequested('presensi_attendances');

        $query = DB::connection('legacy_presensi')->table('attendances');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($userMap, $bar) {
                $payload = [];

                foreach ($rows as $row) {
                    if (! isset($userMap[$row->user_id])) {
                        continue;
                    }

                    $payload[] = [
                        'id'                  => $row->id,
                        'user_id'             => $userMap[$row->user_id],
                        'schedule_latitude'   => $row->schedule_latitude,
                        'schedule_longitude'  => $row->schedule_longitude,
                        'schedule_start_time' => $row->schedule_start_time,
                        'schedule_end_time'   => $row->schedule_end_time,
                        'start_latitude'      => $row->start_latitude,
                        'start_longitude'     => $row->start_longitude,
                        'end_latitude'        => $row->end_latitude,
                        'end_longitude'       => $row->end_longitude,
                        'start_time'          => $row->start_time,
                        'end_time'            => $row->end_time,
                        'is_leave'            => $row->is_leave ?? false,
                        'start_photo_path'    => $row->start_photo_path,
                        'end_photo_path'      => $row->end_photo_path,
                        'deleted_at'          => $row->deleted_at,
                        'created_at'          => $row->created_at,
                        'updated_at'          => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_attendances')->upsert(
                        $payload,
                        ['id'],
                        [
                            'user_id',
                            'schedule_latitude',
                            'schedule_longitude',
                            'schedule_start_time',
                            'schedule_end_time',
                            'start_latitude',
                            'start_longitude',
                            'end_latitude',
                            'end_longitude',
                            'start_time',
                            'end_time',
                            'is_leave',
                            'start_photo_path',
                            'end_photo_path',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                        ]
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }

    protected function migrateLeaves(array $userMap): void
    {
        $this->info('Migrating Leaves...');

        $this->truncateTableIfRequested('presensi_leaves');

        $query = DB::connection('legacy_presensi')->table('leaves');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($userMap, $bar) {
                $payload = [];

                foreach ($rows as $row) {
                    if (! isset($userMap[$row->user_id])) {
                        continue;
                    }

                    $payload[] = [
                        'id'         => $row->id,
                        'user_id'    => $userMap[$row->user_id],
                        'type'       => $row->type ?? 'Izin',
                        'start_date' => $row->start_date,
                        'end_date'   => $row->end_date,
                        'reason'     => $row->reason,
                        'status'     => $row->status ?? 'pending',
                        'note'       => $row->note,
                        'attachment' => $row->attachment,
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_leaves')->upsert(
                        $payload,
                        ['id'],
                        ['user_id', 'type', 'start_date', 'end_date', 'reason', 'status', 'note', 'attachment', 'deleted_at', 'created_at', 'updated_at']
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }

    protected function migrateOvertimes(array $userMap): void
    {
        $this->info('Migrating Overtimes...');

        $this->truncateTableIfRequested('presensi_overtimes');

        $query = DB::connection('legacy_presensi')->table('overtimes');
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->orderBy('id')
            ->chunk($this->chunkSize(), function ($rows) use ($userMap, $bar) {
                $payload = [];

                foreach ($rows as $row) {
                    if (! isset($userMap[$row->user_id])) {
                        continue;
                    }

                    $payload[] = [
                        'id'         => $row->id,
                        'user_id'    => $userMap[$row->user_id],
                        'date'       => $row->date,
                        'start_time' => $row->start_time,
                        'end_time'   => $row->end_time,
                        'reason'     => $row->reason,
                        'status'     => $row->status ?? 'pending',
                        'note'       => $row->note,
                        'attachment' => $row->attachment,
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('presensi_overtimes')->upsert(
                        $payload,
                        ['id'],
                        ['user_id', 'date', 'start_time', 'end_time', 'reason', 'status', 'note', 'attachment', 'deleted_at', 'created_at', 'updated_at']
                    );
                }

                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
    }
}
