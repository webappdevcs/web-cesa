<?php

namespace Cesa\Shelf\Http\Controllers;

use Cesa\Shelf\Services\PublicAssetRequestService;
use Cesa\Shelf\Support\ShelfStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssetRequestController extends Controller
{
    public function __construct(protected PublicAssetRequestService $publicAssetRequestService) {}

    public function legacyIndexRedirect(): RedirectResponse
    {
        return redirect()->route('asset-requests.index', [], 301);
    }

    public function legacySuccessRedirect(string $uuid): RedirectResponse
    {
        return redirect()->route('asset-requests.progress', ['uuid' => $uuid], 301);
    }

    public function legacyApprovalRedirect(string $token): RedirectResponse
    {
        return redirect()->route('asset-requests.show-approval', ['token' => $token], 301);
    }

    public function legacyCreateRedirect(string $type): RedirectResponse
    {
        return redirect()->route('asset-requests.create', ['type' => $type], 301);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        abort_if($this->publicAssetRequestService->requestType($type) === null, 404);

        $validated = $request->validate([
            'requester_name' => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'division'       => ['required', 'string', 'max:255'],
            'placement'      => ['required', 'string', 'max:255'],
            'item_name'      => ['required', 'string', 'max:255'],
            'qty'            => ['required', 'integer', 'min:1'],
            'attachment'     => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,gif,webp,bmp'],
        ]);

        $attachmentDisk = ShelfStorage::disk();
        $attachmentPath = null;
        $attachmentFile = $request->file('attachment');

        if ($attachmentFile !== null) {
            $attachmentPath = $attachmentFile->store('shelf/asset-requests/tmp', $attachmentDisk);
            $validated['attachment_path'] = $attachmentPath;
            $validated['attachment_original_name'] = mb_substr(
                $attachmentFile->getClientOriginalName(),
                0,
                255,
            );
        }

        try {
            $assetRequest = $this->publicAssetRequestService->submit($type, $validated);
        } catch (ValidationException $exception) {
            if ($attachmentPath !== null) {
                Storage::disk($attachmentDisk)->delete($attachmentPath);
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($attachmentPath !== null) {
                Storage::disk($attachmentDisk)->delete($attachmentPath);
            }

            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'request' => 'Request tidak dapat diproses saat ini. Silakan coba lagi beberapa saat lagi.',
                ]);
        }

        return redirect()->route('asset-requests.progress', ['uuid' => $assetRequest->uuid]);
    }

    public function processApproval(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->publicAssetRequestService->processApproval(
                $token,
                $validated['action'],
                $validated['notes'] ?? null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('asset-requests.show-approval', ['token' => $token])
                ->with('error', 'Pengajuan tidak dapat diproses saat ini. Silakan coba lagi beberapa saat lagi.');
        }

        $redirect = redirect()->route('asset-requests.show-approval', ['token' => $token]);

        if (filled($result['type'] ?? null) && filled($result['message'] ?? null)) {
            $redirect->with($result['type'], $result['message']);
        }

        return $redirect;
    }
}
