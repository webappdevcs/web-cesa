<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Kepegawaian\Database\Seeders\DefaultHrWorkflowTemplateSeeder;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeLifecycleBridge
{
    public function __construct(private readonly HrWorkflowService $workflowService) {}

    /**
     * @param  array{name: string, email?: string|null, phone?: string|null, job_title?: string|null, birth_date?: string|null, gender?: string|null}  $candidate
     */
    public function hireFromRecruitment(array $candidate, int $sourceId, ?User $actor = null): HrWorkflowRun
    {
        $sourceKey = "rekrutmen:{$sourceId}:onboarding";

        if ($existing = HrWorkflowRun::query()->where('source_key', $sourceKey)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($candidate, $sourceId, $actor, $sourceKey): HrWorkflowRun {
            $identifier = EmployeeIdentifier::query()
                ->where('source_system', 'cesa_rekrutmen')
                ->where('source_instance', 'production')
                ->where('identifier_type', 'job_application_id')
                ->where('normalized_value', (string) $sourceId)
                ->first();

            $employee = $identifier?->employee;
            $email = $this->normalizeEmail($candidate['email'] ?? null);

            if (! $employee && $email !== null) {
                $employee = Employee::query()
                    ->whereRaw('LOWER(private_email) = ?', [$email])
                    ->orWhereRaw('LOWER(work_email) = ?', [$email])
                    ->first();
            }

            $employee ??= Employee::query()->create([
                'name'          => $candidate['name'],
                'employee_code' => $this->uniqueRecruitmentEmployeeCode($sourceId),
                'private_email' => $email,
                'mobile_phone'  => $candidate['phone'] ?? null,
                'job_title'     => $candidate['job_title'] ?? null,
                'birthday'      => $candidate['birth_date'] ?? null,
                'gender'        => $candidate['gender'] ?? null,
                'creator_id'    => $actor?->getKey(),
                'is_active'     => true,
            ]);

            EmployeeIdentifier::query()->firstOrCreate([
                'source_system'   => 'cesa_rekrutmen',
                'source_instance' => 'production',
                'identifier_type' => 'job_application_id',
                'external_id'     => (string) $sourceId,
            ], [
                'employee_id' => $employee->getKey(),
                'metadata'    => ['candidate_email' => $email],
                'verified_at' => now(),
                'last_seen_at'=> now(),
                'creator_id'  => $actor?->getKey(),
            ]);

            $template = HrWorkflowTemplate::query()
                ->where('code', DefaultHrWorkflowTemplateSeeder::ONBOARDING_CODE)
                ->where('is_active', true)
                ->firstOrFail();

            return $this->workflowService->start($template, $employee, $actor, [
                'source'             => 'rekrutmen',
                'source_key'         => $sourceKey,
                'job_application_id' => $sourceId,
            ]);
        });
    }

    /**
     * @param  array{name: string, email?: string|null, departure_date?: string|null, reason?: string|null, form_uid?: string|null}  $request
     */
    public function startOffboardingFromExit(array $request, int $sourceId, ?User $actor = null): HrWorkflowRun
    {
        $sourceKey = "exit_clearance:{$sourceId}:offboarding";

        if ($existing = HrWorkflowRun::query()->where('source_key', $sourceKey)->first()) {
            return $existing;
        }

        $employee = $this->resolveExitEmployee($request);
        $template = HrWorkflowTemplate::query()
            ->where('code', DefaultHrWorkflowTemplateSeeder::OFFBOARDING_CODE)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->workflowService->start($template, $employee, $actor, [
            'source'                            => 'exit_clearance',
            'source_key'                        => $sourceKey,
            'exit_clearance_request_id'         => $sourceId,
            'exit_clearance_uid'                => $request['form_uid'] ?? null,
            'departure_date'                    => $request['departure_date'] ?? null,
            'departure_reason'                  => $request['reason'] ?? null,
            'deactivate_employee_on_completion' => true,
        ]);
    }

    /**
     * @param  array{name: string, email?: string|null}  $request
     */
    private function resolveExitEmployee(array $request): Employee
    {
        $email = $this->normalizeEmail($request['email'] ?? null);

        if ($email === null) {
            throw new LogicException('An approved exit request must contain an employee email.');
        }

        $matches = Employee::query()
            ->whereRaw('LOWER(private_email) = ?', [$email])
            ->orWhereRaw('LOWER(work_email) = ?', [$email])
            ->get();

        if ($matches->count() !== 1) {
            throw new LogicException('The approved exit request could not be matched to exactly one employee.');
        }

        return $matches->firstOrFail();
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (blank($email)) {
            return null;
        }

        return Str::lower(trim($email));
    }

    private function uniqueRecruitmentEmployeeCode(int $sourceId): string
    {
        $base = 'REC-'.now()->format('Y').'-'.str_pad((string) $sourceId, 6, '0', STR_PAD_LEFT);
        $code = $base;
        $suffix = 1;

        while (Employee::query()->where('employee_code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }

        return $code;
    }
}
