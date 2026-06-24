<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\FormTransferFactory;
use Illuminate\Database\Eloquent\Builder;
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
use Webkul\Security\Traits\HasNullableCreator;
use Webkul\Support\Models\Company;

class FormTransfer extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    public const PUBLIC_ENTRY_TYPE_INTERNAL = 'internal';

    public const PUBLIC_ENTRY_TYPE_EXTERNAL = 'external';

    public const PUBLIC_INDEX_TRANSFER_REQUESTS = FormTransferPublicCategory::SLUG_TRANSFER_REQUESTS;

    public const PUBLIC_INDEX_AFFILIATES = FormTransferPublicCategory::SLUG_AFFILIATES;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'uid_prefix',
        'uid_padding',
        'uid_sequence',
        'description',
        'public_entry_type',
        'public_external_url',
        'apps_script_web_app_url',
        'public_badge_label',
        'public_sort_order',
        'show_on_transfer_request_index',
        'show_on_affiliate_index',
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
            'uid_padding'                    => 'integer',
            'uid_sequence'                   => 'integer',
            'public_sort_order'              => 'integer',
            'show_on_transfer_request_index' => 'boolean',
            'show_on_affiliate_index'        => 'boolean',
            'is_active'                      => 'boolean',
        ];
    }

    public function scopeInternalEntry(Builder $query): Builder
    {
        return $query
            ->where('public_entry_type', self::PUBLIC_ENTRY_TYPE_INTERNAL)
            ->whereNull($query->qualifyColumn('deleted_at'));
    }

    public function usesExternalPublicEntry(): bool
    {
        return $this->public_entry_type === self::PUBLIC_ENTRY_TYPE_EXTERNAL && filled($this->public_external_url);
    }

    /**
     * @return array<int, string>
     */
    public static function reservedPublicIndexSlugs(): array
    {
        return FormTransferPublicCategory::reservedSlugs();
    }

    public static function normalizePublicIndexSlug(mixed $slug): string
    {
        return FormTransferPublicCategory::normalizeSlug($slug);
    }

    /**
     * @return array<int, string>
     */
    public static function normalizePublicIndexSlugs(mixed $slugs): array
    {
        if (is_string($slugs)) {
            $slugs = preg_split('/[\s,]+/', $slugs) ?: [];
        }

        if (! is_array($slugs)) {
            return [];
        }

        return collect($slugs)
            ->map(fn (mixed $slug): string => self::normalizePublicIndexSlug($slug))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function isAllowedPublicIndexSlug(mixed $slug): bool
    {
        return FormTransferPublicCategory::isAllowedSlug($slug);
    }

    public static function isBuiltInPublicIndexSlug(string $slug): bool
    {
        return FormTransferPublicCategory::isBuiltInSlug($slug);
    }

    public static function hasPublicIndexSlug(string $slug): bool
    {
        return FormTransferPublicCategory::activeSlugExists($slug);
    }

    public function scopeShownOnPublicIndex(Builder $query, string $slug): Builder
    {
        $slug = self::normalizePublicIndexSlug($slug);

        return $query->where(function (Builder $query) use ($slug): void {
            $query->whereHas('publicCategories', function (Builder $query) use ($slug): void {
                $query
                    ->where('slug', $slug)
                    ->where('is_active', true);
            });

            if ($slug === self::PUBLIC_INDEX_TRANSFER_REQUESTS) {
                $query->orWhere('show_on_transfer_request_index', true);
            }

            if ($slug === self::PUBLIC_INDEX_AFFILIATES) {
                $query->orWhere('show_on_affiliate_index', true);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function getPublicIndexSlugs(): array
    {
        if ($this->relationLoaded('publicCategories')) {
            $explicitSlugs = $this->publicCategories
                ->where('is_active', true)
                ->pluck('slug')
                ->all();

            if ($explicitSlugs !== []) {
                return self::normalizePublicIndexSlugs($explicitSlugs);
            }
        }

        $explicitSlugs = $this->publicCategories()
            ->where('is_active', true)
            ->pluck('slug')
            ->all();

        if ($explicitSlugs !== []) {
            return $explicitSlugs;
        }

        return $this->legacyPublicIndexSlugsFromFlags();
    }

    public function getPublicDestinationUrlAttribute(): string
    {
        if ($this->usesExternalPublicEntry()) {
            return (string) $this->public_external_url;
        }

        return route('form-transfer.public.form', $this->code ?: $this->getKey());
    }

    protected static function booted(): void
    {
        static::creating(function (FormTransfer $formTransfer): void {
            if (! $formTransfer->creator_id && Auth::check()) {
                $formTransfer->creator_id = Auth::id();
            }

            if ($formTransfer->public_sort_order === null) {
                $maxSortOrder = self::query()
                    ->withTrashed()
                    ->max('public_sort_order');

                $formTransfer->public_sort_order = is_numeric($maxSortOrder)
                    ? ((int) $maxSortOrder + 1)
                    : 1;
            }
        });
    }

    /**
     * @return array<int, string>
     */
    protected function legacyPublicIndexSlugsFromFlags(): array
    {
        if (
            ! array_key_exists('show_on_transfer_request_index', $this->attributes)
            && ! array_key_exists('show_on_affiliate_index', $this->attributes)
        ) {
            return [self::PUBLIC_INDEX_TRANSFER_REQUESTS];
        }

        $slugs = [];

        if ((bool) $this->show_on_transfer_request_index) {
            $slugs[] = self::PUBLIC_INDEX_TRANSFER_REQUESTS;
        }

        if ((bool) $this->show_on_affiliate_index) {
            $slugs[] = self::PUBLIC_INDEX_AFFILIATES;
        }

        return self::normalizePublicIndexSlugs($slugs);
    }

    public function syncLegacyPublicIndexFlags(): void
    {
        $slugs = $this->getPublicIndexSlugs();

        $this->forceFill([
            'show_on_transfer_request_index' => in_array(self::PUBLIC_INDEX_TRANSFER_REQUESTS, $slugs, true),
            'show_on_affiliate_index'        => in_array(self::PUBLIC_INDEX_AFFILIATES, $slugs, true),
        ])->saveQuietly();
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

    public function publicCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            FormTransferPublicCategory::class,
            'form_transfer_public_category_assignments',
            'form_transfer_id',
            'form_transfer_public_category_id',
        )->withTimestamps();
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
