<?php

namespace Cesa\LegacySync;

use Cesa\LegacySync\Console\Commands\SyncAllLegacyData;
use Cesa\LegacySync\Console\Commands\SyncLegacySqlData;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class LegacySyncServiceProvider extends PackageServiceProvider
{
    public static string $name = 'legacy-sync';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->isCore()
            ->hasConfigFile('legacy-sync')
            ->hasCommands([
                SyncLegacySqlData::class,
                SyncAllLegacyData::class,
            ])
            ->hasMigrations([
                '2026_03_12_004250_create_legacy_sync_mappings_table',
            ])
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $legacyConnections = config('legacy-sync.connections', []);
        $databaseConnections = config('database.connections', []);

        if (! is_array($legacyConnections) || ! is_array($databaseConnections)) {
            return;
        }

        config([
            'database.connections' => array_replace($legacyConnections, $databaseConnections),
        ]);
    }
}
