<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const string IDENTITY_INDEX = 'employee_source_records_identity_index';

    public function up(): void
    {
        $this->assertRequiredTablesExist();
        $this->encryptionKey();
        $this->addRunColumns();
        $this->addSourceRecordBlindIndexes();
        $this->addReviewedRunForeignKey();
        $this->dropLegacyPlaintextIdentityIndex();
        $this->widenEncryptedColumnsOnMySql();
        $this->encryptLegacyScalarValues();
        $this->ensureBlindIdentityIndex();
    }

    public function down(): void
    {
        // Irreversible security hardening: rolling back would restore plaintext PII.
    }

    private function assertRequiredTablesExist(): void
    {
        foreach (['employees_sync_runs', 'employees_source_records', 'employees_sync_conflicts'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Employee sync hardening requires the [{$table}] table.");
            }
        }
    }

    private function addRunColumns(): void
    {
        if (! Schema::hasColumn('employees_sync_runs', 'manifest_hash')) {
            Schema::table('employees_sync_runs', function (Blueprint $table): void {
                $table->char('manifest_hash', 64)->nullable()->after('file_checksum');
            });
        }

        if (! Schema::hasColumn('employees_sync_runs', 'reviewed_run_id')) {
            Schema::table('employees_sync_runs', function (Blueprint $table): void {
                $table->unsignedBigInteger('reviewed_run_id')->nullable()->after('manifest_hash');
            });
        }

        if (! Schema::hasColumn('employees_sync_runs', 'channel')) {
            Schema::table('employees_sync_runs', function (Blueprint $table): void {
                $table->string('channel', 32)->default('console')->after('reviewed_run_id');
            });
        }

        if (! Schema::hasColumn('employees_sync_runs', 'commit_reason')) {
            Schema::table('employees_sync_runs', function (Blueprint $table): void {
                $table->longText('commit_reason')->nullable()->after('channel');
            });
        }
    }

    private function addSourceRecordBlindIndexes(): void
    {
        if (! Schema::hasColumn('employees_source_records', 'external_id_hash')) {
            Schema::table('employees_source_records', function (Blueprint $table): void {
                $table->char('external_id_hash', 64)->nullable()->after('external_id');
            });
        }

        if (! Schema::hasColumn('employees_source_records', 'employee_code_hash')) {
            Schema::table('employees_source_records', function (Blueprint $table): void {
                $table->char('employee_code_hash', 64)->nullable()->after('employee_code');
            });
        }
    }

    private function addReviewedRunForeignKey(): void
    {
        $hasForeignKey = collect(Schema::getForeignKeys('employees_sync_runs'))->contains(
            fn (array $foreignKey): bool => ($foreignKey['columns'] ?? null) === ['reviewed_run_id']
                && ($foreignKey['foreign_table'] ?? null) === 'employees_sync_runs'
        );

        if ($hasForeignKey) {
            return;
        }

        Schema::table('employees_sync_runs', function (Blueprint $table): void {
            $table->foreign('reviewed_run_id')
                ->references('id')
                ->on('employees_sync_runs')
                ->restrictOnDelete();
        });
    }

    private function dropLegacyPlaintextIdentityIndex(): void
    {
        $identityIndex = $this->identityIndex();

        if (
            $identityIndex === null
            || ($identityIndex['columns'] ?? null) === ['external_id_hash', 'employee_code_hash']
        ) {
            return;
        }

        Schema::table('employees_source_records', function (Blueprint $table): void {
            $table->dropIndex(self::IDENTITY_INDEX);
        });
    }

    private function widenEncryptedColumnsOnMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->widenColumn('employees_sync_runs', 'error_message', true);
        $this->widenColumn('employees_source_records', 'external_id', true);
        $this->widenColumn('employees_source_records', 'employee_code', true);
        $this->widenColumn('employees_source_records', 'payload', false);
        $this->widenColumn('employees_sync_conflicts', 'details', false);
        $this->widenColumn('employees_sync_conflicts', 'resolution_notes', true);
    }

    private function widenColumn(string $tableName, string $columnName, bool $nullable): void
    {
        if (Str::lower(Schema::getColumnType($tableName, $columnName)) === 'longtext') {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $nullable): void {
            $column = $table->longText($columnName);

            if ($nullable) {
                $column->nullable();
            }

            $column->change();
        });
    }

    private function encryptLegacyScalarValues(): void
    {
        DB::table('employees_sync_runs')
            ->select(['id', 'error_message', 'commit_reason'])
            ->orderBy('id')
            ->chunkById(200, function ($runs): void {
                foreach ($runs as $run) {
                    DB::table('employees_sync_runs')
                        ->where('id', $run->id)
                        ->update([
                            'error_message' => $this->encryptedScalar($run->error_message)[0],
                            'commit_reason' => $this->encryptedScalar($run->commit_reason)[0],
                        ]);
                }
            });

        DB::table('employees_source_records')
            ->select(['id', 'external_id', 'employee_code'])
            ->orderBy('id')
            ->chunkById(200, function ($records): void {
                foreach ($records as $record) {
                    [$externalIdCiphertext, $externalId] = $this->encryptedScalar($record->external_id);
                    [$employeeCodeCiphertext, $employeeCode] = $this->encryptedScalar($record->employee_code);

                    DB::table('employees_source_records')
                        ->where('id', $record->id)
                        ->update([
                            'external_id'        => $externalIdCiphertext,
                            'external_id_hash'   => $this->blindIndex($externalId),
                            'employee_code'      => $employeeCodeCiphertext,
                            'employee_code_hash' => $this->blindIndex($employeeCode),
                        ]);
                }
            });

        DB::table('employees_sync_conflicts')
            ->select(['id', 'resolution_notes'])
            ->orderBy('id')
            ->chunkById(200, function ($conflicts): void {
                foreach ($conflicts as $conflict) {
                    DB::table('employees_sync_conflicts')
                        ->where('id', $conflict->id)
                        ->update([
                            'resolution_notes' => $this->encryptedScalar($conflict->resolution_notes)[0],
                        ]);
                }
            });
    }

    private function ensureBlindIdentityIndex(): void
    {
        $identityIndex = $this->identityIndex();

        if (($identityIndex['columns'] ?? null) === ['external_id_hash', 'employee_code_hash']) {
            return;
        }

        Schema::table('employees_source_records', function (Blueprint $table): void {
            $table->index(
                ['external_id_hash', 'employee_code_hash'],
                self::IDENTITY_INDEX,
            );
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function identityIndex(): ?array
    {
        return collect(Schema::getIndexes('employees_source_records'))
            ->first(fn (array $index): bool => ($index['name'] ?? null) === self::IDENTITY_INDEX);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function encryptedScalar(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        try {
            return [$value, Crypt::decryptString($value)];
        } catch (DecryptException) {
            return [Crypt::encryptString($value), $value];
        }
    }

    private function blindIndex(?string $value): ?string
    {
        $normalized = Str::lower(Str::squish((string) $value));

        if ($normalized === '') {
            return null;
        }

        return hash_hmac('sha256', $normalized, $this->encryptionKey());
    }

    private function encryptionKey(): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new LogicException('APP_KEY is required to harden employee sync staging data.');
        }

        return $key;
    }
};
