<?php

namespace Cesa\Shelf\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\User;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    public function downloadAssetTransfer(int $id): Response
    {
        $relations = [
            'company',
            'companyDocumentSetting',
            'fromUser',
            'toUser',
            'details.asset.category',
            'details.asset.brand',
            'details.asset.attributes',
        ];

        if (User::supportsJobTitles()) {
            $relations[] = 'fromUser.jobTitle';
            $relations[] = 'toUser.jobTitle';
        }

        $assetTransfer = AssetTransfer::with($relations)->findOrFail($id);
        $this->authorize('view', $assetTransfer);

        $statusMap = [
            'BERITA ACARA SERAH TERIMA'        => 'BA',
            'BERITA ACARA PENGALIHAN BARANG'   => 'BAPAB',
            'BERITA ACARA PENGEMBALIAN BARANG' => 'BAPEB',
        ];

        $status = $statusMap[$assetTransfer->status] ?? 'UNKNOWN';

        $headerImage = CompanyDocumentSetting::resolveLetterheadAbsolutePath($assetTransfer->company, $assetTransfer->companyDocumentSetting)
            ?: public_path('images/logo.png');

        $letterNumber = $assetTransfer->letter_number;
        $toUserName = strtolower(str_replace(' ', '_', $assetTransfer->toUser->name));
        $toUserJobTitle = $assetTransfer->toUser->jobTitle
            ? strtolower(str_replace(' ', '_', $assetTransfer->toUser->jobTitle->title))
            : 'no_title';

        $fileName = "{$status}_{$letterNumber}_{$toUserName}_{$toUserJobTitle}.pdf";

        $pdf = Pdf::loadView('shelf::pdf.asset-transfer', compact('assetTransfer', 'headerImage'));

        return $pdf->download($fileName);
    }

    public function downloadTaskCompletion(int $id): Response
    {
        $task = Task::with(['company', 'companyDocumentSetting'])->findOrFail($id);
        $this->authorize('view', $task);

        $headerImage = CompanyDocumentSetting::resolveLetterheadAbsolutePath($task->company, $task->companyDocumentSetting)
            ?: public_path('images/logo.png');

        $fileName = strtolower(str_replace(' ', '_', $task->name));

        $attachments = collect($task->attachment_files)->map(function (string $image) use ($task) {
            $imagePath = $task->managedFileAbsolutePathForPath('attachment', $image);

            if ($imagePath === null) {
                return '';
            }

            return "<img src='{$imagePath}' alt='Lampiran' style='max-width: 100%; height: auto; margin: 10px 0;'>";
        })->implode('');

        $pdf = Pdf::loadView('shelf::pdf.task-completion', compact('task', 'headerImage', 'attachments'));

        return $pdf->download('berita_acara_pengerjaan_'.$fileName.'.pdf');
    }

    public function previewTaskCompletion(int $id): Response
    {
        $task = Task::with(['company', 'companyDocumentSetting'])->findOrFail($id);
        $this->authorize('view', $task);

        $headerImage = CompanyDocumentSetting::resolveLetterheadAbsolutePath($task->company, $task->companyDocumentSetting)
            ?: public_path('images/logo.png');

        $attachments = collect($task->attachment_files)->map(function (string $image) use ($task) {
            $imagePath = $task->managedFileAbsolutePathForPath('attachment', $image);

            if ($imagePath === null) {
                return '';
            }

            return "<img src='{$imagePath}' alt='Lampiran' style='max-width: 100%; height: auto; margin: 10px 0;'>";
        })->implode('');

        return Pdf::loadView('shelf::pdf.task-completion', compact('task', 'headerImage', 'attachments'))
            ->stream('berita_acara_pengerjaan_'.strtolower(str_replace(' ', '_', $task->name)).'.pdf');
    }
}
