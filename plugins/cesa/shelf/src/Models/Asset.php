<?php

namespace Cesa\Shelf\Models;

use Carbon\Carbon;
use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Enums\NbhStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Support\Models\Company;

class Asset extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    protected $fillable = [
        'purchase_date',
        'company_id',
        'name',
        'image',
        'category_id',
        'brand_id',
        'type',
        'serial_number',
        'imei1',
        'imei2',
        'item_price',
        'asset_location_id',
        'condition_status',
        'nbh_status',
        'nbh_reported_at',
        'audit_document_path',
        'nbh_document_path',
        'nbh_notes',
        'nbh_responsible_user_id',
        'qty',
        'is_available',
        'recipient_id',
        'recipient_company_id',
    ];

    protected $casts = [
        'is_available'     => 'boolean',
        'condition_status' => AssetCondition::class,
        'nbh_status'       => NbhStatus::class,
        'nbh_reported_at'  => 'date',
    ];

    public function attributes(): HasMany
    {
        return $this->hasManyIncludingTrashed(AssetAttribute::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Company::class, 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Brand::class);
    }

    public function assetLocation(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(AssetLocation::class);
    }

    public function assetTransfers(): HasMany
    {
        return $this->hasManyIncludingTrashed(AssetTransfer::class);
    }

    public function assetTransferDetails(): HasMany
    {
        return $this->hasManyIncludingTrashed(AssetTransferDetail::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class, 'recipient_id');
    }

    public function recipientCompany(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Company::class, 'recipient_company_id');
    }

    public function companyDocumentSetting(): HasOne
    {
        return $this->hasOneIncludingTrashed(CompanyDocumentSetting::class, 'company_id', 'company_id');
    }

    public function nbhResponsible(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class, 'nbh_responsible_user_id');
    }

    public function getResourceUsersAttribute(): EloquentCollection
    {
        $users = new EloquentCollection;

        foreach ([$this->creator, $this->recipient, $this->nbhResponsible] as $relatedUser) {
            if ($relatedUser && ! $users->contains('id', $relatedUser->id)) {
                $users->push($relatedUser);
            }
        }

        return $users;
    }

    public function scopeApplyPermissionScope(Builder $query): Builder
    {
        $user = filament()->auth()->user();

        if (! $user) {
            return $query;
        }

        $userIds = bouncer()->getAuthorizedUserIds();

        if (empty($userIds)) {
            return $query;
        }

        return $query->where(function (Builder $scopedQuery) use ($userIds): void {
            $scopedQuery->whereIn('creator_id', $userIds)
                ->orWhereIn('recipient_id', $userIds)
                ->orWhereIn('nbh_responsible_user_id', $userIds);
        });
    }

    private function formatDiff(int $value, string $unit): string
    {
        return $value.' '.$unit;
    }

    public function getItemAgeAttribute(): ?string
    {
        $purchaseDate = $this->attributes['purchase_date'] ?? null;

        if (blank($purchaseDate)) {
            return null;
        }

        $purchaseDate = Carbon::parse($purchaseDate);
        $now = Carbon::now();

        $diff = $purchaseDate->diff($now);

        if ($diff->y > 0 && $diff->m > 0) {
            return $diff->y.' tahun '.$diff->m.' bulan';
        }

        if ($diff->y > 0) {
            return $this->formatDiff($diff->y, 'tahun');
        }

        if ($diff->m > 0) {
            return $this->formatDiff($diff->m, 'bulan');
        }

        return $this->formatDiff($diff->d, 'hari');
    }

    public function scopeSortByItemAge(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderByRaw('DATEDIFF(NOW(), purchase_date) '.$direction);
    }

    public function getIsAvailableAttribute(mixed $value): bool
    {
        if ($this->condition_status instanceof AssetCondition) {
            return $this->condition_status === AssetCondition::Available;
        }

        return (bool) $value;
    }

    public function setIsAvailableAttribute($value): void
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolValue === null) {
            $this->attributes['is_available'] = $value;

            return;
        }

        $this->attributes['is_available'] = $boolValue;
        $this->attributes['condition_status'] = $boolValue
            ? AssetCondition::Available->value
            : AssetCondition::Transferred->value;
    }

    public function getConditionStatusLabelAttribute(): string
    {
        return $this->condition_status instanceof AssetCondition
            ? $this->condition_status->label()
            : 'Tidak Diketahui';
    }

    public function getConditionStatusColorAttribute(): string
    {
        return $this->condition_status instanceof AssetCondition
            ? $this->condition_status->color()
            : 'secondary';
    }

    public function setConditionStatusAttribute($value): void
    {
        if ($value instanceof AssetCondition) {
            $enum = $value;
        } else {
            $enum = AssetCondition::tryFrom((string) $value) ?? AssetCondition::Available;
        }

        $this->attributes['condition_status'] = $enum->value;
        $this->attributes['is_available'] = $enum === AssetCondition::Available;

        if (in_array($enum, [AssetCondition::Lost, AssetCondition::Damaged], true)) {
            if (($this->attributes['nbh_status'] ?? null) === NbhStatus::None->value || ! isset($this->attributes['nbh_status'])) {
                $this->setNbhStatusAttribute(NbhStatus::Pending);
            }
        } else {
            $this->setNbhStatusAttribute(NbhStatus::None);
        }
    }

    public function getNbhStatusLabelAttribute(): string
    {
        return $this->nbh_status instanceof NbhStatus
            ? $this->nbh_status->label()
            : NbhStatus::None->label();
    }

    public function getNbhStatusColorAttribute(): string
    {
        return $this->nbh_status instanceof NbhStatus
            ? $this->nbh_status->color()
            : NbhStatus::None->color();
    }

    public function setNbhStatusAttribute($value): void
    {
        if ($value instanceof NbhStatus) {
            $enum = $value;
        } else {
            $enum = NbhStatus::tryFrom((string) $value) ?? NbhStatus::None;
        }

        $this->attributes['nbh_status'] = $enum->value;

        if ($enum === NbhStatus::None) {
            $this->attributes['nbh_responsible_user_id'] = null;
            $this->attributes['nbh_reported_at'] = null;
            $this->attributes['audit_document_path'] = null;
            $this->attributes['nbh_document_path'] = null;
            $this->attributes['nbh_notes'] = null;
        }
    }

    protected ?bool $cachedValidRecipientResult = null;

    protected ?AssetTransferDetail $cachedLatestTransferDetail = null;

    protected ?AssetTransfer $cachedLatestTransfer = null;

    public function checkValidRecipient(): bool
    {
        if ($this->cachedValidRecipientResult !== null) {
            return $this->cachedValidRecipientResult;
        }

        $this->cachedValidRecipientResult = $this->performValidRecipientCheck();

        return $this->cachedValidRecipientResult;
    }

    protected function performValidRecipientCheck(): bool
    {
        // Use the relationship if already loaded, otherwise query
        $latestTransferDetail = $this->relationLoaded('latestTransferDetail')
            ? $this->latestTransferDetail
            : $this->cachedLatestTransferDetail ??= AssetTransferDetail::where('asset_id', $this->id)
                ->latest()
                ->first();

        if (! $latestTransferDetail) {
            return true;
        }

        // Use the relationship if already loaded
        $latestTransfer = $this->relationLoaded('latestTransferDetail.assetTransfer')
            ? $latestTransferDetail->assetTransfer
            : $this->cachedLatestTransfer ??= AssetTransfer::withTrashed()->find($latestTransferDetail->asset_transfer_id);

        if (! $latestTransfer) {
            return true;
        }

        if ($this->recipient_id != $latestTransfer->to_user_id) {
            return false;
        }

        $recipient = $this->recipient ?? User::withTrashed()->find($this->recipient_id);

        if (! $recipient) {
            return false;
        }

        if (in_array($this->condition_status, [AssetCondition::Lost, AssetCondition::Damaged], true)) {
            if ($this->nbh_status === NbhStatus::None) {
                return false;
            }

            if ($this->nbh_status === NbhStatus::Resolved) {
                return ! empty($this->audit_document_path)
                    && ! empty($this->nbh_responsible_user_id);
            }

            return true;
        }

        $latestTransferType = $latestTransfer->transfer_type;

        if ($latestTransferType === AssetTransfer::TYPE_RETURN && $this->condition_status !== AssetCondition::Available) {
            return false;
        }

        if (
            in_array($latestTransferType, [AssetTransfer::TYPE_HANDOVER, AssetTransfer::TYPE_REASSIGNMENT], true)
            && $this->condition_status !== AssetCondition::Transferred
        ) {
            return false;
        }

        if (! in_array($latestTransferType, [
            AssetTransfer::TYPE_HANDOVER,
            AssetTransfer::TYPE_REASSIGNMENT,
            AssetTransfer::TYPE_RETURN,
        ], true)) {
            return false;
        }

        return true;
    }

    public function latestTransferDetail(): HasOne
    {
        return $this->hasOneIncludingTrashed(AssetTransferDetail::class, 'asset_id')->latestOfMany();
    }

    public function vehicleChecksheets(): HasMany
    {
        return $this->hasManyIncludingTrashed(VehicleChecksheet::class, 'asset_id');
    }

    protected function managedFileAttributes(): array
    {
        return [
            'image' => [
                'directory' => 'shelf/assets/images',
            ],
            'audit_document_path' => [
                'directory' => 'shelf/assets/audit-documents',
            ],
            'nbh_document_path' => [
                'directory' => 'shelf/assets/nbh-documents',
            ],
        ];
    }
}
