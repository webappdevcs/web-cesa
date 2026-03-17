<?php

$csvToArray = static function (?string $value, bool $integers = false): array {
    $items = array_map(static fn (string $item): string => trim($item), explode(',', (string) $value));
    $items = array_values(array_filter($items, static fn (string $item): bool => $item !== ''));

    if (! $integers) {
        return array_map(static fn (string $item): string => strtolower($item), $items);
    }

    return array_values(array_map(static fn (string $item): int => (int) $item, array_filter(
        $items,
        static fn (string $item): bool => is_numeric($item),
    )));
};

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
    'shelf' => [
        'asset_transfers' => [
            'custodian_legacy_user_ids'    => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_LEGACY_USER_IDS'), true),
            'custodian_legacy_user_emails' => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_LEGACY_USER_EMAILS')),
            'custodian_legacy_user_names'  => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_LEGACY_USER_NAMES')),
            'custodian_target_user_ids'    => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_TARGET_USER_IDS'), true),
            'custodian_target_user_emails' => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_TARGET_USER_EMAILS')),
            'custodian_target_user_names'  => $csvToArray(env('LEGACY_SYNC_SHELF_CUSTODIAN_TARGET_USER_NAMES')),
            'fallback_to_role_inference'   => (bool) env('LEGACY_SYNC_SHELF_TRANSFER_FALLBACK_TO_ROLE_INFERENCE', true),
        ],
    ],
];
