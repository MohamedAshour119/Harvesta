<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class RepositoryTestCase extends TestCase
{
    use RefreshDatabase;
    use RegistersRepositorySqliteFunctions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRepositorySqliteFunctions();
    }
}
