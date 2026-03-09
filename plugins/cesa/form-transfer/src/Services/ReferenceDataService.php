<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing and caching form transfer reference data.
 *
 * Centralizes access to banks, divisions, workflows, and reference notes
 * with automatic cache management and invalidation.
 */
class ReferenceDataService
{
    /**
     * Cache time-to-live configurations
     */
    private const CACHE_TTL_BANKS = 1800; // 30 minutes

    private const CACHE_TTL_DIVISIONS = 300; // 5 minutes

    private const CACHE_TTL_WORKFLOWS = 300; // 5 minutes

    private const CACHE_TTL_REFERENCE_NOTES = 300; // 5 minutes

    /**
     * Get cached bank options for form dropdown.
     *
     * @return array<int, string> Map of bank ID to display name
     */
    public function getBankOptions(): array
    {
        return Cache::remember(
            $this->getBanksCacheKey(),
            self::CACHE_TTL_BANKS,
            function (): array {
                try {
                    return TransferBank::active()
                        ->ordered()
                        ->get()
                        ->mapWithKeys(function ($bank): array {
                            return [$bank->getKey() => $bank->display_name];
                        })
                        ->all();
                } catch (\Exception $e) {
                    logger()->error('Error fetching bank options', [
                        'error' => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );
    }

    /**
     * Get cached division options for a specific form transfer.
     *
     * @return array<int, string> Map of division ID to name
     */
    public function getDivisionOptions(?int $formTransferId): array
    {
        if (! $formTransferId) {
            return [];
        }

        return Cache::remember(
            $this->getDivisionsCacheKey($formTransferId),
            self::CACHE_TTL_DIVISIONS,
            function () use ($formTransferId): array {
                try {
                    return TransferDivision::query()
                        ->where('form_transfer_id', $formTransferId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                } catch (\Exception $e) {
                    logger()->error('Error fetching division options', [
                        'form_transfer_id' => $formTransferId,
                        'error'            => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );
    }

    /**
     * Get cached reference note options for a specific form transfer.
     *
     * @return array<string, string> Map of label to label (for dropdown compatibility)
     */
    public function getReferenceNoteOptions(?int $formTransferId): array
    {
        if (! $formTransferId) {
            return [];
        }

        return Cache::remember(
            $this->getReferenceNotesCacheKey($formTransferId),
            self::CACHE_TTL_REFERENCE_NOTES,
            function () use ($formTransferId): array {
                try {
                    return TransferReferenceNote::query()
                        ->where('form_transfer_id', $formTransferId)
                        ->where('is_active', true)
                        ->orderBy('label')
                        ->pluck('label', 'label')
                        ->all();
                } catch (\Exception $e) {
                    logger()->error('Error fetching reference note options', [
                        'form_transfer_id' => $formTransferId,
                        'error'            => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );
    }

    /**
     * Get cached workflow options for a form transfer and optional division.
     *
     * @return array<int, string> Map of workflow ID to display name
     */
    public function getWorkflowOptions(?int $formTransferId, ?int $divisionId = null): array
    {
        if (! $formTransferId) {
            return [];
        }

        return Cache::remember(
            $this->getWorkflowsCacheKey($formTransferId, $divisionId),
            self::CACHE_TTL_WORKFLOWS,
            function () use ($formTransferId, $divisionId): array {
                try {
                    return TransferApprovalWorkflow::query()
                        ->where('form_transfer_id', $formTransferId)
                        ->where('is_active', true)
                        ->where(function ($query) use ($divisionId): void {
                            if ($divisionId) {
                                $query->whereNull('division_id')
                                    ->orWhere('division_id', $divisionId);

                                return;
                            }
                            $query->whereNull('division_id');
                        })
                        ->with('division')
                        ->get()
                        ->mapWithKeys(function ($workflow): array {
                            $stepCount = is_array($workflow->steps) ? count($workflow->steps) : 0;
                            $divisionName = $workflow->division?->name ?? 'General';
                            $displayName = "{$divisionName} ({$stepCount} steps)";

                            return [$workflow->id => $displayName];
                        })
                        ->all();
                } catch (\Exception $e) {
                    logger()->error('Error fetching workflow options', [
                        'form_transfer_id' => $formTransferId,
                        'division_id'      => $divisionId,
                        'error'            => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );
    }

    /**
     * Find a bank by ID.
     */
    public function findBank(?int $bankId): ?TransferBank
    {
        if (! $bankId) {
            return null;
        }

        try {
            return TransferBank::query()->withTrashed()->find($bankId);
        } catch (\Exception $e) {
            logger()->error('Error finding bank by ID', [
                'bank_id' => $bankId,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a division by ID.
     */
    public function findDivision(?int $divisionId): ?TransferDivision
    {
        if (! $divisionId) {
            return null;
        }

        try {
            return TransferDivision::query()->withTrashed()->find($divisionId);
        } catch (\Exception $e) {
            logger()->error('Error finding division by ID', [
                'division_id' => $divisionId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a reference note by ID.
     */
    public function findReferenceNote(?int $referenceNoteId): ?TransferReferenceNote
    {
        if (! $referenceNoteId) {
            return null;
        }

        try {
            return TransferReferenceNote::query()->find($referenceNoteId);
        } catch (\Exception $e) {
            logger()->error('Error finding reference note by ID', [
                'reference_note_id' => $referenceNoteId,
                'error'             => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Invalidate bank cache.
     */
    public function invalidateBankCache(): void
    {
        Cache::forget($this->getBanksCacheKey());
        $this->flushTags(['form_transfer:reference_data', 'form_transfer:banks']);
    }

    /**
     * Invalidate division cache for a specific form transfer.
     */
    public function invalidateDivisionCache(int $formTransferId): void
    {
        Cache::forget($this->getDivisionsCacheKey($formTransferId));
        $this->flushTags(['form_transfer:reference_data', "form_transfer:divisions:{$formTransferId}"]);
    }

    /**
     * Invalidate reference note cache for a specific form transfer.
     */
    public function invalidateReferenceNoteCache(int $formTransferId): void
    {
        Cache::forget($this->getReferenceNotesCacheKey($formTransferId));
        $this->flushTags(['form_transfer:reference_data', "form_transfer:reference_notes:{$formTransferId}"]);
    }

    /**
     * Invalidate workflow cache for a specific form transfer.
     */
    public function invalidateWorkflowCache(int $formTransferId, ?int $divisionId = null): void
    {
        if ($divisionId) {
            Cache::forget($this->getWorkflowsCacheKey($formTransferId, $divisionId));
        }
        Cache::forget($this->getWorkflowsCacheKey($formTransferId, null));
        $this->flushTags(['form_transfer:reference_data', "form_transfer:workflows:{$formTransferId}"]);
    }

    /**
     * Invalidate all reference data cache.
     */
    public function invalidateAllCache(): void
    {
        if ($this->cacheSupportsTags()) {
            Cache::tags(['form_transfer:reference_data'])->flush();

            return;
        }

        Cache::flush();
    }

    /**
     * Get cache key for banks.
     */
    private function getBanksCacheKey(): string
    {
        return 'form_transfer:banks:active';
    }

    /**
     * Get cache key for divisions.
     */
    private function getDivisionsCacheKey(int $formTransferId): string
    {
        return "form_transfer:divisions:{$formTransferId}:active";
    }

    /**
     * Get cache key for reference notes.
     */
    private function getReferenceNotesCacheKey(int $formTransferId): string
    {
        return "form_transfer:reference_notes:{$formTransferId}:active";
    }

    /**
     * Get cache key for workflows.
     */
    private function getWorkflowsCacheKey(int $formTransferId, ?int $divisionId): string
    {
        $divisionPart = $divisionId ? ":{$divisionId}" : ':general';

        return "form_transfer:workflows:{$formTransferId}{$divisionPart}:active";
    }

    private function cacheSupportsTags(): bool
    {
        $store = Cache::getStore();

        return method_exists($store, 'tags');
    }

    private function flushTags(array $tags): void
    {
        if (! $this->cacheSupportsTags()) {
            return;
        }

        Cache::tags($tags)->flush();
    }
}
