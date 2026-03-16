<?php

namespace Cesa\Kepegawaian\Filament\Resources\DepartmentResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Cesa\Kepegawaian\Traits\Resources\Department\DepartmentEmployee;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageEmployee extends ManageRelatedRecords
{
    use DepartmentEmployee;

    protected static string $resource = DepartmentResource::class;

    protected static string $relationship = 'employees';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/department/pages/manage-employee.navigation.title');
    }
}
