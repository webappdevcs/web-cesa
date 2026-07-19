<?php

return [
    'title'  => 'HR activities',
    'fields' => [
        'workflow'         => 'Workflow',
        'template'         => 'Workflow template',
        'type'             => 'Type',
        'status'           => 'Status',
        'tasks'            => 'Tasks',
        'due_at'           => 'Due at',
        'reference_number' => 'Reference number',
        'effective_date'   => 'Effective date',
        'expiry_date'      => 'Expiry date',
        'notes'            => 'Notes',
    ],
    'actions'       => ['start' => 'Start HR workflow'],
    'notifications' => [
        'started' => 'Workflow started for this employee',
        'failed'  => 'Workflow could not be started',
    ],
];
