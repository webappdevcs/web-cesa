<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\DefaultHrWorkflowTemplateSeeder;
use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;

class DefaultHrWorkflowTemplateSeederTest extends KepegawaianIdentityTestCase
{
    public function test_it_installs_excel_derived_onboarding_and_offboarding_templates_idempotently(): void
    {
        $seeder = app(DefaultHrWorkflowTemplateSeeder::class);

        $seeder->run();

        $onboarding = HrWorkflowTemplate::query()
            ->where('code', DefaultHrWorkflowTemplateSeeder::ONBOARDING_CODE)
            ->firstOrFail();
        $offboarding = HrWorkflowTemplate::query()
            ->where('code', DefaultHrWorkflowTemplateSeeder::OFFBOARDING_CODE)
            ->firstOrFail();

        $this->assertSame(HrWorkflowType::Onboarding, $onboarding->type);
        $this->assertSame(HrWorkflowType::Offboarding, $offboarding->type);
        $this->assertSame(30, $onboarding->steps()->count());
        $this->assertSame(14, $offboarding->steps()->count());
        $this->assertSame(
            ['Recruitment', 'Personalia', 'Training', 'GA', 'Busdev', 'HR Manager'],
            $onboarding->steps()->orderBy('sort_order')->pluck('department')->unique()->values()->all(),
        );
        $this->assertSame(
            ['Personalia', 'GA', 'Busdev'],
            $offboarding->steps()->orderBy('sort_order')->pluck('department')->unique()->values()->all(),
        );
        $this->assertTrue(
            $onboarding->steps()->where('name', 'TTD Kontrak Kerja')->where('requires_evidence', true)->exists(),
        );
        $this->assertTrue(
            $offboarding->steps()->where('name', 'Exit Clearance Form')->where('requires_evidence', true)->exists(),
        );

        $templateIds = HrWorkflowTemplate::query()->orderBy('id')->pluck('id')->all();

        $seeder->run();

        $this->assertSame(2, HrWorkflowTemplate::query()->count());
        $this->assertSame(44, HrWorkflowTemplate::query()->withCount('steps')->get()->sum('steps_count'));
        $this->assertSame($templateIds, HrWorkflowTemplate::query()->orderBy('id')->pluck('id')->all());
    }
}
