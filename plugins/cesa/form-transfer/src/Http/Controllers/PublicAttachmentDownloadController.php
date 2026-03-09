<?php

namespace Cesa\FormTransfer\Http\Controllers;

use Cesa\FormTransfer\Services\TransferRequestService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicAttachmentDownloadController extends Controller
{
    public function __construct(
        protected TransferRequestService $transferRequestService,
    ) {}

    public function __invoke(Request $request, string $statusResponseId, string $attachment): StreamedResponse
    {
        abort_unless(in_array($attachment, ['invoice', 'account-attachment', 'realization-proof'], true), 404);

        $transferRequest = $this->transferRequestService->findByStatusResponseId($statusResponseId);

        if (! $transferRequest) {
            Log::warning('Transfer request for attachment download not found.', [
                'status_response_id' => $statusResponseId,
                'attachment'         => $attachment,
            ]);

            abort(404);
        }

        $path = match ($attachment) {
            'invoice'            => $transferRequest->invoice_path,
            'account-attachment' => $transferRequest->account_attachment_path,
            'realization-proof'  => $transferRequest->realization_proof_path,
        };

        $paths = \Cesa\FormTransfer\Models\TransferRequest::normalizeAttachmentPaths($path);
        $path = $this->resolveAttachmentPath($paths, $request->query('file'));

        if (! $path) {
            Log::warning('Requested attachment is missing from transfer request.', [
                'transfer_request_id' => $transferRequest->getKey(),
                'attachment'          => $attachment,
            ]);

            abort(404);
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return redirect()->away($path);
        }

        $relativePath = ltrim($path, '/');

        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk', null),
            config('filesystems.default'),
            'local',
            'public',
            'ftp',
        ])));

        foreach ($candidateDisks as $disk) {
            try {
                if (! config()->has("filesystems.disks.{$disk}")) {
                    continue;
                }

                if (! Storage::disk($disk)->exists($relativePath)) {
                    continue;
                }

                return Storage::disk($disk)->response($relativePath, basename($relativePath));
            } catch (\Throwable $exception) {
                Log::warning('Attempted attachment download failed for disk.', [
                    'transfer_request_id' => $transferRequest->getKey(),
                    'attachment'          => $attachment,
                    'disk'                => $disk,
                    'path'                => $relativePath,
                    'error'               => $exception->getMessage(),
                ]);
            }
        }

        Log::error('Unable to serve attachment after disk fallbacks.', [
            'transfer_request_id' => $transferRequest->getKey(),
            'attachment'          => $attachment,
            'path'                => $relativePath,
            'disks_tested'        => $candidateDisks,
        ]);

        abort(404);
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function resolveAttachmentPath(array $paths, mixed $index): ?string
    {
        if ($paths === []) {
            return null;
        }

        if ($index === null || $index === '') {
            return $paths[0] ?? null;
        }

        if (! is_numeric($index)) {
            return null;
        }

        $index = (int) $index;

        return $paths[$index] ?? null;
    }
}
