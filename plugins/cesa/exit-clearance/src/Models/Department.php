<?php

namespace Cesa\ExitClearance\Models;

use Cesa\ExitClearance\Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exit_clearance_departments';

    protected $fillable = [
        'code',
        'name',
        'description',
        'head_of_department_id',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this department.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Security\Models\User::class, 'created_by')->withTrashed();
    }

    /**
     * Get the head of department.
     */
    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'head_of_department_id')->withTrashed();
    }

    /**
     * Get sub-departments under this department.
     */
    public function subDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'head_of_department_id')->withTrashed();
    }

    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(Approver::class, 'exit_clearance_department_approver', 'department_id', 'approver_id')
            ->withTrashed();
    }

    /**
     * Create a new factory instance for model.
     */
    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
