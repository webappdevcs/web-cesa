<?php

return [
    'title'          => 'External Identifiers',
    'canonical_uuid' => 'Canonical UUID',
    'form'           => [
        'source_system'      => 'Source System',
        'source_system_help' => 'Examples: talenta, hr_workbook, legacy_web_cesa.',
        'source_instance'    => 'Source Instance',
        'identifier_type'    => 'Identifier Type',
        'external_id'        => 'External ID',
    ],
    'table' => [
        'source_system'   => 'Source System',
        'source_instance' => 'Source Instance',
        'identifier_type' => 'Identifier Type',
        'external_id'     => 'External ID',
        'verified_at'     => 'Verified At',
        'last_seen_at'    => 'Last Seen At',
        'retired_at'      => 'Retired At',
    ],
    'status' => [
        'current' => 'Current',
    ],
    'actions' => [
        'create'     => 'Add Identifier',
        'verify'     => 'Verify',
        'retire'     => 'Retire',
        'reactivate' => 'Reactivate',
    ],
    'notifications' => [
        'verified'    => 'Identifier verified.',
        'retired'     => 'Identifier retired.',
        'reactivated' => 'Identifier reactivated.',
    ],
];
