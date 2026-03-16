<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\ShelfPlugin;
use Cesa\Shelf\ShelfServiceProvider;
use Tests\TestCase;

class ShelfPluginSmokeTest extends TestCase
{
    public function test_it_uses_the_shelf_identity(): void
    {
        $this->assertSame('shelf', ShelfServiceProvider::$name);
        $this->assertSame('shelf', app(ShelfPlugin::class)->getId());
    }
}
