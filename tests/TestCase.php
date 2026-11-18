<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI runs the suite without `npm run build`, so don't require a Vite manifest.
        $this->withoutVite();
    }
}
