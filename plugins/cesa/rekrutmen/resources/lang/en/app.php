<?php

return [
    'name' => 'Recruitment',

    'navigation' => [
        'group' => 'Recruitment',
    ],

    'resources' => [
        'rekrutmen_pipeline' => [
            'navigation' => [
                'label' => 'Recruitment Pipelines',
            ],
            'model' => [
                'singular' => 'Recruitment Pipeline',
                'plural'   => 'Recruitment Pipelines',
            ],
            'form' => [
                'sections' => [
                    'pipeline_details' => 'Pipeline Details',
                    'stages'           => 'Recruitment Stages',
                ],
                'descriptions' => [
                    'stages' => 'Define the stages for this pipeline in order.',
                ],
                'fields' => [
                    'name'        => 'Name',
                    'description' => 'Description',
                ],
                'actions' => [
                    'add_stage' => 'Add Stage',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'         => 'Name',
                    'stages_count' => 'Total Stages',
                ],
            ],
        ],

        'request_man_power' => [
            'navigation' => [
                'label' => 'Manpower Requests',
            ],
            'model' => [
                'singular' => 'Manpower Request',
                'plural'   => 'Manpower Requests',
            ],
            'form' => [
                'sections' => [
                    'applicant_information' => 'Requester Information',
                    'requirement_details'   => 'Requirement Details',
                    'qualifications'        => 'Qualifications & Job Description',
                    'approval_status'       => 'Approval Status',
                ],
                'fields' => [
                    'nama_pengaju'               => 'Requester Name',
                    'posisi_pengaju'             => 'Requester Position',
                    'email_address'              => 'Requester Email',
                    'tanggal_pengajuan'          => 'Submission Date',
                    'divisi'                     => 'Division',
                    'badan_usaha'                => 'Business Entity',
                    'posisi_dibutuhkan'          => 'Requested Position',
                    'lokasi_penempatan'          => 'Placement Location',
                    'status_kebutuhan'           => 'Requirement Status',
                    'level_pekerjaan'            => 'Job Level',
                    'jumlah_karyawan_dibutuhkan' => 'Number of Employees Needed',
                    'estimasi_tanggal_join'      => 'Estimated Join Date',
                    'nama_karyawan_replacement'  => 'Employee to Be Replaced',
                    'requirements_kualifikasi'   => 'Required Qualifications',
                    'job_description'            => 'Job Description',
                    'keterangan'                 => 'Additional Notes',
                    'status'                     => 'Approval Status',
                    'approved_by'                => 'Approved By',
                ],
                'helper_texts' => [
                    'nama_karyawan_replacement' => 'For replacement requests, enter the name of the employee being replaced.',
                ],
            ],
            'table' => [
                'columns' => [
                    'nama_pengaju'              => 'Requester Name',
                    'posisi_dibutuhkan'         => 'Requested Position',
                    'divisi'                    => 'Division',
                    'status_kebutuhan'          => 'Requirement Status',
                    'nama_karyawan_replacement' => 'Replacement Employee',
                    'jumlah_karyawan_dibutuhkan'=> 'Quantity',
                    'tanggal_pengajuan'         => 'Submission Date',
                    'status'                    => 'Approval Status',
                ],
                'placeholders' => [
                    'nama_karyawan_replacement' => '-',
                ],
                'filters' => [
                    'status'           => 'Approval Status',
                    'status_kebutuhan' => 'Requirement Status',
                    'divisi'           => 'Division',
                ],
                'actions' => [
                    'approve'     => 'Approve',
                    'reject'      => 'Reject',
                    'set_pending' => 'Set Pending',
                ],
            ],
        ],

        'job_posting' => [
            'navigation' => [
                'label' => 'Job Postings',
            ],
            'model' => [
                'singular' => 'Job Posting',
                'plural'   => 'Job Postings',
            ],
            'generated_title' => 'Job Posting #:id',
            'form'            => [
                'sections' => [
                    'job_information' => 'Job Information',
                    'details'         => 'Details',
                ],
                'fields' => [
                    'request_man_power_id' => 'Related Manpower Request (Optional)',
                    'rekrutmen_pipeline_id'=> 'Recruitment Pipeline',
                    'title'                => 'Title',
                    'slug'                 => 'Slug',
                    'location'             => 'Location',
                    'closing_date'         => 'Closing Date',
                    'is_published'         => 'Published for Recruitment',
                    'description'          => 'Description',
                    'requirements'         => 'Requirements',
                ],
            ],
            'table' => [
                'columns' => [
                    'title'        => 'Title',
                    'location'     => 'Location',
                    'is_published' => 'Published',
                    'closing_date' => 'Closing Date',
                ],
                'filters' => [
                    'is_published' => 'Published',
                ],
            ],
        ],

        'job_application' => [
            'navigation' => [
                'label' => 'Job Applications',
            ],
            'model' => [
                'singular' => 'Job Application',
                'plural'   => 'Job Applications',
            ],
            'generated' => [
                'unknown_position' => 'unknown-position',
            ],
            'form' => [
                'sections' => [
                    'candidate_information' => 'Candidate Information',
                    'application_details'   => 'Application Details',
                ],
                'fields' => [
                    'job_posting_id'   => 'Job Posting',
                    'full_name'        => 'Full Name',
                    'email'            => 'Email',
                    'phone'            => 'Phone Number',
                    'portfolio_url'    => 'Portfolio URL',
                    'current_stage_id' => 'Current Stage',
                    'status'           => 'Status',
                    'resume_path'      => 'Resume',
                    'cover_letter'     => 'Cover Letter',
                ],
            ],
            'table' => [
                'columns' => [
                    'full_name'     => 'Full Name',
                    'job_posting'   => 'Applied For',
                    'email'         => 'Email',
                    'phone'         => 'Phone Number',
                    'current_stage' => 'Stage',
                    'status'        => 'Status',
                ],
                'filters' => [
                    'job_posting_id' => 'Job Posting',
                    'status'         => 'Status',
                ],
                'actions' => [
                    'change_stage'    => 'Move Stage',
                    'to_stage_id'     => 'Move to Stage',
                    'notes'           => 'Notes',
                    'download_resume' => 'Resume',
                ],
            ],
        ],
    ],

    'relation_managers' => [
        'histories' => [
            'title'   => 'History',
            'columns' => [
                'from_stage'   => 'From Stage',
                'to_stage'     => 'To Stage',
                'status'       => 'Status',
                'notes'        => 'Notes',
                'performed_by' => 'Performed By',
                'created_at'   => 'Date',
            ],
            'placeholders' => [
                'from_stage' => 'Start',
                'to_stage'   => 'N/A',
            ],
        ],
    ],

    'public_request_form' => [
        'layout' => [
            'title' => 'Manpower Request Form',
        ],
        'summary' => [
            'title'       => 'Request Submitted Successfully',
            'description' => 'Use the following summary to confirm that the submitted information is correct.',
            'fields'      => [
                'status_response_id' => 'Tracking ID',
                'posisi_dibutuhkan'  => 'Requested Position',
                'nama_pengaju'       => 'Requester Name',
                'status_kebutuhan'   => 'Requirement Status',
                'nama_replacement'   => 'Replacement Employee Name',
                'progress_url'       => 'Progress Link',
            ],
            'actions' => [
                'submit_another' => 'Submit Another Request',
            ],
        ],
        'header' => [
            'title'       => 'MANPOWER REQUEST FORM',
            'description' => 'Complete the following form to submit a manpower request.',
            'required'    => '* Required',
        ],
        'fields' => [
            'nama_pengaju'               => 'Requester Name',
            'posisi_pengaju'             => 'Requester Position / Title',
            'email_address'              => 'Requester Email',
            'tanggal_pengajuan'          => 'Submission Date',
            'divisi'                     => 'Division',
            'badan_usaha'                => 'Business Entity',
            'posisi_dibutuhkan'          => 'Requested Position',
            'lokasi_penempatan'          => 'Placement Location',
            'status_kebutuhan'           => 'Requirement Status',
            'nama_karyawan_replacement'  => 'Employee to Be Replaced',
            'level_pekerjaan'            => 'Job Level',
            'jumlah_karyawan_dibutuhkan' => 'Number of Employees Needed',
            'estimasi_tanggal_join'      => 'Estimated Join Date',
            'requirements_kualifikasi'   => 'Required Qualifications',
            'job_description'            => 'Job Description',
            'keterangan'                 => 'Additional Notes',
        ],
        'placeholders' => [
            'nama_pengaju'               => 'Requester full name',
            'posisi_pengaju'             => 'Example: HR Manager',
            'email_address'              => 'email@company.com',
            'divisi'                     => 'Example: Finance, Operations',
            'badan_usaha'                => 'Company / business entity name',
            'posisi_dibutuhkan'          => 'Example: Accounting Staff',
            'lokasi_penempatan'          => 'Example: Central Jakarta',
            'nama_karyawan_replacement'  => 'Example: Budi Santoso',
            'requirements_kualifikasi'   => 'Required education, experience, and skills...',
            'job_description'            => 'Job duties and responsibilities for the requested position...',
            'keterangan'                 => 'Additional relevant information (optional)',
        ],
        'helper_texts' => [
            'nama_karyawan_replacement' => 'For replacement requests, enter the name of the employee being replaced.',
        ],
        'actions' => [
            'submit' => 'Submit Request',
        ],
        'pagination' => [
            'single_page' => 'Page :current of :total',
        ],
        'notifications' => [
            'success' => [
                'title' => 'Success',
                'body'  => 'The manpower request has been submitted successfully!',
            ],
            'validation' => [
                'title' => 'Validation failed',
                'body'  => 'Please review the submitted data.',
            ],
        ],
        'errors' => [
            'nama_karyawan_replacement_required' => 'The replacement employee name is required.',
            'system'                             => 'A system error occurred. Please try again.',
            'recaptcha_required'                 => 'reCAPTCHA verification is required.',
            'recaptcha_failed'                   => 'reCAPTCHA verification failed. Please try again.',
        ],
    ],

    'public_progress' => [
        'heading'            => 'Manpower Request Progress',
        'subheading'         => 'Track the latest status of your manpower request on this page.',
        'page_title'         => 'MAN POWER REQUEST PROGRESS',
        'submitted_by'       => 'Submitted by',
        'current_status'     => 'Current status',
        'submission_summary' => 'Submission Summary',
        'fields'             => [
            'status_response_id'         => 'Tracking ID',
            'tanggal_pengajuan'          => 'Submission Date',
            'posisi_dibutuhkan'          => 'Requested Position',
            'status_kebutuhan'           => 'Requirement Status',
            'level_pekerjaan'            => 'Job Level',
            'jumlah_karyawan_dibutuhkan' => 'Number of Employees Needed',
            'lokasi_penempatan'          => 'Placement Location',
            'estimasi_tanggal_join'      => 'Estimated Join Date',
            'nama_karyawan_replacement'  => 'Employee to Be Replaced',
            'requirements_kualifikasi'   => 'Required Qualifications',
            'job_description'            => 'Job Description',
            'keterangan'                 => 'Additional Notes',
        ],
    ],

    'application_form' => [
        'fields' => [
            'full_name'       => 'Full Name',
            'email'           => 'Email',
            'phone'           => 'Phone Number',
            'portfolio_url'   => 'Portfolio URL',
            'cover_letter'    => 'Cover Letter',
            'resume'          => 'Resume',
            'github_url'      => 'GitHub URL',
            'expected_salary' => 'Expected Salary',
        ],
    ],

    'api' => [
        'messages' => [
            'job_listed'            => 'Job listings retrieved successfully.',
            'job_not_found'         => 'Job posting not found.',
            'job_detail_retrieved'  => 'Job detail retrieved successfully.',
            'job_not_open'          => 'Job posting not found or is no longer open.',
            'application_submitted' => 'Application submitted successfully.',
        ],
        'validation' => [
            'messages' => [
                'full_name.required'       => 'The full name field is required.',
                'email.required'           => 'The email field is required.',
                'email.email'              => 'The email format is invalid.',
                'phone.required'           => 'The phone number field is required.',
                'portfolio_url.url'        => 'The portfolio URL format is invalid.',
                'resume.mimes'             => 'The resume file must be a pdf, doc, or docx file.',
                'resume.max'               => 'The resume file may not be greater than 5 MB.',
                'additional_answers.array' => 'Additional answers must be provided as an array.',
                'required'                 => 'The :attribute field is required.',
            ],
            'attributes' => [
                'full_name'      => 'full name',
                'email'          => 'email',
                'phone'          => 'phone number',
                'portfolio_url'  => 'portfolio URL',
                'resume'         => 'resume',
                'cover_letter'   => 'cover letter',
            ],
        ],
        'application' => [
            'additional_answers_prefix' => 'Additional Answers:',
            'submitted_via_public_api'  => 'Application submitted via public API.',
        ],
    ],

    'enums' => [
        'status_kebutuhan' => [
            'new_hiring'  => 'New Hiring',
            'replacement' => 'Replacement',
        ],
        'request_man_power_status' => [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'job_application_status' => [
            'in_progress' => 'In Progress',
            'hired'       => 'Hired',
            'rejected'    => 'Rejected',
            'withdrawn'   => 'Withdrawn',
        ],
        'level_pekerjaan' => [
            'staff'       => 'Staff',
            'leader'      => 'Leader',
            'coordinator' => 'Coordinator',
            'manager'     => 'Manager',
        ],
    ],

    'mail' => [
        'request_man_power_submitted' => [
            'subject'            => 'Manpower request submitted',
            'greeting'           => 'Hello :name,',
            'body'               => 'Your manpower request has been received.',
            'position'           => 'Position: :value',
            'requirement_status' => 'Requirement status: :value',
            'submission_id'      => 'Request ID: #:id',
            'view_progress'      => 'View Submission Progress',
        ],
        'request_man_power_status_changed' => [
            'subject'         => 'Manpower request status updated',
            'greeting'        => 'Hello :name,',
            'body'            => 'Your manpower request status has been updated.',
            'position'        => 'Position: :value',
            'latest_status'   => 'Latest status: :value',
            'previous_status' => 'Previous status: :value',
            'submission_id'   => 'Request ID: #:id',
            'view_progress'   => 'View Submission Progress',
        ],
    ],

    'common' => [
        'not_available' => '—',
    ],
];
