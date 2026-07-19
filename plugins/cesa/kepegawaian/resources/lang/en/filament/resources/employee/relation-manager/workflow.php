<?php

return [
    'title'  => 'HR activities',
    'fields' => [
        'workflow' => 'Workflow',
        'template' => 'Workflow template',
        'type'     => 'Type',
        'status'   => 'Status',
        'tasks'    => 'Tasks',
        'due_at'   => 'Due at',
    ],
    'actions'       => ['start' => 'Start HR workflow'],
    'notifications' => [
        'started' => 'Workflow started for this employee',
        'failed'  => 'Workflow could not be started',
    ],
];
