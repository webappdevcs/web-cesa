<?php

return [
    'application_form' => [
        'default_fields' => [
            [
                'name'     => 'full_name',
                'label'    => 'rekrutmen::app.application_form.fields.full_name',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'email',
                'label'    => 'rekrutmen::app.application_form.fields.email',
                'type'     => 'email',
                'required' => true,
            ],
            [
                'name'     => 'phone',
                'label'    => 'rekrutmen::app.application_form.fields.phone',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'portfolio_url',
                'label'    => 'rekrutmen::app.application_form.fields.portfolio_url',
                'type'     => 'url',
                'required' => false,
            ],
            [
                'name'     => 'cover_letter',
                'label'    => 'rekrutmen::app.application_form.fields.cover_letter',
                'type'     => 'textarea',
                'required' => false,
            ],
            [
                'name'     => 'resume',
                'label'    => 'rekrutmen::app.application_form.fields.resume',
                'type'     => 'file',
                'required' => false,
            ],
        ],
        'by_slug' => [
            'software-engineer-jakarta' => [
                [
                    'name'     => 'github_url',
                    'label'    => 'rekrutmen::app.application_form.fields.github_url',
                    'type'     => 'url',
                    'required' => true,
                ],
                [
                    'name'     => 'expected_salary',
                    'label'    => 'rekrutmen::app.application_form.fields.expected_salary',
                    'type'     => 'number',
                    'required' => false,
                ],
            ],
        ],
    ],

    'security' => [
        'recaptcha' => [
            'enabled'         => env('REKRUTMEN_RECAPTCHA_ENABLED', false),
            'site_key'        => env('REKRUTMEN_RECAPTCHA_SITE_KEY'),
            'secret_key'      => env('REKRUTMEN_RECAPTCHA_SECRET_KEY'),
            'action'          => env('REKRUTMEN_RECAPTCHA_ACTION', 'request_man_power'),
            'score_threshold' => (float) env('REKRUTMEN_RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'timeout'         => (int) env('REKRUTMEN_RECAPTCHA_TIMEOUT', 5),
        ],
    ],
];
