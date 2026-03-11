<?php

namespace Webkul\Security\Tests;

use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class SecurityTestCase extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
    }
}
