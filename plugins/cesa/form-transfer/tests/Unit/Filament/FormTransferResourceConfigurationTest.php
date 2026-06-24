<?php

namespace Cesa\FormTransfer\Tests\Unit\Filament;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages\EditFormTransfer;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages\ViewFormTransfer;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ApprovalWorkflowsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\DivisionsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ReferenceNotesRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\PublicCategoryResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\PublicCategoryResource\Pages\ListPublicCategories;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Cesa\FormTransfer\Policies\FormTransferPublicCategoryPolicy;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class FormTransferResourceConfigurationTest extends FormTransferTestCase
{
    public function test_external_entry_preparation_keeps_public_fields_simple(): void
    {
        $prepared = FormTransferResource::prepareDataForPersistence([
            'name'                   => 'Google Resto',
            'public_entry_type'      => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'    => 'https://forms.gle/google-resto',
            'apps_script_web_app_url' => 'https://script.google.com/macros/s/test/exec',
            'public_badge_label'     => 'Google Form',
            'approver_mail_subject'  => 'Should be removed',
        ]);

        $this->assertSame(FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL, $prepared['public_entry_type']);
        $this->assertSame('https://forms.gle/google-resto', $prepared['public_external_url']);
        $this->assertSame('https://script.google.com/macros/s/test/exec', $prepared['apps_script_web_app_url']);
        $this->assertArrayNotHasKey('public_open_in_new_tab', $prepared);
        $this->assertArrayHasKey('uid_prefix', $prepared);
        $this->assertNotSame('', $prepared['uid_prefix']);

        foreach (array_keys(FormTransferResource::getDefaultNotificationData()) as $field) {
            $this->assertNull($prepared[$field]);
        }
    }

    public function test_internal_entry_preparation_applies_default_notification_templates(): void
    {
        $prepared = FormTransferResource::prepareDataForPersistence([
            'name'              => 'Form Internal',
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);

        $this->assertNull($prepared['public_external_url']);
        $this->assertNull($prepared['apps_script_web_app_url']);
        $this->assertArrayNotHasKey('public_open_in_new_tab', $prepared);

        foreach (FormTransferResource::getDefaultNotificationData() as $field => $value) {
            $this->assertSame($value, $prepared[$field]);
        }
    }

    public function test_form_transfer_resource_source_hides_internal_sections_for_external_entries(): void
    {
        $source = file_get_contents(base_path(
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/FormTransferResource.php'
        ));

        $this->assertIsString($source);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Builder;', $source);
        $this->assertStringContainsString('->modifyQueryUsing(fn (Builder $query): Builder => $query->with(\'publicCategories\'))', $source);
        $this->assertStringContainsString(
            '->visible(fn (Get $get): bool => static::isInternalEntry($get))',
            $source
        );
        $this->assertStringContainsString(
            '->visible(fn (FormTransfer $record): bool => ! $record->usesExternalPublicEntry())',
            $source
        );
        $this->assertStringContainsString("Select::make('publicCategories')", $source);
        $this->assertStringContainsString("TextInput::make('apps_script_web_app_url')", $source);
        $this->assertStringContainsString("Action::make('resend_external_approval')", $source);
        $this->assertStringContainsString("route('form-transfer.public.external-resend'", $source);
        $this->assertStringContainsString("'form' => \$record->code ?: \$record->getKey()", $source);
        $this->assertStringContainsString('(/form/{$record->slug})', $source);
        $this->assertStringContainsString("=> '/form/'.\$slug", $source);
        $this->assertStringNotContainsString("Toggle::make('public_open_in_new_tab')", $source);
    }

    public function test_public_category_resource_exposes_master_crud_for_public_slugs(): void
    {
        $resourceTraits = class_uses_recursive(PublicCategoryResource::class);
        $source = file_get_contents(base_path(
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/PublicCategoryResource.php'
        ));
        $pageSource = file_get_contents(base_path(
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/PublicCategoryResource/Pages/ListPublicCategories.php'
        ));

        $this->assertContains(HasResourcePermissionQuery::class, $resourceTraits);
        $this->assertIsString($source);
        $this->assertIsString($pageSource);
        $this->assertStringContainsString("TextInput::make('name')", $source);
        $this->assertStringContainsString("TextInput::make('slug')", $source);
        $this->assertStringNotContainsString("TextInput::make('sort_order')", $source);
        $this->assertStringNotContainsString("TextColumn::make('sort_order')", $source);
        $this->assertStringContainsString("=> '/form/'.\$state", $source);
        $this->assertStringContainsString('static fn (): \Closure => static function (string $attribute, mixed $value, \Closure $fail): void', $source);
        $this->assertStringContainsString("Toggle::make('is_active')", $source);
        $this->assertStringContainsString('->disabled(fn (?FormTransferPublicCategory $record): bool => (bool) $record?->isBuiltIn())', $source);
        $this->assertStringContainsString("Action::make('openPublicForm')", $source);
        $this->assertStringContainsString("route('form-transfer.public.dynamic-index'", $source);
        $this->assertStringContainsString("'publicIndexSlug' => \$record->slug", $source);
        $this->assertStringContainsString('DeleteAction::make()', $source);
        $this->assertStringContainsString('->hidden(fn (FormTransferPublicCategory $record): bool => $record->isBuiltIn())', $source);
        $this->assertStringContainsString('TrashedFilter::make()', $source);
        $this->assertStringContainsString('CreateAction::make()', $pageSource);
    }

    public function test_public_category_create_action_validates_slug_without_unresolvable_closure_parameters(): void
    {
        Filament::setCurrentPanel('admin');

        Route::get('/testing/form-transfer/configurations', fn (): string => 'form-transfer-configurations')
            ->name('filament.admin.form-transfer.configurations');
        Route::get('/testing/public-categories', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.public-categories.index');
        Route::get('/form/{publicIndexSlug}', fn (string $publicIndexSlug): string => $publicIndexSlug)
            ->name('form-transfer.public.dynamic-index');

        $user = User::factory()->create([
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);

        Permission::findOrCreate('view_any_form_transfer_public::category', 'web');
        Permission::findOrCreate('create_form_transfer_public::category', 'web');

        $user->givePermissionTo([
            'view_any_form_transfer_public::category',
            'create_form_transfer_public::category',
        ]);

        $this->actingAs($user);

        Livewire::test(ListPublicCategories::class)
            ->mountAction('create')
            ->fillForm([
                'name'        => 'Retail Modern',
                'slug'        => 'retail-modern',
                'is_active'   => true,
                'description' => 'Retail public form category.',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(FormTransferPublicCategory::class, [
            'slug'      => 'retail-modern',
            'is_active' => true,
        ]);
    }

    public function test_public_category_bulk_delete_skips_built_in_categories(): void
    {
        Filament::setCurrentPanel('admin');

        Route::get('/testing/form-transfer/configurations', fn (): string => 'form-transfer-configurations')
            ->name('filament.admin.form-transfer.configurations');
        Route::get('/testing/public-categories', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.public-categories.index');
        Route::get('/form/{publicIndexSlug}', fn (string $publicIndexSlug): string => $publicIndexSlug)
            ->name('form-transfer.public.dynamic-index');

        $user = User::factory()->create([
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);

        $permissions = [
            'view_any_form_transfer_public::category',
            'delete_form_transfer_public::category',
            'delete_any_form_transfer_public::category',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        $this->actingAs($user);

        $builtIn = FormTransferPublicCategory::query()
            ->where('slug', FormTransferPublicCategory::SLUG_TRANSFER_REQUESTS)
            ->firstOrFail();
        $deletable = FormTransferPublicCategory::factory()->create([
            'name' => 'Retail Modern',
            'slug' => 'retail-modern',
        ]);

        Livewire::test(ListPublicCategories::class)
            ->callTableBulkAction('delete', [$builtIn, $deletable]);

        $this->assertNotSoftDeleted($builtIn);
        $this->assertSoftDeleted($deletable);
    }

    public function test_public_category_create_action_rejects_unnormalized_duplicate_slug(): void
    {
        Filament::setCurrentPanel('admin');

        Route::get('/testing/form-transfer/configurations', fn (): string => 'form-transfer-configurations')
            ->name('filament.admin.form-transfer.configurations');
        Route::get('/testing/public-categories', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.public-categories.index');
        Route::get('/form/{publicIndexSlug}', fn (string $publicIndexSlug): string => $publicIndexSlug)
            ->name('form-transfer.public.dynamic-index');

        $user = User::factory()->create([
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);

        Permission::findOrCreate('view_any_form_transfer_public::category', 'web');
        Permission::findOrCreate('create_form_transfer_public::category', 'web');

        $user->givePermissionTo([
            'view_any_form_transfer_public::category',
            'create_form_transfer_public::category',
        ]);

        $this->actingAs($user);

        FormTransferPublicCategory::factory()->create([
            'name' => 'Retail Store',
            'slug' => 'retail-store',
        ]);

        Livewire::test(ListPublicCategories::class)
            ->mountAction('create')
            ->fillForm([
                'name'        => 'Retail Store',
                'slug'        => 'Retail Store',
                'is_active'   => true,
                'description' => 'Duplicate of an existing slug after normalization.',
            ])
            ->callMountedAction()
            ->assertHasFormErrors(['slug']);

        $this->assertSame(
            1,
            FormTransferPublicCategory::withTrashed()->where('slug', 'retail-store')->count(),
        );
    }

    public function test_public_category_policy_requires_public_category_permissions(): void
    {
        $user = User::factory()->create([
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $category = FormTransferPublicCategory::factory()->create([
            'creator_id' => $user->getKey(),
        ]);
        $policy = new FormTransferPublicCategoryPolicy;

        $formTransferPermissions = [
            'view_any_form_transfer_form::transfer',
            'view_form_transfer_form::transfer',
            'create_form_transfer_form::transfer',
            'update_form_transfer_form::transfer',
            'delete_form_transfer_form::transfer',
            'delete_any_form_transfer_form::transfer',
            'force_delete_form_transfer_form::transfer',
            'force_delete_any_form_transfer_form::transfer',
            'restore_form_transfer_form::transfer',
            'restore_any_form_transfer_form::transfer',
        ];

        foreach ($formTransferPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($formTransferPermissions);

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $category));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $category));
        $this->assertFalse($policy->delete($user, $category));
        $this->assertFalse($policy->deleteAny($user));
        $this->assertFalse($policy->forceDelete($user, $category));
        $this->assertFalse($policy->forceDeleteAny($user));
        $this->assertFalse($policy->restore($user, $category));
        $this->assertFalse($policy->restoreAny($user));

        $publicCategoryPermissions = [
            'view_any_form_transfer_public::category',
            'view_form_transfer_public::category',
            'create_form_transfer_public::category',
            'update_form_transfer_public::category',
            'delete_form_transfer_public::category',
            'delete_any_form_transfer_public::category',
            'force_delete_form_transfer_public::category',
            'force_delete_any_form_transfer_public::category',
            'restore_form_transfer_public::category',
            'restore_any_form_transfer_public::category',
        ];

        foreach ($publicCategoryPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($publicCategoryPermissions);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $category));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $category));
        $this->assertTrue($policy->delete($user, $category));
        $this->assertTrue($policy->deleteAny($user));
        $this->assertTrue($policy->forceDelete($user, $category));
        $this->assertTrue($policy->forceDeleteAny($user));
        $this->assertTrue($policy->restore($user, $category));
        $this->assertTrue($policy->restoreAny($user));
    }

    public function test_configuration_resources_only_use_internal_form_transfers_for_relationship_options(): void
    {
        $resourcePaths = [
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/DivisionResource.php',
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/ReferenceNoteResource.php',
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/ApprovalWorkflowResource.php',
        ];

        foreach ($resourcePaths as $resourcePath) {
            $source = file_get_contents(base_path($resourcePath));

            $this->assertIsString($source);
            $this->assertStringContainsString("Select::make('form_transfer_id')", $source);
            $this->assertStringContainsString("SelectFilter::make('form_transfer_id')", $source);
            $this->assertGreaterThanOrEqual(2, substr_count($source, '->internalEntry()'));
        }
    }

    public function test_relation_managers_are_hidden_for_external_form_transfers(): void
    {
        $internal = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);
        $external = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);

        $relationManagers = [
            DivisionsRelationManager::class,
            ReferenceNotesRelationManager::class,
            ApprovalWorkflowsRelationManager::class,
        ];

        $pages = [
            EditFormTransfer::class,
            ViewFormTransfer::class,
        ];

        foreach ($relationManagers as $relationManager) {
            foreach ($pages as $pageClass) {
                $this->assertTrue($relationManager::canViewForRecord($internal, $pageClass));
                $this->assertFalse($relationManager::canViewForRecord($external, $pageClass));
            }
        }
    }
}
