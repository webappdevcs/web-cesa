<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferApprovalWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'form_transfer_approval_workflows';

    protected $fillable = [
        'form_transfer_id',
        'division_id',
        'name',
        'code',
        'description',
        'steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps'     => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getStepCountAttribute(): int
    {
        return count($this->steps ?? []);
    }

    public function getStepSummaryAttribute(): string
    {
        return collect($this->steps ?? [])
            ->map(fn (array $step, int $index): string => $step['label'] ?? 'Step '.($index + 1))
            ->implode(' → ');
    }

    public function formTransfer(): BelongsTo
    {
        return $this->belongsTo(FormTransfer::class)->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TransferDivision::class, 'division_id')->withTrashed();
    }

    protected static function newFactory(): Factory
    {
        return TransferApprovalWorkflowFactory::new();
    }
}
