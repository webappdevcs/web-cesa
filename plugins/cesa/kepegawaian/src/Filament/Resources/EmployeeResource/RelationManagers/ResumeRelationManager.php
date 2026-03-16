<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers;

use Cesa\Kepegawaian\Traits\Resources\Employee\EmployeeResumeRelation;
use Filament\Resources\RelationManagers\RelationManager;

class ResumeRelationManager extends RelationManager
{
    use EmployeeResumeRelation;

    protected static string $relationship = 'resumes';

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('kepegawaian::filament/resources/employee/relation-manager/resume.title');
    }
}
