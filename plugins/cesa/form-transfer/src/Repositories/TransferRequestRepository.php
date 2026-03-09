<?php

namespace Cesa\FormTransfer\Repositories;

use Cesa\FormTransfer\Models\TransferRequest;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for TransferRequest model with query optimization.
 *
 * Centralizes database queries with eager loading to prevent N+1 queries.
 */
class TransferRequestRepository
{
    /**
     * Default relationships to eager load.
     */
    private const DEFAULT_RELATIONS = [
        'formTransfer:id,name,company_id,code',
        'division:id,name',
        'approvalWorkflow:id,name',
        'company:id,name',
    ];

    /**
     * Extended relationships for detailed views.
     */
    private const DETAILED_RELATIONS = [
        'formTransfer:id,name,company_id,code',
        'division:id,name',
        'bank:code,name,short_name',
        'approvalWorkflow:id,name',
        'company:id,name',
        'user:id,name,email',
        'creator:id,name,email',
    ];

    /**
     * Find a transfer request by ID with specified relationships.
     *
     * @param  array<string>|null  $relations  Relationships to eager load (uses defaults if null)
     */
    public function find(int $id, ?array $relations = null): ?TransferRequest
    {
        $relations = $relations ?? self::DEFAULT_RELATIONS;

        return TransferRequest::query()
            ->with($relations)
            ->find($id);
    }

    /**
     * Find a transfer request by ID with detailed relationships.
     */
    public function findWithDetails(int $id): ?TransferRequest
    {
        return $this->find($id, self::DETAILED_RELATIONS);
    }

    /**
     * Find a transfer request by approval task ID.
     *
     * Uses JSON querying for efficient task ID lookup in approvals array.
     */
    public function findByTaskId(string $taskId): ?TransferRequest
    {
        try {
            $connection = TransferRequest::query()->getConnection()->getDriverName();

            if ($connection === 'sqlite') {
                // SQLite fallback: Load all and filter in PHP
                return TransferRequest::query()
                    ->whereNotNull('approvals')
                    ->get()
                    ->first(function (TransferRequest $request) use ($taskId): bool {
                        return collect($request->approvals ?? [])
                            ->contains(fn (array $approval): bool => ($approval['task_id'] ?? null) === $taskId);
                    });
            }

            // MySQL/PostgreSQL: Use native JSON search
            return TransferRequest::query()
                ->with(self::DEFAULT_RELATIONS)
                ->whereRaw("JSON_SEARCH(approvals, 'one', ?, NULL, '$[*].task_id') IS NOT NULL", [$taskId])
                ->first();
        } catch (\Throwable $exception) {
            logger()->error('Error finding transfer request by task ID', [
                'task_id' => $taskId,
                'error'   => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a transfer request by status response ID.
     */
    public function findByStatusResponseId(string $statusResponseId): ?TransferRequest
    {
        try {
            return TransferRequest::query()
                ->with(self::DEFAULT_RELATIONS)
                ->where('status_response_id', $statusResponseId)
                ->first();
        } catch (\Throwable $exception) {
            logger()->error('Error finding transfer request by status response ID', [
                'status_response_id' => $statusResponseId,
                'error'              => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get paginated transfer requests for admin list view.
     *
     * @param  array<string, mixed>  $filters  Query filters (form_transfer_id, approval_status, realization_status, etc.)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = TransferRequest::query()
            ->with(self::DEFAULT_RELATIONS);

        // Apply filters
        if (isset($filters['form_transfer_id'])) {
            $query->where('form_transfer_id', $filters['form_transfer_id']);
        }

        if (isset($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        if (isset($filters['realization_status'])) {
            $query->where('realization_status', $filters['realization_status']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('uid', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get transfer requests for a specific form transfer.
     *
     * @return Collection<int, TransferRequest>
     */
    public function getByFormTransfer(int $formTransferId, int $limit = 50): Collection
    {
        return TransferRequest::query()
            ->with(self::DEFAULT_RELATIONS)
            ->where('form_transfer_id', $formTransferId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new transfer request.
     *
     * @param  array<string, mixed>  $data  Request data
     */
    public function create(array $data): TransferRequest
    {
        return TransferRequest::create($data);
    }

    /**
     * Update an existing transfer request.
     *
     * @param  array<string, mixed>  $data  Update data
     */
    public function update(int $id, array $data): bool
    {
        $request = $this->find($id);

        if (! $request) {
            return false;
        }

        return $request->update($data);
    }

    /**
     * Delete a transfer request.
     */
    public function delete(int $id): bool
    {
        $request = $this->find($id);

        if (! $request) {
            return false;
        }

        return $request->delete();
    }

    /**
     * Count transfer requests by status.
     *
     * @return array<string, int> Status counts
     */
    public function countByStatus(?int $formTransferId = null): array
    {
        $query = TransferRequest::query();

        if ($formTransferId) {
            $query->where('form_transfer_id', $formTransferId);
        }

        return [
            'pending'   => (clone $query)->where('approval_status', 'pending')->count(),
            'approved'  => (clone $query)->where('approval_status', 'approved')->count(),
            'rejected'  => (clone $query)->where('approval_status', 'rejected')->count(),
            'realized'  => (clone $query)->where('realization_status', 'realized')->count(),
            'cancelled' => (clone $query)->where('realization_status', 'cancelled')->count(),
        ];
    }
}
