<?php

return [
    'title'  => 'Operational tasks',
    'fields' => [
        'name'       => 'Task',
        'department' => 'Department',
        'assignee'   => 'PIC',
        'due_at'     => 'Due at',
        'evidence'   => 'Evidence',
        'status'     => 'Status',
        'note'       => 'Completion note',
        'reason'     => 'Reason',
    ],
    'actions' => [
        'assign'   => 'Assign PIC',
        'begin'    => 'Start work',
        'complete' => 'Complete',
        'skip'     => 'Skip',
    ],
    'notifications' => [
        'assigned'  => 'PIC assigned',
        'started'   => 'Task started',
        'completed' => 'Task completed',
        'skipped'   => 'Task skipped',
    ],
];
