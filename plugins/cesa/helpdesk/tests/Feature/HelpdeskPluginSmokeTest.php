<?php

namespace Cesa\Helpdesk\Tests\Feature;

use Cesa\Helpdesk\HelpdeskPlugin;
use Cesa\Helpdesk\HelpdeskServiceProvider;
use Tests\TestCase;

class HelpdeskPluginSmokeTest extends TestCase
{
    public function test_it_uses_the_helpdesk_identity(): void
    {
        $this->assertSame('helpdesk', HelpdeskServiceProvider::$name);
        $this->assertSame('helpdesk', app(HelpdeskPlugin::class)->getId());
    }
}
