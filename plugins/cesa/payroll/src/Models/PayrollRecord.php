<?php

namespace Cesa\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

class PayrollRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payroll_records';

    protected $fillable = [
        'user_id',
        'payroll_period_id',
        'total_attendance_days',
        'total_overtime_hours',
        'total_late_minutes',
        'gross_salary',
        'total_penalties',
        'net_salary',
        'details',
    ];

    protected $casts = [
        'details'              => 'array',
        'total_overtime_hours' => 'decimal:2',
        'gross_salary'         => 'decimal:2',
        'total_penalties'      => 'decimal:2',
        'net_salary'           => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id')->withTrashed();
    }
}
