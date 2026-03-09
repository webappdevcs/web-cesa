<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use App\Models\User;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Notification;

class RequestManPowerTest extends RekrutmenTestCase
{
    public function test_nama_karyawan_replacement_is_only_stored_for_replacement_status(): void
    {
        $newHiring = RequestManPower::query()->create([
            ...$this->basePayload(),
            'status_kebutuhan'          => StatusKebutuhan::NEW_HIRING,
            'nama_karyawan_replacement' => 'Should Be Null',
        ]);

        $replacement = RequestManPower::query()->create([
            ...$this->basePayload([
                'email_address'    => 'replacement@example.com',
                'status_kebutuhan' => StatusKebutuhan::REPLACEMENT,
            ]),
            'nama_karyawan_replacement' => '  Rina Putri  ',
        ]);

        $this->assertNull($newHiring->fresh()->nama_karyawan_replacement);
        $this->assertSame('Rina Putri', $replacement->fresh()->nama_karyawan_replacement);
        $this->assertTrue($replacement->isReplacement());
    }

    public function test_approve_by_updates_status_and_creates_job_posting(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name'        => 'Default Pipeline',
            'description' => 'Main pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'requester@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();
        $request->approveBy($approver->id);

        $request->refresh();
        $jobPosting = $request->jobPosting;

        $this->assertNotNull($jobPosting);
        $this->assertSame(RequestManPowerStatus::APPROVED, $request->status);
        $this->assertSame($approver->id, $request->approved_by);
        $this->assertSame($request->lokasi_penempatan, $jobPosting->location);
        $this->assertNotEmpty($jobPosting->slug);
    }

    public function test_create_job_posting_generates_unique_slug_for_same_title(): void
    {
        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'first@example.com',
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'second@example.com',
        ]));

        $firstPosting = $firstRequest->createJobPostingIfMissing();
        $secondPosting = $secondRequest->createJobPostingIfMissing();
        $repeatSecondPosting = $secondRequest->createJobPostingIfMissing();

        $this->assertNotSame($firstPosting->slug, $secondPosting->slug);
        $this->assertStringStartsWith($firstPosting->slug, $secondPosting->slug);
        $this->assertSame($secondPosting->id, $repeatSecondPosting->id);
    }

    public function test_scopes_filter_records_by_divisi_status_and_tanggal(): void
    {
        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'it.pending@example.com',
            'divisi'            => 'IT',
            'status'            => RequestManPowerStatus::PENDING,
            'tanggal_pengajuan' => '2026-03-01',
        ]));

        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'it.approved@example.com',
            'divisi'            => 'IT',
            'status'            => RequestManPowerStatus::APPROVED,
            'tanggal_pengajuan' => '2026-03-10',
        ]));

        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'finance.pending@example.com',
            'divisi'            => 'Finance',
            'status'            => RequestManPowerStatus::PENDING,
            'tanggal_pengajuan' => '2026-04-10',
        ]));

        $this->assertSame(2, RequestManPower::query()->byDivisi('IT')->count());
        $this->assertSame(2, RequestManPower::query()->byStatus(RequestManPowerStatus::PENDING->value)->count());
        $this->assertSame(2, RequestManPower::query()->byTanggal('2026-03-01', '2026-03-31')->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'email_address'              => 'requester@example.com',
            'nama_pengaju'               => 'Andi Saputra',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => '2026-03-02',
            'posisi_dibutuhkan'          => 'Software Engineer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'IT',
            'level_pekerjaan'            => 'Staff',
            'badan_usaha'                => 'PT Cesa Indonesia',
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => '2026-04-01',
            'requirements_kualifikasi'   => 'PHP, Laravel, SQL',
            'job_description'            => 'Develop internal systems',
            'status'                     => RequestManPowerStatus::PENDING,
        ], $overrides);
    }
}
