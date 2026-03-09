<?php

return [
    'index' => [
        'heading'     => 'Transfer Request Forms',
        'description' => 'Submit and track transfer requests using the available forms.',
    ],

    'form' => [
        'heading'       => 'Transfer Request - :form',
        'description'   => 'Fill out the information below to submit a transfer request for :form.',
        'submit'        => 'Submit Request',
        'placeholders'  => [
            'email'           => 'Enter email',
            'requester_name'  => 'Enter requester name',
            'account_number'  => 'Enter account number',
            'account_name'    => 'Enter account holder name',
            'transfer_amount' => 'Example: 1000000',
            'purpose'         => 'Describe the transfer purpose',
            'reference_note'  => 'Enter reference note',
        ],
        'account_validation' => [
            'action'       => 'Check Account',
            'hint'         => 'Click Check Account to validate the account number.',
            'hint_manual'  => 'Click Check Account to validate the account number. The account name remains manual.',
            'success'      => 'Account verified.',
            'not_found'    => 'Account not found. Please check the bank and account number.',
            'failed'       => 'Account validation failed. Please try again.',
            'rate_limited' => 'Too many validation attempts. Please try again later.',
        ],
        'notifications' => [
            'success' => [
                'title' => 'Transfer Request Submitted',
                'body'  => 'Your request has been submitted successfully. Reference: :uid.',
            ],
            'error' => [
                'title' => 'Submission Failed',
                'body'  => 'We could not submit your request. Please try again later.',
            ],
            'validation' => [
                'title' => 'Validation Error',
                'body'  => 'Please review the highlighted fields and try again.',
            ],
            'rate_limit' => [
                'title' => 'Slow Down',
                'body'  => 'Too many requests detected. Please wait :seconds seconds before trying again.',
            ],
        ],
        'actions' => [
            'heading'               => 'Transfer Request Approval - :form',
            'subheading'            => 'Requester: :requester',
            'comments'              => 'Comments',
            'comments_placeholder'  => 'Provide optional comments for this decision.',
            'approved'              => 'Approval recorded successfully.',
            'rejected'              => 'Rejection recorded successfully.',
            'invalid_state'         => 'This approval task can no longer be processed.',
            'already_processed_body' => 'This request has already been processed and cannot be modified.',
            'rate_limit'            => [
                'title' => 'Too Many Attempts',
                'body'  => 'Please wait :seconds seconds before trying again.',
            ],
        ],
    ],

    'progress' => [
        'heading'            => 'Transfer Request Progress',
        'description'        => 'Enter your reference to check the current status of your transfer request.',
        'attn'               => 'Attn:',
        'current_status'     => 'Current Status',
        'submission_summary' => 'SUBMISSION SUMMARY',
        'approval_flow'      => 'APPROVAL FLOW',
    ],

    'submission' => [
        'success_title'         => 'Submission Successfully Sent',
        'success_description'   => 'Save the following :reference_label to track the process: :uid.',
        'form_label'            => 'Transfer Form',
        'reference_id_label'    => 'Reference ID',
        'status_response_label' => 'Status Response ID',
        'submit_another'        => 'Submit another request',
        'required_hint'         => '* Required',
        'page_of'               => 'Page :current of :total',
    ],

    'approval' => [
        'submission_status_label' => 'Submission status',
        'your_approval_status'    => 'Your approval status',
        'submission_summary'      => 'SUBMISSION SUMMARY',
        'approval_flow'           => 'APPROVAL FLOW',
        'actions'                 => 'ACTIONS',
        'reject'                  => 'Reject',
        'approve'                 => 'Approve',
        'information'             => 'INFORMATION',
        'completed_info'          => 'This stage has been processed. You may close this page.',
    ],
];
