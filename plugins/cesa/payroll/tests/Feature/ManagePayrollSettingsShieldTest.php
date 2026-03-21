<?php

namespace Cesa\Payroll\Tests\Feature;

use Cesa\Payroll\Filament\Pages\ManagePayrollSettings;
use Cesa\Payroll\Tests\PayrollTestCase;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Security\Models\Permission;
use Webkul\Security\Models\User;

class ManagePayrollSettingsShieldTest extends PayrollTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_04_132945_create_permission_tables.php',
            '--realpath' => false,
        ]);
    }

    public function test_manage_payroll_settings_requires_generated_shield_permission(): void
    {
        Filament::setCurrentPanel('admin');

        $permission = 'page_payroll_manage_payroll_settings';
        Permission::query()->firstOrCreate([
            'name'       => $permission,
            'guard_name' => 'web',
        ]);

        $user = User::withoutEvents(fn (): User => User::factory()->create());

        $this->actingAs($user);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse(ManagePayrollSettings::canAccess());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(ManagePayrollSettings::canAccess());
    }
}
