<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Support\Models\Company;

class AssetTransfer extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    protected ?string $ownerColumn = 'from_user_id';

    protected ?string $assignmentColumn = 'to_user_id';

    public const TYPE_HANDOVER = 'handover';

    public const TYPE_REASSIGNMENT = 'reassignment';

    public const TYPE_RETURN = 'return';

    public const STATUS_HANDOVER = 'BERITA ACARA SERAH TERIMA';

    public const STATUS_REASSIGNMENT = 'BERITA ACARA PENGALIHAN BARANG';

    public const STATUS_RETURN = 'BERITA ACARA PENGEMBALIAN BARANG';

    public const STATUS_UNKNOWN = 'Unknown Status';

    protected $fillable = [
        'company_id',
        'letter_number',
        'transfer_type',
        'from_user_id',
        'to_user_id',
        'document',
        'transfer_date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Company::class, 'company_id');
    }

    public function companyDocumentSetting(): HasOne
    {
        return $this->hasOneIncludingTrashed(CompanyDocumentSetting::class, 'company_id', 'company_id');
    }

    public function details(): HasMany
    {
        return $this->hasManyIncludingTrashed(AssetTransferDetail::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class, 'to_user_id');
    }

    public function getResourceUsersAttribute(): EloquentCollection
    {
        $users = new EloquentCollection;

        foreach ([$this->creator, $this->fromUser, $this->toUser] as $relatedUser) {
            if ($relatedUser && ! $users->contains('id', $relatedUser->id)) {
                $users->push($relatedUser);
            }
        }

        return $users;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_HANDOVER     => self::STATUS_HANDOVER,
            self::STATUS_REASSIGNMENT => self::STATUS_REASSIGNMENT,
            self::STATUS_RETURN       => self::STATUS_RETURN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function transferTypeOptions(): array
    {
        return [
            self::TYPE_HANDOVER     => self::STATUS_HANDOVER,
            self::TYPE_REASSIGNMENT => self::STATUS_REASSIGNMENT,
            self::TYPE_RETURN       => self::STATUS_RETURN,
        ];
    }

    public function scopeStatusLabel(Builder $query, ?string $status): Builder
    {
        return $query->forTransferType(self::transferTypeFromStatusLabel($status));
    }

    public function scopeForTransferType(Builder $query, ?string $transferType): Builder
    {
        if (! array_key_exists((string) $transferType, self::transferTypeOptions())) {
            return $query;
        }

        return $query->where('transfer_type', $transferType);
    }

    public static function transferTypeFromStatusLabel(?string $statusLabel): ?string
    {
        return array_search($statusLabel, self::transferTypeOptions(), true) ?: null;
    }

    public static function labelForTransferType(?string $transferType): string
    {
        return self::transferTypeOptions()[$transferType] ?? self::STATUS_UNKNOWN;
    }

    public static function inferTransferTypeFromUsers(?User $fromUser, ?User $toUser): ?string
    {
        if ($fromUser === null || $toUser === null) {
            return null;
        }

        $fromIsCustodian = self::isCustodianUser($fromUser);
        $toIsCustodian = self::isCustodianUser($toUser);

        return match (true) {
            $fromIsCustodian && ! $toIsCustodian   => self::TYPE_HANDOVER,
            ! $fromIsCustodian && $toIsCustodian   => self::TYPE_RETURN,
            ! $fromIsCustodian && ! $toIsCustodian => self::TYPE_REASSIGNMENT,
            default                                => null,
        };
    }

    public static function inferTransferTypeFromUserIds(?int $fromUserId, ?int $toUserId): ?string
    {
        if ($fromUserId === null || $toUserId === null) {
            return null;
        }

        return self::inferTransferTypeFromUsers(
            User::query()->withTrashed()->find($fromUserId),
            User::query()->withTrashed()->find($toUserId),
        );
    }

    public function getStatusAttribute(): string
    {
        if (filled($this->transfer_type)) {
            return self::labelForTransferType($this->transfer_type);
        }

        return self::STATUS_UNKNOWN;
    }

    protected function managedFileAttributes(): array
    {
        return [
            'document' => [
                'directory' => 'shelf/asset-transfers/documents',
            ],
        ];
    }

    private static function isCustodianUser(User $user): bool
    {
        $candidates = [
            $user->name,
            $user->jobTitle?->title,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = strtolower(trim(preg_replace('/\s+/u', ' ', $candidate) ?? ''));

            if (in_array($normalized, ['ga', 'general affair', 'general affairs'], true)) {
                return true;
            }
        }

        return false;
    }
}
