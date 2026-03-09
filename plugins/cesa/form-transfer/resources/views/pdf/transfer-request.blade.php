<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Transfer {{ $record->uid ?? $record->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.4;
            margin: 24px;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .left,
        .right {
            display: inline-block;
            vertical-align: top;
        }

        .left {
            width: 62%;
        }

        .right {
            width: 37%;
            text-align: right;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.4px;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #555;
        }

        .doc-number {
            margin-top: 2px;
            font-size: 12px;
            font-weight: 700;
        }

        .section-title {
            margin: 14px 0 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .box td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            vertical-align: top;
        }

        .box td.label {
            width: 34%;
            background: #f5f5f5;
            font-weight: 700;
        }

        .items th,
        .items td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .items th {
            background: #f5f5f5;
            text-align: left;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 45%;
            margin-left: auto;
            margin-top: 10px;
        }

        .totals td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        .totals td.label {
            background: #f5f5f5;
            font-weight: 700;
            width: 55%;
        }

        .totals tr.total td {
            font-weight: 700;
            font-size: 12px;
            border-top: 2px solid #111;
        }

        .notes {
            margin-top: 14px;
            border: 1px solid #d1d5db;
            padding: 8px;
            min-height: 40px;
        }

        .signatures {
            margin-top: 18px;
        }

        .signature-box {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-line {
            margin-top: 42px;
            border-top: 1px solid #555;
            width: 80%;
        }

        .footnote {
            margin-top: 18px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    @php
        $docNo = $record->uid ?? (string) $record->id;
        $approvals = is_array($record->approvals) ? $record->approvals : [];
        $submissionStatus = $record->submission_status?->getLabel() ?? '-';
        $approvalStatus = $record->approval_status?->getLabel() ?? '-';
        $realizationStatus = $record->realization_status?->getLabel() ?? '-';
        $transferAmount = (float) $record->transfer_amount;
        $formTransferName = $record->formTransfer?->name ?? 'Form Transfer';
    @endphp

    <div class="header">
        <div class="left">
            <p class="title">{{ strtoupper($formTransferName) }}</p>
            <p class="subtitle">Dokumen Pengajuan Transfer Dana</p>
            <p class="doc-number">No: {{ $docNo }}</p>
        </div>
        <div class="right">
            <div><strong>Tanggal Cetak</strong></div>
            <div>{{ now()->format('d M Y H:i') }}</div>
            <div style="margin-top: 6px;"><strong>Perusahaan</strong></div>
            <div>{{ $record->company?->name ?? '-' }}</div>
        </div>
    </div>

    <div class="section-title">Informasi Pengaju</div>
    <table class="box">
        <tr>
            <td class="label">Nama Pengaju</td>
            <td>{{ $record->requester_name ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td>{{ $record->email ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Divisi</td>
            <td>{{ $record->division?->name ?? $record->division_name ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Rincian Transfer</div>
    <table class="items">
        <tr>
            <th style="width: 34%;">Deskripsi</th>
            <th>Nilai</th>
        </tr>
        <tr>
            <td>Nomor Rekening</td>
            <td>{{ $record->account_number ?: '-' }}</td>
        </tr>
        <tr>
            <td>Nama Rekening</td>
            <td>{{ $record->account_name ?: '-' }}</td>
        </tr>
        <tr>
            <td>Bank</td>
            <td>{{ $record->bank?->display_name ?? $record->bank?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Keperluan</td>
            <td>{{ $record->purpose ?: '-' }}</td>
        </tr>
        <tr>
            <td>Referensi</td>
            <td>{{ $record->reference_note ?: '-' }}</td>
        </tr>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Jumlah Transfer</td>
            <td class="text-right">Rp {{ number_format($transferAmount, 0, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td class="label">Total Dibayarkan</td>
            <td class="text-right">Rp {{ number_format($transferAmount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">Status Dokumen</div>
    <table class="box">
        <tr>
            <td class="label">Status Pengajuan</td>
            <td>{{ $submissionStatus }}</td>
        </tr>
        <tr>
            <td class="label">Status Approval</td>
            <td>{{ $approvalStatus }}</td>
        </tr>
        <tr>
            <td class="label">Status Realisasi</td>
            <td>{{ $realizationStatus }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Realisasi</td>
            <td>{{ $record->realized_at?->format('d M Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Catatan Realisasi</td>
            <td>{{ $record->realization_notes ?: '-' }}</td>
        </tr>
    </table>

    @if ($approvals !== [])
        <div class="section-title">Riwayat Approval</div>
        <table class="items">
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 24%;">Approver</th>
                <th style="width: 26%;">Jabatan</th>
                <th style="width: 20%;">Status</th>
                <th>Keterangan</th>
            </tr>
            @foreach ($approvals as $index => $approval)
                @php
                    $statusValue = $approval['status'] ?? null;
                    $statusLabel = \Cesa\FormTransfer\Enums\ApprovalStatus::tryFrom((string) $statusValue)?->getLabel() ?? (string) ($statusValue ?: '-');
                    $approverTitle = $approval['jabatan'] ?? $approval['title'] ?? '-';
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $approval['name'] ?? '-' }}</td>
                    <td>{{ $approverTitle }}</td>
                    <td>{{ $statusLabel }}</td>
                    <td>{{ $approval['remark'] ?? '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="section-title">Catatan</div>
    <div class="notes">
        {{ $record->realization_notes ?: 'Tidak ada catatan tambahan.' }}
    </div>

    <div class="signatures">
        <div class="signature-box">
            Disiapkan oleh,
            <div class="signature-line"></div>
            {{ $record->requester_name ?: '-' }}
        </div>
        <div class="signature-box" style="text-align: right;">
            Disetujui oleh,
            <div class="signature-line" style="margin-left: auto;"></div>
            Finance
        </div>
    </div>

    <div class="footnote">Dokumen ini dibuat otomatis dari sistem CESA.</div>
</body>
</html>
