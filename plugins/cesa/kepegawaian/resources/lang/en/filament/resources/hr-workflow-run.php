<?php

return [
    'title'        => 'employee workflow',
    'plural_title' => 'employee workflows',
    'navigation'   => 'Employee workflows',
    'sections'     => ['summary' => 'Workflow summary'],
    'fields'       => [
        'employee'            => 'Employee',
        'workflow'            => 'Workflow',
        'type'                => 'Type',
        'progress'            => 'Progress',
        'status'              => 'Status',
        'due_at'              => 'Due at',
        'started_at'          => 'Started at',
        'started_by'          => 'Started by',
        'completed_at'        => 'Completed at',
        'cancellation_reason' => 'Cancellation reason',
        'reference_number'    => 'Reference number',
        'effective_date'      => 'Effective date',
        'expiry_date'         => 'Expiry date',
        'notes'               => 'Notes',
    ],
    'statuses' => [
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ],
    'actions'       => ['cancel' => 'Cancel workflow'],
    'notifications' => ['cancelled' => 'Workflow cancelled'],
];
