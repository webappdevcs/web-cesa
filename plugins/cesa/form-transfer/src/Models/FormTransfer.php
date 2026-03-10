<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\FormTransferFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class FormTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'uid_prefix',
        'uid_padding',
        'uid_sequence',
        'description',
        'is_active',
        'creator_id',
        'approver_mail_subject',
        'approver_mail_greeting',
        'approver_mail_action_text',
        'approver_mail_template',
        'requester_mail_subject',
        'requester_mail_greeting',
        'requester_mail_action_text',
        'requester_mail_template',
        'approver_whatsapp_template',
    ];

    public function hasCustomNotificationTemplates(): bool
    {
        return collect([
            $this->approver_mail_subject,
            $this->approver_mail_greeting,
            $this->approver_mail_action_text,
            $this->approver_mail_template,
            $this->requester_mail_subject,
            $this->requester_mail_greeting,
            $this->requester_mail_action_text,
            $this->requester_mail_template,
            $this->approver_whatsapp_template,
        ])->contains(fn (?string $value): bool => filled($value));
    }

    protected function casts(): array
    {
        return [
            'uid_padding'  => 'integer',
            'uid_sequence' => 'integer',
            'is_active'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FormTransfer $formTransfer): void {
            if (! $formTransfer->creator_id && Auth::check()) {
                $formTransfer->creator_id = Auth::id();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(TransferDivision::class)->withTrashed();
    }

    public function referenceNotes(): HasMany
    {
        return $this->hasMany(TransferReferenceNote::class)->withTrashed();
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(TransferApprovalWorkflow::class)->withTrashed();
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(TransferRequest::class)->withTrashed();
    }

    /**
     * Users who have specific access to this form transfer.
     */
    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'form_transfer_user_accesses', 'form_transfer_id', 'user_id')
            ->withTrashed()
            ->withTimestamps();
    }

    public function generateNextRequestUid(): string
    {
        $formTransfer = DB::transaction(function () {
            $locked = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new RuntimeException('Form transfer not found when generating UID.');
            }

            $locked->uid_sequence++;
            $locked->save();

            return $locked->fresh(['company']);
        });

        $this->uid_sequence = $formTransfer->uid_sequence;

        $sequence = str_pad(
            (string) $formTransfer->uid_sequence,
            $formTransfer->uid_padding,
            '0',
            STR_PAD_LEFT,
        );

        return sprintf('%s-%s', $formTransfer->uid_prefix, $sequence);
    }

    protected static function newFactory(): Factory
    {
        return FormTransferFactory::new();
    }
}
