<?php

return [
    'title'        => 'Employee sync audit',
    'plural_title' => 'Employee sync audits',
    'navigation'   => 'Data sync audits',
    'sections'     => [
        'source' => 'Source and execution',
        'counts' => 'Reconciliation summary',
    ],
    'fields' => [
        'uuid'               => 'Run UUID',
        'source_system'      => 'Source system',
        'source_instance'    => 'Source instance',
        'mode'               => 'Mode',
        'status'             => 'Status',
        'file_name'          => 'Source file name',
        'file_checksum'      => 'SHA-256 checksum',
        'initiator'          => 'Initiated by',
        'started_at'         => 'Started at',
        'completed_at'       => 'Completed at',
        'total_records'      => 'Total records',
        'matched_count'      => 'Exact matches',
        'linked_count'       => 'Linked',
        'created_count'      => 'Created inactive',
        'would_link_count'   => 'Candidate links',
        'would_create_count' => 'Candidate creations',
        'conflict_count'     => 'Conflicts',
        'invalid_count'      => 'Invalid records',
    ],
    'values' => [
        'dry_run'   => 'Dry run',
        'commit'    => 'Commit',
        'running'   => 'Running',
        'completed' => 'Completed',
        'failed'    => 'Failed',
    ],
];
