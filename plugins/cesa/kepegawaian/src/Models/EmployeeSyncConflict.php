<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Database\Factories\EmployeeSyncConflictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class EmployeeSyncConflict extends Model
{
    use HasFactory;

    protected $table = 'employees_sync_conflicts';

    protected $fillable = [
        'source_record_id',
        'type',
        'status',
        'details',
        'employee_id',
        'resolution',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'details'     => 'encrypted:array',
        'resolved_at' => 'datetime',
    ];

    public function sourceRecord(): BelongsTo
    {
        return $this->belongsTo(EmployeeSourceRecord::class, 'source_record_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    protected static function newFactory(): EmployeeSyncConflictFactory
    {
        return EmployeeSyncConflictFactory::new();
    }
}
