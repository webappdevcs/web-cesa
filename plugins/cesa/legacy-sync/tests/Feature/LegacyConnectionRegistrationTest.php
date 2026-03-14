<?php

namespace Cesa\LegacySync\Tests\Feature;

use Tests\TestCase;

class LegacyConnectionRegistrationTest extends TestCase
{
    public function test_legacy_sync_connection_is_registered_into_database_connections(): void
    {
        $connection = config('database.connections.legacy_sync');

        $this->assertIsArray($connection);
        $this->assertSame(config('legacy-sync.connections.legacy_sync'), $connection);
    }
}
