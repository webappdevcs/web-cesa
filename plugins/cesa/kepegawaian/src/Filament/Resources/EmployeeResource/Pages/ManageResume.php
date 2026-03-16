<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use Cesa\Kepegawaian\Traits\Resources\Employee\EmployeeResumeRelation;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageResume extends ManageRelatedRecords
{
    use EmployeeResumeRelation;

    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'resumes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/employee/pages/manage-resume.navigation.title');
    }
}
