<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Support\Models\Company;

class CompanyDocumentSetting extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    protected $fillable = [
        'company_id',
        'format',
        'color',
        'letterhead_path',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Company::class);
    }

    public static function resolveFormat(?Company $company, ?self $profile = null): string
    {
        if (! $company) {
            return '';
        }

        $format = $profile?->format;

        if (filled($format)) {
            return (string) $format;
        }

        return (string) ($company->company_id ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name ?? ''), 0, 4)));
    }

    public static function resolveColor(?Company $company, ?self $profile = null): string
    {
        return (string) ($profile?->color ?: $company?->color ?: 'gray');
    }

    public static function resolveLetterheadPath(?Company $company, ?self $profile = null): ?string
    {
        return $profile?->letterhead_path ?: $company?->logo;
    }

    public static function resolveLetterheadAbsolutePath(?Company $company, ?self $profile = null): ?string
    {
        if ($profile?->letterhead_path) {
            return $profile->managedFileAbsolutePath('letterhead_path');
        }

        if (blank($company?->logo)) {
            return null;
        }

        return storage_path('app/public/'.$company->logo);
    }

    protected function managedFileAttributes(): array
    {
        return [
            'letterhead_path' => [
                'directory' => 'shelf/letterheads',
            ],
        ];
    }
}
