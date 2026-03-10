<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Cesa\ExitClearance\Models\Department;
use Cesa\ExitClearance\Models\Request;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class RequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = $this->getRequestsFromGForm();

        foreach ($requests as $requestData) {
            $department = $this->getDepartmentByCode($requestData['department_code']);
            $lookup = ! empty($requestData['form_uid'])
                ? ['form_uid' => $requestData['form_uid']]
                : ['email' => $requestData['email']];
            $requestDate = $this->parseDate($requestData['request_date']) ?? now()->format('Y-m-d');

            Request::updateOrCreate(
                $lookup,
                [
                    'department_id'                  => $department?->id,
                    'name'                           => $requestData['name'],
                    'email'                          => $requestData['email'],
                    'phone'                          => null,
                    'position'                       => $requestData['position'],
                    'placement'                      => $requestData['placement'],
                    'join_date'                      => $this->parseDate($requestData['join_date']),
                    'request_date'                   => $requestDate,
                    'departure_date'                 => $this->parseDate($requestData['departure_date']),
                    'reason'                         => $requestData['reason'],
                    'workload_feedback'              => $requestData['workload_feedback'],
                    'career_growth_feedback'         => $requestData['career_growth_feedback'],
                    'facility_welfare_feedback'      => $requestData['facility_welfare_feedback'],
                    'work_relationship_feedback'     => $requestData['work_relationship_feedback'],
                    'compensation_feedback'          => $requestData['compensation_feedback'],
                    'division_feedback'              => $requestData['division_feedback'],
                    'company_feedback'               => $requestData['company_feedback'],
                    'clearance_kartu_halo'           => $requestData['clearance_kartu_halo'],
                    'clearance_employee_debt'        => $requestData['clearance_employee_debt'],
                    'clearance_uniform_return'       => $requestData['clearance_uniform_return'],
                    'clearance_vehicle_return'       => $requestData['clearance_vehicle_return'],
                    'clearance_inventory_return'     => $requestData['clearance_inventory_return'],
                    'clearance_account_deactivation' => $requestData['clearance_account_deactivation'],
                    'clearance_receivable_data'      => $requestData['clearance_receivable_data'],
                    'clearance_promotor_internal'    => $requestData['clearance_promotor_internal'],
                    'clearance_nota_pending'         => $requestData['clearance_nota_pending'],
                    'clearance_stock_opname'         => $requestData['clearance_stock_opname'],
                    'resignation_letter_url'         => ! empty($requestData['resignation_letter_url'])
                        ? $requestData['resignation_letter_url']
                        : null,
                    'form_uid'          => $requestData['form_uid'],
                    'form_status'       => $requestData['form_status'],
                    'form_response_id'  => $requestData['form_response_id'],
                    'created_by'        => null,
                ]
            );
        }
    }

    /**
     * Get requests from gform-ec/data.csv
     *
     * @return array<array{
     *   department_code: string,
     *   name: string,
     *   email: string,
     *   position: string|null,
     *   placement: string|null,
     *   join_date: string|null,
     *   request_date: string,
     *   departure_date: string|null,
     *   reason: string|null,
     *   workload_feedback: string|null,
     *   career_growth_feedback: string|null,
     *   facility_welfare_feedback: string|null,
     *   work_relationship_feedback: string|null,
     *   compensation_feedback: string|null,
     *   division_feedback: string|null,
     *   company_feedback: string|null,
     *   clearance_kartu_halo: string|null,
     *   clearance_employee_debt: string|null,
     *   clearance_uniform_return: string|null,
     *   clearance_vehicle_return: string|null,
     *   clearance_inventory_return: string|null,
     *   clearance_account_deactivation: string|null,
     *   clearance_receivable_data: string|null,
     *   clearance_promotor_internal: string|null,
     *   clearance_nota_pending: string|null,
     *   clearance_stock_opname: string|null,
     *   resignation_letter_url: string|null,
     *   form_uid: string|null,
     *   form_status: string|null,
     *   form_response_id: string|null
     * }>
     */
    private function getRequestsFromGForm(): array
    {
        $csvPath = __DIR__.'/../../gform-ec/data.csv';

        if (! file_exists($csvPath)) {
            return [];
        }

        $csvFile = fopen($csvPath, 'r');
        if ($csvFile === false) {
            return [];
        }

        $requests = [];

        $headers = fgetcsv($csvFile, 0, ',');
        if ($headers === false) {
            fclose($csvFile);

            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], $headers);

        while (($row = fgetcsv($csvFile, 0, ',')) !== false) {
            $record = $this->combineRow($headers, $row);
            $email = $record['Email Address'] ?? null;

            if (empty($email)) {
                continue;
            }

            $requests[] = [
                'department_code'                => $this->parseDepartmentCode($record['Divisi'] ?? null),
                'name'                           => $record['Nama Lengkap'] ?? null,
                'email'                          => $email,
                'position'                       => $record['Posisi Pekerjaan'] ?? null,
                'placement'                      => $record['Penempatan'] ?? null,
                'join_date'                      => $record['Tanggal Masuk Kerja'] ?? null,
                'request_date'                   => $record['Timestamp'] ?? null,
                'departure_date'                 => $record['Tanggal Keluar Kerja'] ?? null,
                'reason'                         => $record['1. Alasan Anda Mengajukan permohonan pengunduran diri ?'] ?? null,
                'workload_feedback'              => $record['2. Jelaskan apa yang Anda rasakan dengan beban pekerjaan yang telah diberikan pada Anda dari awal masuk kerja hingga saat ini?'] ?? null,
                'career_growth_feedback'         => $record['3. Jelaskan bagaimana jenjang karir Anda selama anda bekerja di perusahaan ini?'] ?? null,
                'facility_welfare_feedback'      => $record['4. Bagaimana penilaian Anda atas perhatian penunjang kerja, kesejahteraan dan fasilitas yang diberikan kepada Anda oleh perusahaan ini'] ?? null,
                'work_relationship_feedback'     => $record['5. Bagaimana hubungan kerja Anda di lingkungan kerja Perusahaan ini'] ?? null,
                'compensation_feedback'          => $record['6. Bagaimana penilaian Anda atas imbalan yang Anda terima dari Perusahaan pada saat ini'] ?? null,
                'division_feedback'              => $record['7. Berikan pendapat Anda mengenai Divisi tempat Anda ditempatkan sebagai bahan masukan bagi kami'] ?? null,
                'company_feedback'               => $record['8. Berikan pendapat Anda mengenai perusahaan ini sebagai bahan masukan bagi kami'] ?? null,
                'clearance_kartu_halo'           => $record['1. Kartu Halo & Tagihan yang belum dibayar'] ?? null,
                'clearance_employee_debt'        => $record['2. Hutang karyawan terhadap Perusahaan'] ?? null,
                'clearance_uniform_return'       => $record['3. Pengembalian seragam kantor, nametag dsb'] ?? null,
                'clearance_vehicle_return'       => $record['4. Menyerahkan kendaraan perusahaan'] ?? null,
                'clearance_inventory_return'     => $record['5. Pengecekan dan pengembalian Inventaris kantor'] ?? null,
                'clearance_account_deactivation' => $record['6. Penonaktifan Account/User'] ?? null,
                'clearance_receivable_data'      => $record['7. Data Tagihan / Piutang'] ?? null,
                'clearance_promotor_internal'    => $record['8. Promotor Internal'] ?? null,
                'clearance_nota_pending'         => $record['9. Nota Pending'] ?? null,
                'clearance_stock_opname'         => $record['10. Stock Opname'] ?? null,
                'resignation_letter_url'         => $record['Surat Resign'] ?? null,
                'form_uid'                       => $record['_uid'] ?? null,
                'form_status'                    => $record['_status'] ?? null,
                'form_response_id'               => $record['_response_id'] ?? null,
            ];
        }

        fclose($csvFile);

        return $requests;
    }

    /**
     * Normalize CSV headers by trimming whitespace and wrapping quotes.
     */
    private function normalizeHeader(string $header): string
    {
        return trim($header, " \t\n\r\0\x0B\"");
    }

    /**
     * Combine headers with row values, padding or trimming to match column count.
     *
     * @return array<string, string|null>
     */
    private function combineRow(array $headers, array $row): array
    {
        $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
        $record = array_combine($headers, $row);

        return $record === false ? [] : $record;
    }

    /**
     * Parse department code from division string
     */
    private function parseDepartmentCode(?string $division): ?string
    {
        if (empty($division)) {
            return null;
        }

        return strtoupper(str_replace(' ', '_', $division));
    }

    /**
     * Get department by code
     */
    private function getDepartmentByCode(?string $code): ?Department
    {
        if (empty($code)) {
            return null;
        }

        return Department::where('code', $code)->first();
    }

    /**
     * Parse date from MM/DD/YYYY format to Y-m-d
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['m/d/Y H:i:s', 'm/d/Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
