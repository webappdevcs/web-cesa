<?php

return [
    'title'        => 'HR workflow template',
    'plural_title' => 'HR workflow templates',
    'navigation'   => 'Workflow templates',
    'sections'     => [
        'identity'   => 'Template information',
        'steps'      => 'Operational steps',
        'steps_hint' => 'Define the task order, owner, deadline, and required evidence.',
    ],
    'fields' => [
        'name'               => 'Name',
        'code'               => 'Code',
        'type'               => 'Workflow type',
        'description'        => 'Description',
        'is_active'          => 'Active',
        'step_name'          => 'Task',
        'department'         => 'Responsible department',
        'due_days'           => 'Due after (days)',
        'default_assignee'   => 'Default PIC',
        'is_required'        => 'Required task',
        'requires_evidence'  => 'Requires evidence',
        'step_count'         => 'Tasks',
    ],
    'actions' => ['add_step' => 'Add task'],
    'types'   => [
        'onboarding'       => 'Onboarding',
        'offboarding'      => 'Offboarding',
        'contract_renewal' => 'Contract renewal',
        'disciplinary'     => 'Disciplinary action',
        'training'         => 'Training',
        'offering'         => 'Offering',
        'custom'           => 'Custom',
    ],
];
