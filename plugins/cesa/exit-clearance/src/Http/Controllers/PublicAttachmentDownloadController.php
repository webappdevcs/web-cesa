<?php

namespace Cesa\ExitClearance\Http\Controllers;

use Cesa\ExitClearance\Models\Request as ExitClearanceRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, string $response, string $attachment): StreamedResponse
    {
        abort_unless(in_array($attachment, ['resignation-letter'], true), 404);

        $exitRequest = ExitClearanceRequest::query()
            ->where('form_response_id', $response)
            ->first();

        if (! $exitRequest) {
            Log::warning('Exit clearance request for attachment download not found.', [
                'response'   => $response,
                'attachment' => $attachment,
            ]);

            abort(404);
        }

        $path = match ($attachment) {
            'resignation-letter' => $exitRequest->resignation_letter_url,
        };

        if (blank($path)) {
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
                Log::warning('Attempted exit clearance attachment download failed for disk.', [
                    'exit_clearance_request_id' => $exitRequest->getKey(),
                    'attachment'                => $attachment,
                    'disk'                      => $disk,
                    'path'                      => $relativePath,
                    'error'                     => $exception->getMessage(),
                ]);
            }
        }

        Log::error('Unable to serve exit clearance attachment after disk fallbacks.', [
            'exit_clearance_request_id' => $exitRequest->getKey(),
            'attachment'                => $attachment,
            'path'                      => $relativePath,
            'disks_tested'              => $candidateDisks,
        ]);

        abort(404);
    }
}
