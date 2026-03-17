<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Support\Models\Company;

class Task extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    protected $fillable = [
        'code',
        'company_id',
        'user_id',
        'work_timestamp',
        'name',
        'description',
        'vendor_id',
        'cost',
        'location',
        'status',
        'attachment',
        'document_upload',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Company::class, 'company_id');
    }

    public function companyDocumentSetting(): HasOne
    {
        return $this->hasOneIncludingTrashed(CompanyDocumentSetting::class, 'company_id', 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Vendor::class);
    }

    public function getResourceUsersAttribute(): EloquentCollection
    {
        $users = new EloquentCollection;

        foreach ([$this->creator, $this->user] as $relatedUser) {
            if ($relatedUser && ! $users->contains('id', $relatedUser->id)) {
                $users->push($relatedUser);
            }
        }

        return $users;
    }

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            $task->code = static::generateTaskCode($task);
        });

        static::updating(function (self $task): void {
            if ($task->isDirty('company_id')) {
                $task->code = static::generateTaskCode($task);
            }
        });
    }

    protected static function generateTaskCode(self $task): string
    {
        $year = now()->year;
        $companyCode = strtoupper((string) Company::query()
            ->whereKey($task->company_id)
            ->value('name'));

        $lastTaskForYear = static::query()
            ->where('company_id', $task->company_id)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTaskForYear) {
            $lastOrder = (int) explode('/', $lastTaskForYear->code)[0];
            $nextOrder = $lastOrder + 1;
        } else {
            $nextOrder = 1;
        }

        return sprintf('%03d/BAP/%s/GA/%s', $nextOrder, $companyCode, $year);
    }

    protected function managedFileAttributes(): array
    {
        return [
            'attachment' => [
                'directory' => 'shelf/tasks/attachments',
                'multiple'  => true,
            ],
            'document_upload' => [
                'directory' => 'shelf/tasks/documents',
            ],
        ];
    }

    public function getAttachmentFilesAttribute(): array
    {
        $attachments = $this->attributes['attachment'] ?? null;

        if ($attachments === null || $attachments === '') {
            return [];
        }

        if (is_array($attachments)) {
            return array_values(array_filter(
                $attachments,
                fn (mixed $attachment): bool => is_string($attachment) && $attachment !== '',
            ));
        }

        $decoded = json_decode($attachments, true);

        if (is_array($decoded)) {
            return array_values(array_filter(
                $decoded,
                fn (mixed $attachment): bool => is_string($attachment) && $attachment !== '',
            ));
        }

        return [$attachments];
    }

    public function setAttachmentAttribute(array|string|null $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->attributes['attachment'] = null;

            return;
        }

        if (is_array($value)) {
            $attachments = array_values(array_filter(
                $value,
                fn (mixed $attachment): bool => is_string($attachment) && $attachment !== '',
            ));

            $this->attributes['attachment'] = $attachments === []
                ? null
                : json_encode($attachments, JSON_UNESCAPED_SLASHES);

            return;
        }

        $this->attributes['attachment'] = $value;
    }
}
