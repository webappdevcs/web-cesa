<?php

namespace Cesa\Presensi\Tests\Feature;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Tests\PresensiTestCase;
use Filament\Pages\Page;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConfigurationsClusterAccessTest extends PresensiTestCase
{
    public function test_configurations_abort_when_no_clustered_component_is_accessible(): void
    {
        $cluster = new class extends Configurations
        {
            public static function getClusteredComponents(): array
            {
                return [InaccessiblePresensiConfigurationsPage::class];
            }
        };

        try {
            $cluster->mount();
            $this->fail('Expected the presensi configurations cluster to abort with 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}

class InaccessiblePresensiConfigurationsPage extends Page
{
    public static function canAccess(): bool
    {
        return false;
    }
}
