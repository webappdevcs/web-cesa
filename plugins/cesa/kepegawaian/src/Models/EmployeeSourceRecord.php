<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Database\Factories\EmployeeSourceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class EmployeeSourceRecord extends Model
{
    use HasFactory;

    protected $table = 'employees_source_records';

    protected $fillable = [
        'sync_run_id',
        'row_number',
        'external_id',
        'external_id_hash',
        'employee_code',
        'employee_code_hash',
        'checksum',
        'payload',
        'status',
        'match_strategy',
        'employee_id',
    ];

    protected $hidden = [
        'external_id',
        'employee_code',
        'payload',
    ];

    protected $casts = [
        'external_id'  => 'encrypted',
        'employee_code'=> 'encrypted',
        'payload'      => 'encrypted:array',
    ];

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(EmployeeSyncRun::class, 'sync_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function conflict(): HasOne
    {
        return $this->hasOne(EmployeeSyncConflict::class, 'source_record_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            $record->external_id_hash = self::blindIndex($record->external_id);
            $record->employee_code_hash = self::blindIndex($record->employee_code);
        });
    }

    protected static function newFactory(): EmployeeSourceRecordFactory
    {
        return EmployeeSourceRecordFactory::new();
    }

    private static function blindIndex(?string $value): ?string
    {
        $value = Str::lower(Str::squish((string) $value));

        if ($value === '') {
            return null;
        }

        $key = (string) config('app.key');

        if ($key === '') {
            throw new LogicException('APP_KEY is required to create employee source blind indexes.');
        }

        return hash_hmac('sha256', $value, $key);
    }
}
