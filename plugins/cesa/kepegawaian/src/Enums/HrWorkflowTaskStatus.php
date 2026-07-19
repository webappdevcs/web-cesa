<?php

namespace Cesa\Kepegawaian\Enums;

enum HrWorkflowTaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}
