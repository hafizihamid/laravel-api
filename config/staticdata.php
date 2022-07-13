<?php

return [
    'country_code' => '63',

    'status_codes' => [
        'ok' => '00',
        'validation_failed' => '21',
        'invalid_scope' => '22',
        'authentication_error' => '23',
        'permission_denied' => '24',
        'not_found' => '25',
        'forbidden' => '26',
        'duplicated' => '27',
        'timeout' => '28',
        'unverified_account' => '29',
        'limit_exceeded' => '30',
        'invalid_assignment' => '31',
        'phone_validation_failed' => '32',
        'error' => '99',
    ],

    'http_codes' => [
        'success' => '200',
        'bad_request' => '400',
        'unauthorized' => '401',
        'forbidden' => '403',
        'not_found' => '404',
        'unprocessable_entity' => '422',
        'internal_server_error' => '500',
        'request_timeout' => '408',
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL'),
        'reset_password_path' => env('FRONTEND_RESET_PASSWORD_PATH', '/reset?token={token}&email={email}'),
        'set_password_path' => env('FRONTEND_SET_PASSWORD_PATH', '/set?token={token}&email={email}'),
    ],

    'email' => [
        'mail_to_address' => env('MAIL_TO_ADDRESS'),
        'mail_to_name' => env('MAIL_TO_NAME'),
    ],

    'user_credential' => [
        'superadmin' => [
            'email' => 'hafizihamid92@gmail.com',
            'name' => 'hafizi',
            'password' => 'hafizi123',
        ],
    ],

    'token_scopes' => [
        'admin',
        'customer',
    ],
];
