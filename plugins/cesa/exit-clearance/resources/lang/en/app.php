<?php

return [
    // Navigation (admin.php content merged)
    'navigation' => [
        'exit-clearance'   => 'Exit Clearance',
        'request'          => 'Exit Clearance Request|Exit Clearance Requests',
    ],

    // Resource labels (admin.php content merged)
    'resources' => [
        'Ec Request'   => 'Exit Clearance Request',
        'department'   => 'Department|Departments',
        'approver'     => 'Approver|Approvers',
    ],

    'config' => [
        'navigation' => [
            'label' => 'Settings',
        ],
    ],

    'public' => [
        'form' => [
            'success_message'     => 'Your response has been recorded.',
            'success_title'       => 'Submission Successfully Sent',
            'success_description' => 'Save the following information to track your submission progress.',
            'form_label'          => 'Form',
            'uid_label'           => 'UID:',
            'response_id_label'   => 'Response ID:',
            'submit_another'      => 'Submit another request',
            'page_title'          => 'Exit Clearance Form',
            'page_description'    => 'Please complete the following data to submit your exit clearance request. Make sure all information you enter is valid to expedite the administrative process.',
            'required_note'       => '* Required',
            'next'                => 'Next',
            'submit'              => 'Submit',
            'back'                => 'Back',
            'page_of'             => 'Page :current of :total',
            'validation_title'    => 'Validation failed.',
            'validation_body'     => 'Please review the form data and try again.',
            'recaptcha_required'  => 'reCAPTCHA verification is required.',
            'recaptcha_failed'    => 'reCAPTCHA verification failed. Please try again.',
            'placeholders'        => [
                'answer' => 'Your answer',
                'choose' => 'Choose',
                'date'   => 'YYYY-MM-DD',
            ],
        ],

        'progress' => [
            'heading'             => 'Exit Clearance Progress',
            'subheading'          => 'Monitor your submission status.',
            'page_title'          => 'Exit Clearance Progress',
            'submitted_by'        => 'Submitted by',
            'current_status'      => 'Current status',
            'submission_summary'  => 'Submission Summary',
            'personal_data'       => 'Personal Data',
            'questionnaire'       => 'Questionnaire',
            'clearance'           => 'Clearance',
            'approval_flow'       => 'Approval Flow',
            'view_attachment'     => 'View attachment',
            'notes'               => 'Notes',
            'process_time'        => 'Processing time:',
        ],

        'approval' => [
            'heading'              => 'Exit Clearance Approval',
            'subheading'           => 'Submission for :name',
            'subheading_default'   => 'Exit Clearance Submission.',
            'notes_label'          => 'Notes (optional)',
            'cannot_process'       => 'Action cannot be processed.',
            'approved_success'     => 'Submission successfully approved.',
            'rejected_success'     => 'Submission successfully rejected.',
            'page_title'           => 'Approval Request',
            'please_review'        => 'Please review the submission by',
            'submission_status'    => 'Submission status',
            'your_approval_status' => 'Your approval status',
            'action'               => 'Action',
            'reject'               => 'Reject',
            'approve'              => 'Approve',
            'information'          => 'Information',
            'already_processed'    => 'This stage has been processed. You may close this page.',
        ],
    ],

    'form' => [
        'step' => [
            'resignation_letter' => 'Resignation Letter',
            'personal_data'      => 'Personal Data',
            'exit_interview'     => 'Exit Interview',
            'exit_clearance'     => 'Exit Clearance',
        ],

        'resignation_letter' => [
            'info'         => 'Resignation Letter',
            'not_required' => 'Not required if the employee\'s contract has expired',
        ],

        'fields' => [
            'name'           => 'Full Name',
            'email'          => 'Email Address',
            'phone'          => 'Phone Number',
            'position'       => 'Position / Job Title',
            'placement'      => 'Placement',
            'department'     => 'Department',
            'join_date'      => 'Employment Start Date',
            'departure_date' => 'Employment End Date',
        ],

        'file_upload' => [
            'label'        => 'Upload File',
            'helper_text'  => 'Allowed formats: PDF, JPG, PNG. Maximum 10MB.',
        ],

        'exit_interview' => [
            'q1' => '1. What is your reason for submitting a resignation request?',
            'q2' => '2. Please explain how you feel about the workload assigned to you from the start of your employment until now.',
            'q3' => '3. Please explain your career progression during your time at this company.',
            'q4' => '4. How would you rate the company\'s attention to work support, welfare, and facilities provided to you?',
            'q5' => '5. How is your working relationship in this company\'s work environment?',
            'q6' => '6. How would you rate the compensation you currently receive from the company?',
            'q7' => '7. Please share your thoughts on the Division where you are assigned, as input for us.',
            'q8' => '8. Please share your thoughts on this company, as input for us.',
        ],

        'clearance' => [
            'section_title' => 'Exit Clearance',
            'item_1'        => '1. Halo card and bills',
            'item_2'        => '2. Employee debt',
            'item_3'        => '3. Uniform return',
            'item_4'        => '4. Vehicle return',
            'item_5'        => '5. Inventory return',
            'item_6'        => '6. Account deactivation',
            'item_7'        => '7. Receivable data',
            'item_8'        => '8. Internal promoter',
            'item_9'        => '9. Pending notes',
            'item_10'       => '10. Stock opname',
        ],

        'approvals' => [
            'section_title' => 'Approvals',
        ],

        'metadata' => [
            'section_title' => 'Metadata',
            'form_uid'      => 'Form UID',
            'form_status'   => 'Form Status',
            'form_response' => 'Form Response ID',
        ],

        'infolist' => [
            'employee_info'  => 'Employee Information',
            'request_status' => 'Request Snapshot',
            'approval_chain' => 'Approval Chain',
        ],

        'infolist_fields' => [
            'name'           => 'Name',
            'email'          => 'Email',
            'phone'          => 'Phone',
            'department'     => 'Department',
            'position'       => 'Position',
            'placement'      => 'Placement',
            'joined'         => 'Joined',
            'departing'      => 'Departing',
            'surat_resign'   => 'Resignation Letter',
            'no_file'        => 'No file',
            'uid'            => 'UID',
            'status'         => 'Status',
            'request_date'   => 'Request Date',
            'title'          => 'Title',
        ],

        'table' => [
            'uid'                  => 'UID',
            'employee_name'        => 'Employee Name',
            'email'                => 'Email',
            'position'             => 'Position',
            'placement'            => 'Placement',
            'status'               => 'Status',
            'join_date'            => 'Join Date',
            'request_date'         => 'Request Date',
            'departure_date'       => 'Departure Date',
            'reason'               => 'Reason',
            'resignation_letter'   => 'Resignation Letter',
            'department'           => 'Department',
            'approvers'            => 'Approvers',
        ],

        'filters' => [
            'department'   => 'Department',
            'request_date' => 'Request Date',
        ],

        'department' => [
            'code'        => 'Code',
            'name'        => 'Name',
            'description' => 'Description',
            'approvers'   => 'Approvers',
        ],

        'approver' => [
            'name'        => 'Name',
            'email'       => 'Email',
            'phone'       => 'Phone',
            'title'       => 'Title',
            'departments' => 'Departments',
        ],
    ],

];
