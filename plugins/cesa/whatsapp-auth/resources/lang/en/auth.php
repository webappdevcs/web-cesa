<?php

return [
    'title'      => 'Sign in with WhatsApp',
    'heading'    => 'Sign in with WhatsApp',
    'subheading' => 'Enter your registered WhatsApp number to receive an OTP code.',
    'or'         => 'or',

    'message' => "Your :app login OTP code is: *:code*\n\nThe code is valid for :minutes minutes. Do not share it with anyone.",

    'form' => [
        'phone' => [
            'label' => 'WhatsApp number',
        ],
        'code' => [
            'label'  => 'OTP code',
            'helper' => 'Code sent to :phone via WhatsApp.',
        ],
    ],

    'actions' => [
        'send_otp'             => 'Send OTP code',
        'verify_otp'           => 'Verify & sign in',
        'resend_otp'           => 'Change number / resend',
        'back_to_login'        => 'Back to email login',
        'login_with_whatsapp'  => 'Sign in with WhatsApp',
    ],

    'messages' => [
        'phone_not_registered'    => 'This WhatsApp number is not registered or the account is inactive.',
        'code_invalid'            => 'The OTP code is incorrect or has expired.',
        'code_too_many_attempts'  => 'Too many attempts. Please request a new code.',
        'resend_cooldown'         => 'Please wait :seconds seconds before requesting another code.',
    ],

    'notifications' => [
        'code_sent' => [
            'title' => 'An OTP code has been sent to your WhatsApp.',
        ],
        'delivery_failed' => [
            'title' => 'Failed to send the OTP code.',
            'body'  => 'There was a problem sending the WhatsApp message. Please try again shortly.',
        ],
        'cannot_access' => [
            'title' => 'This account cannot access the panel.',
        ],
        'throttled' => [
            'title' => 'Too many requests. Try again in :seconds seconds.',
        ],
    ],
];
