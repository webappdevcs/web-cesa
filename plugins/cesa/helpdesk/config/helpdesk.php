<?php

return [
    'attachments' => [
        'ticket' => [
            'disk'      => env('HELPDESK_TICKET_ATTACHMENT_DISK', 'public'),
            'directory' => env('HELPDESK_TICKET_ATTACHMENT_DIRECTORY', 'helpdesk/tickets'),
            'visibility'=> env('HELPDESK_TICKET_ATTACHMENT_VISIBILITY', 'public'),
            'max_size'  => (int) env('HELPDESK_TICKET_ATTACHMENT_MAX_SIZE', 10240),
            'max_files' => (int) env('HELPDESK_TICKET_ATTACHMENT_MAX_FILES', 5),
        ],
        'comment' => [
            'disk'      => env('HELPDESK_COMMENT_ATTACHMENT_DISK', 'public'),
            'directory' => env('HELPDESK_COMMENT_ATTACHMENT_DIRECTORY', 'helpdesk/comments'),
            'visibility'=> env('HELPDESK_COMMENT_ATTACHMENT_VISIBILITY', 'public'),
            'max_size'  => (int) env('HELPDESK_COMMENT_ATTACHMENT_MAX_SIZE', 10240),
            'max_files' => (int) env('HELPDESK_COMMENT_ATTACHMENT_MAX_FILES', 5),
        ],
    ],
];
