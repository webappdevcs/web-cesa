<?php

namespace Cesa\Rekrutmen\Http\Controllers;

use Cesa\Rekrutmen\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class JobApplicationAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, JobApplication $jobApplication, string $attachment): Response
    {
        abort_unless($attachment === 'resume', 404);
        abort_unless($request->user()?->can('view', $jobApplication), 403);

        $path = $jobApplication->resume_path;

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
            } catch (\Throwable) {
                continue;
            }
        }

        abort(404);
    }
}
