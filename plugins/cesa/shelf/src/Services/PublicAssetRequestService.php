<?php

namespace Cesa\Shelf\Services;

use Cesa\Shelf\Enums\ApprovalStatus;
use Cesa\Shelf\Enums\RequestStatus;
use Cesa\Shelf\Mail\ApprovalRequested;
use Cesa\Shelf\Mail\AssetRequestStatusChanged;
use Cesa\Shelf\Mail\AssetRequestSubmitted;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\RequestApproval;
use Cesa\Shelf\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicAssetRequestService
{
    /**
     * @return array<string, array{value: string, label: string}>
     */
    public function requestTypes(): array
    {
        return [
            'pengadaan-aset' => [
                'value' => 'pengadaan_aset',
                'label' => 'Pengadaan Aset',
            ],
            'perbaikan-aset' => [
                'value' => 'perbaikan_aset',
                'label' => 'Perbaikan Aset',
            ],
            'penarikan-aset' => [
                'value' => 'penarikan_aset',
                'label' => 'Penarikan Aset',
            ],
        ];
    }

    /**
     * @return array{value: string, label: string}|null
     */
    public function requestType(string $slug): ?array
    {
        return $this->requestTypes()[$slug] ?? null;
    }

    public function getDivisionOptions(string $requestType): Collection
    {
        return ApprovalLevel::query()
            ->where('request_type', $requestType)
            ->where('division', '!=', ApprovalLevel::ALL_DIVISIONS)
            ->pluck('division')
            ->map(fn ($division) => ApprovalLevel::normalizeDivision($division))
            ->filter()
            ->unique(fn (string $division) => ApprovalLevel::normalizeDivisionKey($division))
            ->sortBy(fn (string $division) => ApprovalLevel::normalizeDivisionKey($division))
            ->values();
    }

    public function submit(string $slug, array $data): AssetRequest
    {
        $requestType = $this->requestType($slug);

        if ($requestType === null) {
            throw ValidationException::withMessages([
                'type' => 'Jenis pengajuan tidak tersedia.',
            ]);
        }

        $requesterName = trim((string) Arr::get($data, 'requester_name'));
        $email = trim((string) Arr::get($data, 'email'));
        $division = $this->validateAndNormalizeDivision(
            $requestType['value'],
            (string) Arr::get($data, 'division'),
        );
        $placement = trim((string) Arr::get($data, 'placement'));
        $itemName = trim((string) Arr::get($data, 'item_name'));
        $qty = (int) Arr::get($data, 'qty', 1);
        $approvalTrack = $this->resolveApprovalTrack($requestType['value'], $division);

        $matchedUsers = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($requesterName)])
            ->get(['id']);

        $userId = $matchedUsers->count() === 1 ? $matchedUsers->first()->id : null;
        $assetId = null;

        if (
            $userId !== null
            && in_array($requestType['value'], ['perbaikan_aset', 'penarikan_aset'], true)
        ) {
            $matchedAssets = Asset::query()
                ->where('recipient_id', $userId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($itemName)])
                ->get(['id']);

            $assetId = $matchedAssets->count() === 1 ? $matchedAssets->first()->id : null;
        }

        $attachmentPath = Arr::get($data, 'attachment_path');
        $attachmentOriginalName = Arr::get($data, 'attachment_original_name');

        $result = DB::transaction(function () use (
            $requestType,
            $requesterName,
            $email,
            $division,
            $placement,
            $itemName,
            $qty,
            $approvalTrack,
            $userId,
            $assetId,
            $attachmentPath,
            $attachmentOriginalName,
        ) {
            $assetRequest = AssetRequest::create([
                'uuid'                     => Str::uuid()->toString(),
                'request_type'             => $requestType['value'],
                'requester_name'           => $requesterName,
                'email'                    => $email,
                'division'                 => $division,
                'approval_track'           => $approvalTrack,
                'placement'                => $placement,
                'item_name'                => $itemName,
                'qty'                      => $qty,
                'attachment_path'          => $attachmentPath,
                'attachment_original_name' => $attachmentOriginalName,
                'user_id'                  => $userId,
                'asset_id'                 => $assetId,
            ]);

            $initialApproval = $this->syncApprovalFlow($assetRequest);

            return [
                'assetRequest'    => $assetRequest,
                'initialApproval' => $initialApproval,
            ];
        });

        $assetRequest = $result['assetRequest'];
        $initialApproval = $result['initialApproval'];

        if ($initialApproval) {
            $this->sendApprovalRequestNotification($assetRequest, $initialApproval);
        }

        $this->sendInitialNotifications($assetRequest, $initialApproval);

        return $assetRequest->fresh('approvals') ?? $assetRequest;
    }

    /**
     * @return array{type: string, message: string, assetRequest?: AssetRequest}
     */
    public function processApproval(string $token, string $action, ?string $notes = null): array
    {
        $approval = RequestApproval::query()
            ->where('token', $token)
            ->with([
                'assetRequest' => fn ($query) => $query->withTrashed(),
                'approvalLevel',
            ])
            ->firstOrFail();

        $result = DB::transaction(function () use ($approval, $action, $notes) {
            $lockedApproval = RequestApproval::query()
                ->with('approvalLevel')
                ->lockForUpdate()
                ->findOrFail($approval->getKey());

            $assetRequest = AssetRequest::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($lockedApproval->asset_request_id);

            $currentApproval = $this->disconnectInvalidPendingApprovals($assetRequest);

            if ($assetRequest->trashed()) {
                return [
                    'type'    => 'info',
                    'message' => 'Pengajuan ini sudah diarsipkan. Link approval tidak lagi aktif.',
                ];
            }

            if ($assetRequest->status !== RequestStatus::Pending) {
                return [
                    'type'    => 'info',
                    'message' => 'Pengajuan ini sudah selesai diproses. Link approval tidak lagi aktif.',
                ];
            }

            if ($currentApproval === null) {
                $this->autoApproveRequest($assetRequest);

                return [
                    'type'                  => 'info',
                    'message'               => 'Pengajuan ini tidak lagi memiliki approver aktif dan telah disetujui otomatis.',
                    'assetRequest'          => $assetRequest->fresh(),
                    'shouldNotifyRequester' => true,
                ];
            }

            if ($lockedApproval->status === ApprovalStatus::Pending && ! $lockedApproval->hasActiveApprover()) {
                return [
                    'type'             => 'info',
                    'message'          => 'Link approval ini sudah tidak aktif karena approver tidak lagi terhubung ke employee / user aktif.',
                    'assetRequest'     => $assetRequest,
                    'approvalToNotify' => $currentApproval?->notified_at === null ? $currentApproval : null,
                ];
            }

            if ($lockedApproval->status !== ApprovalStatus::Pending) {
                return [
                    'type'    => 'info',
                    'message' => 'Anda sudah merespons pengajuan ini sebelumnya.',
                ];
            }

            $currentApprovalId = $currentApproval?->getKey();

            if ($currentApprovalId !== $lockedApproval->id) {
                return [
                    'type'    => 'info',
                    'message' => 'Pengajuan ini masih menunggu approval level sebelumnya.',
                ];
            }

            $isApproved = $action === 'approve';

            $lockedApproval->update([
                'status'       => $isApproved ? ApprovalStatus::Approved : ApprovalStatus::Rejected,
                'notes'        => $notes,
                'responded_at' => now(),
            ]);

            $shouldNotifyRequester = false;
            $approvalToNotify = null;

            if ($isApproved) {
                $nextApproval = $this->disconnectInvalidPendingApprovals($assetRequest);

                if ($nextApproval) {
                    $approvalToNotify = $nextApproval;
                } else {
                    $assetRequest->update(['status' => RequestStatus::Approved]);
                    $shouldNotifyRequester = true;
                }
            } else {
                $assetRequest->update([
                    'status'      => RequestStatus::Rejected,
                    'admin_notes' => "Ditolak oleh {$lockedApproval->approver_name} (Level {$lockedApproval->level}): ".($notes ?? '-'),
                ]);

                $shouldNotifyRequester = true;
            }

            return [
                'type'                  => 'success',
                'message'               => $isApproved ? 'Pengajuan berhasil disetujui.' : 'Pengajuan berhasil ditolak.',
                'assetRequest'          => $assetRequest,
                'approvalToNotify'      => $approvalToNotify,
                'shouldNotifyRequester' => $shouldNotifyRequester,
            ];
        });

        if (($result['approvalToNotify'] ?? null) instanceof RequestApproval && isset($result['assetRequest'])) {
            $this->sendApprovalRequestNotification($result['assetRequest'], $result['approvalToNotify']);
        }

        if (($result['shouldNotifyRequester'] ?? false) === true && isset($result['assetRequest'])) {
            $this->notifyRequesterStatusChanged($result['assetRequest']);
        }

        return $result;
    }

    public function syncApprovalFlow(AssetRequest $assetRequest): ?RequestApproval
    {
        $assetRequest->refresh()->loadMissing('approvals');

        if ($assetRequest->status !== RequestStatus::Pending) {
            return $assetRequest->currentApproval();
        }

        $hasProcessedApprovals = $assetRequest->approvals->contains(
            fn (RequestApproval $approval): bool => $approval->status !== ApprovalStatus::Pending || $approval->responded_at !== null,
        );

        if ($assetRequest->approvals->isNotEmpty() && $hasProcessedApprovals) {
            $currentApproval = $this->disconnectInvalidPendingApprovals($assetRequest);

            if ($currentApproval === null) {
                $this->autoApproveRequest($assetRequest);
            }

            return $currentApproval;
        }

        $assetRequest->approvals()->delete();

        $approvalTrack = $assetRequest->approval_track
            ?? $this->resolveApprovalTrack($assetRequest->request_type, $assetRequest->division);

        if (! $approvalTrack) {
            $this->autoApproveRequest($assetRequest);

            return null;
        }

        if ($assetRequest->approval_track !== $approvalTrack) {
            $assetRequest->forceFill(['approval_track' => $approvalTrack])->save();
        }

        $approvalLevels = ApprovalLevel::query()
            ->forTrack($assetRequest->request_type, $approvalTrack)
            ->orderBy('level')
            ->get();

        if ($approvalLevels->isEmpty()) {
            $this->autoApproveRequest($assetRequest);

            return null;
        }

        $approvals = $approvalLevels
            ->map(fn (ApprovalLevel $level): ?RequestApproval => $this->createApproval($assetRequest, $level))
            ->filter();

        if ($approvals->isEmpty()) {
            $this->autoApproveRequest($assetRequest);

            return null;
        }

        return $approvals->first();
    }

    private function validateAndNormalizeDivision(string $requestType, string $division): string
    {
        $normalizedDivision = ApprovalLevel::normalizeDivision($division);
        $configuredDivisions = $this->getDivisionOptions($requestType);

        if ($configuredDivisions->isEmpty()) {
            return $normalizedDivision;
        }

        $matchedDivision = $configuredDivisions->first(
            fn (string $configuredDivision): bool => ApprovalLevel::normalizeDivisionKey($configuredDivision)
                === ApprovalLevel::normalizeDivisionKey($normalizedDivision),
        );

        if ($matchedDivision !== null) {
            return $matchedDivision;
        }

        if ($this->hasGlobalApprovalTrack($requestType)) {
            return $normalizedDivision;
        }

        throw ValidationException::withMessages([
            'division' => 'Divisi yang dipilih tidak tersedia untuk jenis pengajuan ini.',
        ]);
    }

    private function resolveApprovalTrack(string $requestType, string $division): ?string
    {
        $normalizedDivision = ApprovalLevel::normalizeDivision($division);

        if (ApprovalLevel::query()->forTrack($requestType, $normalizedDivision)->exists()) {
            return $normalizedDivision;
        }

        if ($this->hasGlobalApprovalTrack($requestType)) {
            return ApprovalLevel::ALL_DIVISIONS;
        }

        return null;
    }

    private function hasGlobalApprovalTrack(string $requestType): bool
    {
        return ApprovalLevel::query()
            ->forTrack($requestType, ApprovalLevel::ALL_DIVISIONS)
            ->exists();
    }

    public function disconnectPendingApprovalsForEmployee(int $employeeId): void
    {
        $this->disconnectPendingApprovals('approver_employee_id', $employeeId);
    }

    public function disconnectPendingApprovalsForUser(int $userId): void
    {
        $this->disconnectPendingApprovals('approver_user_id', $userId);
    }

    private function autoApproveRequest(AssetRequest $assetRequest): void
    {
        $assetRequest->update([
            'status'      => RequestStatus::Approved,
            'admin_notes' => 'Disetujui otomatis karena tidak ada approval yang dikonfigurasi untuk divisi ini.',
        ]);
    }

    private function notifyRequesterStatusChanged(AssetRequest $assetRequest): void
    {
        if (! $assetRequest->email) {
            return;
        }

        $this->dispatchMailSafely(
            $assetRequest->email,
            new AssetRequestStatusChanged($assetRequest),
        );
    }

    private function sendInitialNotifications(AssetRequest $assetRequest, ?RequestApproval $initialApproval): void
    {
        $this->dispatchMailSafely(
            $assetRequest->email,
            new AssetRequestSubmitted($assetRequest),
        );

        if ($initialApproval) {
            return;
        }

        if ($assetRequest->status === RequestStatus::Approved) {
            $this->notifyRequesterStatusChanged($assetRequest);
        }
    }

    private function sendApprovalRequestNotification(AssetRequest $assetRequest, RequestApproval $approval): void
    {
        $this->dispatchMailOrFail(
            $approval->approver_email,
            new ApprovalRequested($assetRequest, $approval),
        );

        if ($approval->notified_at === null) {
            $approval->forceFill([
                'notified_at' => now(),
            ])->saveQuietly();
        }
    }

    private function dispatchMailOrFail(string $recipient, Mailable $mailable): void
    {
        if (config('queue.default') !== 'sync') {
            Mail::to($recipient)->queue($mailable);

            return;
        }

        Mail::to($recipient)->send($mailable);
    }

    private function dispatchMailSafely(string $recipient, Mailable $mailable): void
    {
        if (app()->runningUnitTests()) {
            $this->sendMailSafely(fn () => Mail::to($recipient)->send($mailable));

            return;
        }

        if (config('queue.default') !== 'sync') {
            $this->sendMailSafely(fn () => Mail::to($recipient)->queue($mailable));

            return;
        }

        app()->terminating(function () use ($recipient, $mailable): void {
            $this->sendMailSafely(fn () => Mail::to($recipient)->send($mailable));
        });
    }

    private function sendMailSafely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function createApproval(AssetRequest $assetRequest, ApprovalLevel $approvalLevel): ?RequestApproval
    {
        $approver = $approvalLevel->resolveActiveApprover();

        if ($approver === null) {
            return null;
        }

        return RequestApproval::create([
            'asset_request_id'      => $assetRequest->id,
            'approval_level_id'     => $approvalLevel->id,
            'token'                 => Str::uuid()->toString(),
            'level'                 => $approvalLevel->level,
            'approver_employee_id'  => $approver['employee_id'],
            'approver_user_id'      => $approver['user_id'],
            'approver_name'         => $approver['name'],
            'approver_email'        => $approver['email'],
            'status'                => ApprovalStatus::Pending,
        ]);
    }

    private function disconnectInvalidPendingApprovals(AssetRequest $assetRequest): ?RequestApproval
    {
        $pendingApprovals = RequestApproval::query()
            ->where('asset_request_id', $assetRequest->getKey())
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('level')
            ->get();

        foreach ($pendingApprovals as $pendingApproval) {
            if ($pendingApproval->hasActiveApprover()) {
                return $pendingApproval;
            }

            $pendingApproval->delete();
        }

        return null;
    }

    private function disconnectPendingApprovals(string $column, int $value): void
    {
        $assetRequestIds = RequestApproval::query()
            ->where($column, $value)
            ->where('status', ApprovalStatus::Pending)
            ->pluck('asset_request_id')
            ->unique()
            ->values();

        foreach ($assetRequestIds as $assetRequestId) {
            $approvalToNotify = null;
            $requestToNotify = null;

            DB::transaction(function () use ($assetRequestId, &$approvalToNotify, &$requestToNotify): void {
                $assetRequest = AssetRequest::query()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->find($assetRequestId);

                if ($assetRequest === null || $assetRequest->trashed() || $assetRequest->status !== RequestStatus::Pending) {
                    return;
                }

                $currentApprovalId = RequestApproval::query()
                    ->where('asset_request_id', $assetRequest->getKey())
                    ->where('status', ApprovalStatus::Pending)
                    ->orderBy('level')
                    ->value('id');

                $nextApproval = $this->disconnectInvalidPendingApprovals($assetRequest);

                if ($nextApproval !== null) {
                    if ($nextApproval->getKey() !== $currentApprovalId && $nextApproval->notified_at === null) {
                        $approvalToNotify = $nextApproval->getKey();
                    }

                    return;
                }

                $assetRequest->forceFill([
                    'status'      => RequestStatus::Approved,
                    'admin_notes' => 'Disetujui otomatis karena approver yang tersisa tidak lagi terhubung ke employee / user aktif.',
                ])->saveQuietly();

                $requestToNotify = $assetRequest->getKey();
            });

            if ($approvalToNotify !== null) {
                $approval = RequestApproval::query()->find($approvalToNotify);
                $assetRequest = AssetRequest::query()->find($assetRequestId);

                if ($approval !== null && $assetRequest !== null) {
                    $this->sendApprovalRequestNotification($assetRequest, $approval);
                }
            }

            if ($requestToNotify !== null) {
                $assetRequest = AssetRequest::query()->find($requestToNotify);

                if ($assetRequest !== null) {
                    $this->notifyRequesterStatusChanged($assetRequest);
                }
            }
        }
    }
}
