<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Database\Factories\EmployeeSourceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeSourceRecord extends Model
{
    use HasFactory;

    protected $table = 'employees_source_records';

    protected $fillable = [
        'sync_run_id',
        'row_number',
        'external_id',
        'employee_code',
        'checksum',
        'payload',
        'status',
        'match_strategy',
        'employee_id',
    ];

    protected $hidden = [
        'payload',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
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

    protected static function newFactory(): EmployeeSourceRecordFactory
    {
        return EmployeeSourceRecordFactory::new();
    }
}
