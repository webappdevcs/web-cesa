<?php

return [
    'connections' => [
        'legacy_sync' => [
            'driver'         => env('LEGACY_SYNC_DB_CONNECTION', 'mysql'),
            'url'            => env('LEGACY_SYNC_DB_URL'),
            'host'           => env('LEGACY_SYNC_DB_HOST', '127.0.0.1'),
            'port'           => env('LEGACY_SYNC_DB_PORT', '3306'),
            'database'       => env('LEGACY_SYNC_DB_DATABASE', 'legacy_sync'),
            'username'       => env('LEGACY_SYNC_DB_USERNAME', 'root'),
            'password'       => env('LEGACY_SYNC_DB_PASSWORD', ''),
            'unix_socket'    => env('LEGACY_SYNC_DB_SOCKET', ''),
            'charset'        => env('LEGACY_SYNC_DB_CHARSET', 'utf8mb4'),
            'collation'      => env('LEGACY_SYNC_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('LEGACY_SYNC_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],
];
