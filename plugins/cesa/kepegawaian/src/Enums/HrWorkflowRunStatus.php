<?php

namespace Cesa\Kepegawaian\Enums;

enum HrWorkflowRunStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
