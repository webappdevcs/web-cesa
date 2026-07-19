<?php

namespace Cesa\Kepegawaian\Models;

use Cesa\Kepegawaian\Database\Factories\EmployeeSyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

class EmployeeSyncRun extends Model
{
    use HasFactory;

    protected $table = 'employees_sync_runs';

    protected $fillable = [
        'source_system',
        'source_instance',
        'mode',
        'status',
        'file_name',
        'file_checksum',
        'total_records',
        'matched_count',
        'linked_count',
        'created_count',
        'would_link_count',
        'would_create_count',
        'conflict_count',
        'invalid_count',
        'error_message',
        'initiated_by',
        'started_at',
        'completed_at',
    ];

    protected $hidden = [
        'error_message',
    ];

    protected $casts = [
        'error_message' => 'encrypted:string',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function sourceRecords(): HasMany
    {
        return $this->hasMany(EmployeeSourceRecord::class, 'sync_run_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            $run->uuid ??= (string) Str::orderedUuid();
        });
    }

    protected static function newFactory(): EmployeeSyncRunFactory
    {
        return EmployeeSyncRunFactory::new();
    }
}
