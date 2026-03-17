<?php

namespace Cesa\Shelf\Models;

use Webkul\Employee\Models\EmployeeJobPosition as BaseJobTitle;

class JobTitle extends BaseJobTitle
{
    protected $table = 'employees_job_positions';

    public function getTitleAttribute(): ?string
    {
        return $this->name;
    }
}
