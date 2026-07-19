<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncConflictResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncRunResource;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Policies\EmployeeSyncConflictPolicy;
use Cesa\Kepegawaian\Policies\EmployeeSyncRunPolicy;
use Tests\TestCase;
use Webkul\Security\Models\User;

class EmployeeSyncFilamentTest extends TestCase
{
    public function test_sync_audit_and_conflict_resources_are_bounded_to_list_and_view_pages(): void
    {
        $this->assertSame(EmployeeSyncRun::class, EmployeeSyncRunResource::getModel());
        $this->assertSame(EmployeeSyncConflict::class, EmployeeSyncConflictResource::getModel());
        $this->assertSame(['index', 'view'], array_keys(EmployeeSyncRunResource::getPages()));
        $this->assertSame(['index', 'view'], array_keys(EmployeeSyncConflictResource::getPages()));
        $this->assertFalse(EmployeeSyncRunResource::canCreate());
        $this->assertFalse(EmployeeSyncConflictResource::canCreate());
    }

    public function test_sync_resources_use_dedicated_least_privilege_permissions(): void
    {
        $user = new class extends User
        {
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };

        $runPolicy = new EmployeeSyncRunPolicy;
        $conflictPolicy = new EmployeeSyncConflictPolicy;

        $user->abilities = ['view_any_kepegawaian_employee::sync::run'];
        $this->assertTrue($runPolicy->viewAny($user));
        $this->assertFalse($runPolicy->create($user));

        $user->abilities = ['view_any_kepegawaian_employee::sync::conflict'];
        $this->assertTrue($conflictPolicy->viewAny($user));
        $this->assertFalse($conflictPolicy->update($user, new EmployeeSyncConflict));

        $user->abilities[] = 'update_kepegawaian_employee::sync::conflict';
        $this->assertTrue($conflictPolicy->update($user, new EmployeeSyncConflict));
        $this->assertFalse($conflictPolicy->delete($user, new EmployeeSyncConflict));
    }

    public function test_shield_configuration_does_not_generate_sync_mutation_permissions(): void
    {
        $shield = require base_path('plugins/cesa/kepegawaian/config/filament-shield.php');
        $resources = $shield['resources']['manage'];

        $this->assertSame(
            ['view_any', 'view'],
            $resources[EmployeeSyncRunResource::class]
        );
        $this->assertSame(
            ['view_any', 'view', 'update'],
            $resources[EmployeeSyncConflictResource::class]
        );
    }

    public function test_sync_review_interface_is_translated_without_exposing_raw_payload_labels(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $runTitle = __('kepegawaian::filament/resources/employee-sync-run.title');
            $conflictTitle = __('kepegawaian::filament/resources/employee-sync-conflict.title');
            $link = __('kepegawaian::filament/resources/employee-sync-conflict.actions.link');

            $this->assertStringNotContainsString('kepegawaian::', $runTitle);
            $this->assertStringNotContainsString('kepegawaian::', $conflictTitle);
            $this->assertStringNotContainsString('kepegawaian::', $link);
        }
    }
}
