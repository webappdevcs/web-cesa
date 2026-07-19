<?php

return [
    'title'        => 'Employee sync conflict',
    'plural_title' => 'Employee sync conflicts',
    'navigation'   => 'Sync conflict review',
    'sections'     => [
        'review'      => 'Conflict review',
        'review_help' => 'Raw vendor payload and internal error details are intentionally not displayed here.',
    ],
    'fields' => [
        'type'              => 'Conflict type',
        'status'            => 'Status',
        'source_system'     => 'Source system',
        'source_instance'   => 'Source instance',
        'run_uuid'          => 'Run UUID',
        'run_mode'          => 'Run mode',
        'row_number'        => 'Source row',
        'external_id'       => 'External record ID',
        'employee_code'     => 'Source employee code',
        'canonical_employee'=> 'Canonical employee',
        'created_at'        => 'Detected at',
        'resolution'        => 'Resolution',
        'resolver'          => 'Resolved by',
        'resolved_at'       => 'Resolved at',
        'resolution_notes'  => 'Resolution notes',
        'rejection_reason'  => 'Rejection reason',
    ],
    'values' => [
        'open'     => 'Open',
        'resolved' => 'Resolved',
    ],
    'types' => [
        'invalid_record'                    => 'Invalid record',
        'missing_external_id'               => 'Missing external ID',
        'identifier_employee_code_mismatch' => 'Identifier and employee code disagree',
        'employee_code_change'              => 'Employee code changed',
        'retired_employee_match'            => 'Matched a retired employee',
    ],
    'actions' => [
        'recheck'       => 'Recheck canonical identity',
        'reject_source' => 'Reject source record',
    ],
    'rejection_reasons' => [
        'duplicate_source_record' => 'Duplicate source record',
        'invalid_source_record'   => 'Invalid source record',
        'out_of_scope'            => 'Outside employee master scope',
    ],
    'notifications' => [
        'rechecked'       => 'The conflict is now unambiguous and has been resolved.',
        'still_ambiguous' => 'The canonical identity is still ambiguous. No data was changed.',
        'rejected'        => 'The source record was rejected and the decision was audited.',
        'failed'          => 'The conflict could not be resolved. No canonical data was changed.',
    ],
];
